<?php
session_start();
include_once '../config/database.php';

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid course ID provided."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$query = "SELECT id, title, description, content, duration, category, status, instructor_id, start_date, end_date FROM courses WHERE id = :id LIMIT 1 OFFSET 0";

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
        "content" => html_entity_decode($content),
        "duration" => $duration,
        "category" => $category,
        "status" => $status,
        "instructor_id" => $instructor_id,
        "start_date" => $start_date,
        "end_date" => $end_date
    );

    http_response_code(200);
    echo json_encode($course_item);
} else {
    http_response_code(404);
    echo json_encode(array("message" => "Course not found."));
}

?>