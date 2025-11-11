<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require GET method
requireMethod('GET');

// Require authentication
requireAuth();

// Get user ID from session
$userId = getCurrentUserId();

// Get database connection
$database = new Database();
$db = $database->getConn();

// Fetch courses with user progress
$query = "SELECT
            c.id AS course_id,
            c.title,
            c.description,
            ucp.status AS user_progress_status,
            ucp.completion_date,
            ucp.score
          FROM courses c
          LEFT JOIN user_course_progress ucp ON c.id = ucp.course_id AND ucp.user_id = :user_id
          ORDER BY c.title ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();

$courses_arr = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $course_item = array(
        "id" => $row['course_id'],
        "title" => $row['title'],
        "description" => html_entity_decode($row['description']),
        "user_progress_status" => $row['user_progress_status'] ?? 'not_started',
        "completion_date" => $row['completion_date'],
        "score" => $row['score']
    );
    array_push($courses_arr, $course_item);
}

if (!empty($courses_arr)) {
    sendOk($courses_arr);
} else {
    sendNotFound("No courses found.");
}
?>