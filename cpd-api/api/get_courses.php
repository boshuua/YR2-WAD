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
// Prepare and execute query
$type = $_GET['type'] ?? 'all';
$sql = "SELECT id, title, description, duration, category, status, instructor_id, start_date, end_date, is_locked, is_template
          FROM courses
          WHERE 1=1";

if ($type === 'locked') {
    $sql .= " AND is_locked = TRUE";
} else if ($type === 'library') {
    $sql .= " AND (is_template = TRUE OR is_locked = TRUE)";
} else if ($type === 'active') {
    // Active usually means filtered instances (not templates) or just non-archived?
    // Based on previous code implied usage:
    $sql .= " AND is_template = FALSE AND is_locked = FALSE";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute();

// Fetch results
$courses_arr = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    extract($row);
    $course_item = array(
        "id" => $id,
        "title" => $title,
        "description" => html_entity_decode($description),
        "duration" => $duration,
        "category" => $category,
        "status" => $status,
        "instructor_id" => $instructor_id,
        "start_date" => $start_date,
        "end_date" => $end_date,
        "is_locked" => (bool) $is_locked
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