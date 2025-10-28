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
if (empty($data->course_id) || empty($data->status)) {
    http_response_code(400);
    echo json_encode(["message" => "Course ID and status are required."]);
    exit();
}

$userId = $_SESSION['user_id'];
$courseId = (int)$data->course_id;
$status = htmlspecialchars(strip_tags($data->status));
$score = isset($data->score) ? (float)$data->score : null;

// Allowed statuses
$allowedStatuses = ['not_started', 'in_progress', 'completed'];
if (!in_array($status, $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid course status provided."]);
    exit();
}

try {
    // Check if progress already exists
    $existingProgressQuery = "SELECT id FROM user_course_progress WHERE user_id = :user_id AND course_id = :course_id";
    $existingProgressStmt = $db->prepare($existingProgressQuery);
    $existingProgressStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $existingProgressStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
    $existingProgressStmt->execute();

    if ($existingProgressStmt->rowCount() > 0) {
        // Update existing progress
        $updateQuery = "UPDATE user_course_progress
                        SET status = :status,
                            completion_date = CASE WHEN :status_case = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END,
                            score = :score,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = :user_id AND course_id = :course_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':status', $status, PDO::PARAM_STR);
        $updateStmt->bindParam(':status_case', $status, PDO::PARAM_STR);
        $updateStmt->bindParam(':score', $score, $score === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $updateStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $updateStmt->execute();
    } else {
        // Insert new progress
        $insertQuery = "INSERT INTO user_course_progress (
                            user_id, 
                            course_id, 
                            status,
                            completion_date,
                            score
                        ) VALUES (
                            :user_id, 
                            :course_id, 
                            :status,
                            CASE WHEN :status_case = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END,
                            :score
                        )";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $insertStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $insertStmt->bindParam(':status', $status, PDO::PARAM_STR);
        $insertStmt->bindParam(':status_case', $status, PDO::PARAM_STR);
        $insertStmt->bindParam(':score', $score, $score === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $insertStmt->execute();
    }

    http_response_code(200);
    echo json_encode(["message" => "Course progress updated successfully."]);
    log_activity($db, $userId, $_SESSION['user_email'], 'Course Progress Updated', "Course ID: {$courseId}, Status: {$status}");

} catch (PDOException $e) {
    http_response_code(503);
    error_log("Database error updating course progress: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred while updating course progress."]);
    log_activity($db, $userId, $_SESSION['user_email'], 'Course Progress Update Failed', "Course ID: {$courseId}, Error: " . $e->getMessage());
}

?>