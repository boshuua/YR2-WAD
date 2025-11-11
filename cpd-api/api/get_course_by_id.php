<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require GET method
requireMethod('GET');

// Get course ID from query string
$courseId = isset($_GET['id']) ? getInt($_GET['id']) : 0;

if ($courseId <= 0) {
    sendBadRequest("Invalid course ID provided.");
}

// Get database connection
$database = new Database();
$db = $database->getConn();

// Fetch course by ID
$query = "SELECT id, title, description, content, duration, category, status, instructor_id, start_date, end_date
          FROM courses
          WHERE id = :id
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $course_item = array(
        "id" => $row['id'],
        "title" => $row['title'],
        "description" => html_entity_decode($row['description']),
        "content" => html_entity_decode($row['content']),
        "duration" => $row['duration'],
        "category" => $row['category'],
        "status" => $row['status'],
        "instructor_id" => $row['instructor_id'],
        "start_date" => $row['start_date'],
        "end_date" => $row['end_date']
    );

    sendOk($course_item);
} else {
    sendNotFound("Course not found.");
}
?>