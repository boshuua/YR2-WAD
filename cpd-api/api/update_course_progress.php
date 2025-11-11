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

// Require authentication
requireAuth();

// Get user ID from session
$userId = getCurrentUserId();

// Get and validate input
$data = getJsonInput();
requireFields($data, ['course_id', 'status']);

$courseId = getInt($data->course_id);
$status = sanitizeString($data->status);
$score = getValue($data, 'score');

// Validate status
requireInList($status, ['not_started', 'in_progress', 'completed'], 'status');

// Get database connection
$database = new Database();
$db = $database->getConn();

// Update or insert progress
try {
    // Check if progress already exists
    $existingProgressQuery = "SELECT id FROM user_course_progress WHERE user_id = :user_id AND course_id = :course_id";
    $existingProgressStmt = $db->prepare($existingProgressQuery);
    $existingProgressStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $existingProgressStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
    $existingProgressStmt->execute();

    if ($existingProgressStmt->rowCount() > 0) {
        // Update existing progress
        $updateQuery = "UPDATE user_course_progress
                        SET status = :status,
                            completion_date = CASE WHEN :status_case = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END,
                            score = :score,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = :user_id AND course_id = :course_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':status', $status, PDO::PARAM_STR);
        $updateStmt->bindParam(':status_case', $status, PDO::PARAM_STR);
        $updateStmt->bindParam(':score', $score, $score === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $updateStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $updateStmt->execute();
    } else {
        // Insert new progress
        $insertQuery = "INSERT INTO user_course_progress (
                            user_id,
                            course_id,
                            status,
                            completion_date,
                            score
                        ) VALUES (
                            :user_id,
                            :course_id,
                            :status,
                            CASE WHEN :status_case = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END,
                            :score
                        )";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $insertStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $insertStmt->bindParam(':status', $status, PDO::PARAM_STR);
        $insertStmt->bindParam(':status_case', $status, PDO::PARAM_STR);
        $insertStmt->bindParam(':score', $score, $score === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $insertStmt->execute();
    }

    log_activity($db, $userId, getCurrentUserEmail(), 'Course Progress Updated', "Course ID: {$courseId}, Status: {$status}");
    sendOk(["message" => "Course progress updated successfully."]);
} catch (PDOException $e) {
    error_log("Database error updating course progress: " . $e->getMessage());
    log_activity($db, $userId, getCurrentUserEmail(), 'Course Progress Update Failed', "Course ID: {$courseId}, Error: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred while updating course progress.");
}
?>