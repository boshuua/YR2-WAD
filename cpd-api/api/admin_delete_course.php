<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require DELETE method
requireMethod('DELETE');

// Require admin authentication
requireAdmin();

// Get course ID from query string
$courseId = isset($_GET['id']) ? getInt($_GET['id']) : 0;

if ($courseId <= 0) {
    sendBadRequest("Invalid course ID provided.");
}

// Get database connection
$database = new Database();
$db = $database->getConn();

// Fetch course title for logging
$courseTitleToLog = 'Unknown (Course not found)';
try {
    $fetchTitleQuery = "SELECT title FROM courses WHERE id = :id";
    $fetchStmt = $db->prepare($fetchTitleQuery);
    $fetchStmt->bindParam(':id', $courseId, PDO::PARAM_INT);
    $fetchStmt->execute();
    if ($fetchStmt->rowCount() > 0) {
        $courseTitleToLog = $fetchStmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch course title before delete: " . $e->getMessage());
}

// Execute deletion
try {
    $query = "DELETE FROM courses WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(":id", $courseId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            log_activity($db, getCurrentUserId(), getCurrentUserEmail(), 'Course Deleted', "Course ID: {$courseId}, Title: {$courseTitleToLog}");
            sendOk(["message" => "Course deleted successfully."]);
        } else {
            log_activity($db, getCurrentUserId(), getCurrentUserEmail(), 'Course Deletion Failed', "Course ID: {$courseId} not found.");
            sendNotFound("Course not found or already deleted.");
        }
    } else {
        log_activity($db, getCurrentUserId(), getCurrentUserEmail(), 'Course Deletion Failed', "Course ID: {$courseId}, DB execution error.");
        sendServiceUnavailable("Unable to delete course.");
    }
} catch (PDOException $e) {
    error_log("Database error during course delete: " . $e->getMessage());
    log_activity($db, getCurrentUserId(), getCurrentUserEmail(), 'Course Deletion Failed', "Course ID: {$courseId}, Error: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred during deletion.");
}
?>