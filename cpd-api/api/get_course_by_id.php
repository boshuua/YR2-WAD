<?php
session_start();
include_once '../config/database.php';

// --- Security Check ---
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

// --- Get Input Data ---
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- Validate Input ---
if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid course ID provided."]);
    exit();
}

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

// --- Prepare SELECT Query ---
$query = "SELECT id, title, description, code, duration, category, instructor_id, status, created_at, updated_at
          FROM courses
          WHERE id = :id
          LIMIT 1 OFFSET 0";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
$stmt->execute();

$num = $stmt->rowCount();

if ($num > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    extract($row);

    $course_item = array(
        "id" => $id,
        "title" => $title,
        "description" => html_entity_decode($description),
        "code" => $code,
        "duration" => $duration,
        "category" => $category,
        "instructor_id" => $instructor_id,
        "status" => $status,
        "created_at" => $created_at,
        "updated_at" => $updated_at
    );

    http_response_code(200);
    echo json_encode($course_item);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "Course not found."));
}

?>