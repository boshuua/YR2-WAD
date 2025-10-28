<?php
session_start();
include_once '../config/database.php';
include_once '../helpers/log_helper.php';

// --- Security Check ---
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

// --- Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed. Use PUT."]);
    exit();
}

// --- Get Input Data ---
$lessonId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = json_decode(file_get_contents("php://input"));

// --- Validate Input ---
if ($lessonId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid lesson ID provided."]);
    exit();
}

if (empty($data->title) || empty($data->course_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. Course ID and title are required."]);
    exit();
}

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

// --- Prepare Update Query ---
$query = "UPDATE lessons
          SET course_id = :course_id,
              title = :title,
              description = :description,
              order_index = :order_index,
              updated_at = CURRENT_TIMESTAMP
          WHERE id = :id";

$stmt = $db->prepare($query);

// Sanitize and bind values
$course_id = (int)$data->course_id;
$title = htmlspecialchars(strip_tags($data->title));
$description = isset($data->description) ? htmlspecialchars(strip_tags($data->description)) : null;
$order_index = isset($data->order_index) ? (int)$data->order_index : 0;

$stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmt->bindParam(':title', $title);
$stmt->bindParam(':description', $description, PDO::PARAM_STR);
$stmt->bindParam(':order_index', $order_index, PDO::PARAM_INT);
$stmt->bindParam(':id', $lessonId, PDO::PARAM_INT);

// --- Execute and Respond ---
try {
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["message" => "Lesson updated successfully."]);
            log_activity($db, null, null, "Lesson Updated", "Lesson ID: {$lessonId}, Title: {$title}");
        } else {
            $checkQuery = "SELECT COUNT(*) FROM lessons WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $lessonId, PDO::PARAM_INT);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn() > 0) {
                http_response_code(200);
                echo json_encode(["message" => "No changes detected for the lesson."]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Lesson not found."]);
            }
        }
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update lesson."]);
        log_activity($db, null, null, "Lesson Update Failed", "Lesson ID: {$lessonId}, Title: {$title}");
    }
} catch (PDOException $e) {
    http_response_code(503);
    error_log("Database error during lesson update: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred during update."]);
    log_activity($db, null, null, "Lesson Update Failed", "Lesson ID: {$lessonId}, Error: " . $e->getMessage());
}

?>