<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require GET method
requireMethod('GET');

// Get database connection
$database = new Database();
$db = $database->getConn();

// Prepare and execute query
$query = "SELECT id, title, description, duration, category, status, instructor_id, start_date, end_date
          FROM courses
          ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();

// Fetch results
$courses_arr = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $course_item = array(
        "id" => $row['id'],
        "title" => $row['title'],
        "description" => html_entity_decode($row['description']),
        "duration" => $row['duration'],
        "category" => $row['category'],
        "status" => $row['status'],
        "instructor_id" => $row['instructor_id'],
        "start_date" => $row['start_date'],
        "end_date" => $row['end_date']
    );
    array_push($courses_arr, $course_item);
}

// Log activity
log_activity($db, null, null, "Viewed Courses", "All courses listed.");

// Send response
if (!empty($courses_arr)) {
    sendOk($courses_arr);
} else {
    sendNotFound("No courses found.");
}
?>