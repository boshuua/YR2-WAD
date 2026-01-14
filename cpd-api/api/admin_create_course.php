<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require POST method
requireMethod('POST');

// Require admin authentication
requireAdmin();

// Get and validate input
$data = getJsonInput();
requireFields($data, ['title', 'description']);

// Get database connection
$database = new Database();
$db = $database->getConn();

// Prepare insert query
$query = "INSERT INTO courses (title, description, content, duration, required_hours, category, status, instructor_id, start_date, end_date)
          VALUES (:title, :description, :content, :duration, :required_hours, :category, :status, :instructor_id, :start_date, :end_date)";

$stmt = $db->prepare($query);

// Sanitize and prepare data
$title = sanitizeString($data->title);
$description = sanitizeString($data->description);
$content = getValue($data, 'content', '');
$duration = getValue($data, 'duration');
$required_hours = getValue($data, 'required_hours', 3.00);
$category = getValue($data, 'category');
$status = getValue($data, 'status', 'draft');
$instructor_id = getValue($data, 'instructor_id');
$start_date = getValue($data, 'start_date');
$end_date = getValue($data, 'end_date');

// Validate status if provided
if ($status && !isInList($status, ['draft', 'published'])) {
    sendBadRequest("Invalid status. Must be 'draft' or 'published'.");
}

// Bind parameters
$stmt->bindParam(':title', $title);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':content', $content);
$stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
$stmt->bindParam(':required_hours', $required_hours);
$stmt->bindParam(':category', $category);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);

// Execute and respond
if ($stmt->execute()) {
    log_activity($db, getCurrentUserId(), getCurrentUserEmail(), "Course Created", "Course: {$title}");
    sendCreated(["message" => "Course was created."]);
} else {
    log_activity($db, getCurrentUserId(), getCurrentUserEmail(), "Course Creation Failed", "Course: {$title}");
    sendServerError("Unable to create course.");
}
?>