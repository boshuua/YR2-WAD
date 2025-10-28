<?php
session_start();
include_once '../config/database.php';
include_once '../helpers/log_helper.php';

// --- Security Check ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["message" => "Access Denied: User not logged in."]);
    exit();
}

// --- Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$data = json_decode(file_get_contents("php://input"));

// --- Validate Input ---
if (empty($data->lesson_id) || empty($data->status)) {
    http_response_code(400);
    echo json_encode(["message" => "Lesson ID and status are required."]);
    exit();
}

$userId = $_SESSION['user_id'];
$lessonId = (int)$data->lesson_id;
$status = htmlspecialchars(strip_tags($data->status));

// Allowed statuses
$allowedStatuses = ['not_started', 'in_progress', 'completed'];
if (!in_array($status, $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid lesson status provided."]);
    exit();
}

try {
    // Check if progress already exists
    $existingProgressQuery = "SELECT id FROM user_lesson_progress WHERE user_id = :user_id AND lesson_id = :lesson_id";
    $existingProgressStmt = $db->prepare($existingProgressQuery);
    $existingProgressStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $existingProgressStmt->bindParam(':lesson_id', $lessonId, PDO::PARAM_INT);
    $existingProgressStmt->execute();

    if ($existingProgressStmt->rowCount() > 0) {
        // Update existing progress
        $updateQuery = "UPDATE user_lesson_progress
                        SET status = :status,
                            completion_date = CASE WHEN :status = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = :user_id AND lesson_id = :lesson_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':status', $status, PDO::PARAM_STR);
        $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $updateStmt->bindParam(':lesson_id', $lessonId, PDO::PARAM_INT);
        $updateStmt->execute();
    } else {
        // Insert new progress
        $insertQuery = "INSERT INTO user_lesson_progress (
                            user_id, 
                            lesson_id, 
                            status,
                            completion_date
                        ) VALUES (
                            :user_id, 
                            :lesson_id, 
                            :status,
                            CASE WHEN :status = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END
                        )";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $insertStmt->bindParam(':lesson_id', $lessonId, PDO::PARAM_INT);
        $insertStmt->bindParam(':status', $status, PDO::PARAM_STR);
        $insertStmt->execute();
    }

    http_response_code(200);
    echo json_encode(["message" => "Lesson progress updated successfully."]);
    log_activity($db, $userId, $_SESSION['user_email'], 'Lesson Progress Updated', "Lesson ID: {$lessonId}, Status: {$status}");

} catch (PDOException $e) {
    http_response_code(503);
    error_log("Database error updating lesson progress: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred while updating lesson progress."]);
    log_activity($db, $userId, $_SESSION['user_email'], 'Lesson Progress Update Failed', "Lesson ID: {$lessonId}, Error: " . $e->getMessage());
}

?>