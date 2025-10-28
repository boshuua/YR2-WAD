<?php
session_start();
include_once '../config/database.php';
include_once '../helpers/log_helper.php';

if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = json_decode(file_get_contents("php://input"));

if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid course ID provided."]);
    exit();
}

if (!isset($data->title) || !isset($data->description)) {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. Title and description are required."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$query = "UPDATE courses SET title = :title, description = :description, content = :content, updated_at = CURRENT_TIMESTAMP WHERE id = :id";

$stmt = $db->prepare($query);

$title = htmlspecialchars(strip_tags($data->title));
$description = htmlspecialchars(strip_tags($data->description));
$content = isset($data->content) ? htmlspecialchars($data->content) : '';

$stmt->bindParam(':title', $title);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':content', $content);
$stmt->bindParam(':id', $courseId, PDO::PARAM_INT);

try {
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["message" => "Course updated successfully."]);
            log_activity($db, null, null, "Course Updated", "Course ID: {$courseId}, Title: {$data->title}");
        } else {
            $checkQuery = "SELECT COUNT(*) FROM courses WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $courseId, PDO::PARAM_INT);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn() > 0) {
                http_response_code(200);
                echo json_encode(["message" => "No changes detected for the course."]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Course not found."]);
            }
        }
    } else {
        http_response_code(503);
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