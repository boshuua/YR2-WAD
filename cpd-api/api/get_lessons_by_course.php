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
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$lessonId = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Optional: to get a single lesson

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

if ($lessonId > 0) {
    // Fetch a single lesson by ID
    $query = "SELECT id, course_id, title, description, order_index, created_at, updated_at
              FROM lessons
              WHERE id = :id AND course_id = :course_id
              LIMIT 1 OFFSET 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $lessonId, PDO::PARAM_INT);
    $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode($lesson);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Lesson not found for this course."]);
    }
} else if ($courseId > 0) {
    // Fetch all lessons for a specific course
    $query = "SELECT id, course_id, title, description, order_index, created_at, updated_at
              FROM lessons
              WHERE course_id = :course_id
              ORDER BY order_index ASC, title ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
    $stmt->execute();

    $lessons_arr = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        $lesson_item = array(
            "id" => $id,
            "course_id" => $course_id,
            "title" => $title,
            "description" => html_entity_decode($description),
            "order_index" => $order_index,
            "created_at" => $created_at,
            "updated_at" => $updated_at
        );
        array_push($lessons_arr, $lesson_item);
    }

    if (!empty($lessons_arr)) {
        http_response_code(200);
        echo json_encode($lessons_arr);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "No lessons found for this course."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Course ID is required."]);
}

?>