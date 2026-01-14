<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require PUT method
requireMethod('PUT');

// Require admin authentication
requireAdmin();

// Get course ID from query string
$courseId = isset($_GET['id']) ? getInt($_GET['id']) : 0;

if ($courseId <= 0) {
    sendBadRequest("Invalid course ID provided.");
}

// Get and validate input
$data = getJsonInput();
requireFields($data, ['title', 'description']);

// Get database connection
$database = new Database();
$db = $database->getConn();

// Prepare update query
$query = "UPDATE courses
          SET title = :title,
              description = :description,
              content = :content,
              duration = :duration,
              required_hours = :required_hours,
              category = :category,
              status = :status,
              instructor_id = :instructor_id,
              start_date = :start_date,
              end_date = :end_date,
              updated_at = CURRENT_TIMESTAMP
          WHERE id = :id";

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
$stmt->bindParam(':id', $courseId, PDO::PARAM_INT);

// Execute and respond
try {
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            log_activity($db, getCurrentUserId(), getCurrentUserEmail(), "Course Updated", "Course ID: {$courseId}, Title: {$title}");
            sendOk(["message" => "Course updated successfully."]);
        } else {
            // Check if course exists but no changes were made
            $checkQuery = "SELECT COUNT(*) FROM courses WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $courseId, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->fetchColumn() > 0) {
                sendOk(["message" => "No changes detected for the course."]);
            } else {
                sendNotFound("Course not found.");
            }
        }
    } else {
        log_activity($db, getCurrentUserId(), getCurrentUserEmail(), "Course Update Failed", "Course ID: {$courseId}");
        sendServiceUnavailable("Unable to update course.");
    }
} catch (PDOException $e) {
    error_log("Database error during course update: " . $e->getMessage());
    log_activity($db, getCurrentUserId(), getCurrentUserEmail(), "Course Update Failed", "Course ID: {$courseId}, Error: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred during update.");
}
?>