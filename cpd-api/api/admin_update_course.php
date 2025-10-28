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

// --- Get Input Data ---
// Expecting ID in query string like ?id=123
// Expecting course data in JSON body for PUT request
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = json_decode(file_get_contents("php://input"));

// --- Validate Input ---
if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid course ID provided."]);
    exit();
}

if (!isset($data->title) || !isset($data->description) || !isset($data->category)) {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. Title, description, and category are required."]);
    exit();
}

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

// --- Prepare Update Query ---
$query = "UPDATE courses
          SET title = :title,
              description = :description,
              code = :code,
              duration = :duration,
              category = :category,
              instructor_id = :instructor_id,
              status = :status,
              updated_at = CURRENT_TIMESTAMP
          WHERE id = :id";

$stmt = $db->prepare($query);

// Handle optional fields safely
$code = isset($data->code) ? htmlspecialchars(strip_tags($data->code)) : null;
$duration = isset($data->duration) ? (int)$data->duration : null;
$instructor_id = isset($data->instructor_id) ? (int)$data->instructor_id : null;

// Bind parameters
$stmt->bindParam(':title', $data->title);
$stmt->bindParam(':description', $data->description);
$stmt->bindParam(':code', $code, PDO::PARAM_STR);
$stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
$stmt->bindParam(':category', $data->category);
$stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmt->bindParam(':status', $data->status);
$stmt->bindParam(':id', $courseId, PDO::PARAM_INT);

// --- Execute and Respond ---
try {
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["message" => "Course updated successfully."]);
            log_activity($db, null, null, "Course Updated", "Course ID: {$courseId}, Title: {$data->title}");
        } else {
            // Check if course exists but no changes were made or course not found
            $checkQuery = "SELECT COUNT(*) FROM courses WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $courseId, PDO::PARAM_INT);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn() > 0) {
                http_response_code(200); // OK, but no changes needed
                echo json_encode(["message" => "No changes detected for the course."]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(["message" => "Course not found."]);
            }
        }
    } else {
        http_response_code(503); // Service Unavailable
        echo json_encode(["message" => "Unable to update course."]);
        log_activity($db, null, null, "Course Update Failed", "Course ID: {$courseId}, Title: {$data->title}");
    }
} catch (PDOException $e) {
    http_response_code(503);
    error_log("Database error during course update: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred during update."]);
    log_activity($db, null, null, "Course Update Failed", "Course ID: {$courseId}, Error: " . $e->getMessage());
}

?>