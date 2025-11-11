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
requireFields($data, ['lesson_id', 'status']);

$lessonId = getInt($data->lesson_id);
$status = sanitizeString($data->status);

// Validate status
requireInList($status, ['not_started', 'in_progress', 'completed'], 'status');

// Get database connection
$database = new Database();
$db = $database->getConn();

// Update or insert progress
try {
    // Check if progress already exists
    $existingProgressQuery = "SELECT id FROM user_lesson_progress WHERE user_id = :user_id AND lesson_id = :lesson_id";
    $existingProgressStmt = $db->prepare($existingProgressQuery);
    $existingProgressStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $existingProgressStmt->bindParam(':lesson_id', $lessonId, PDO::PARAM_INT);
    $existingProgressStmt->execute();

    if ($existingProgressStmt->rowCount() > 0) {
        // Update existing progress
        $updateQuery = "UPDATE user_lesson_progress
                        SET status = :status,
                            completion_date = CASE WHEN :status = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = :user_id AND lesson_id = :lesson_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':status', $status, PDO::PARAM_STR);
        $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $updateStmt->bindParam(':lesson_id', $lessonId, PDO::PARAM_INT);
        $updateStmt->execute();
    } else {
        // Insert new progress
        $insertQuery = "INSERT INTO user_lesson_progress (
                            user_id,
                            lesson_id,
                            status,
                            completion_date
                        ) VALUES (
                            :user_id,
                            :lesson_id,
                            :status,
                            CASE WHEN :status = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END
                        )";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $insertStmt->bindParam(':lesson_id', $lessonId, PDO::PARAM_INT);
        $insertStmt->bindParam(':status', $status, PDO::PARAM_STR);
        $insertStmt->execute();
    }

    log_activity($db, $userId, getCurrentUserEmail(), 'Lesson Progress Updated', "Lesson ID: {$lessonId}, Status: {$status}");
    sendOk(["message" => "Lesson progress updated successfully."]);
} catch (PDOException $e) {
    error_log("Database error updating lesson progress: " . $e->getMessage());
    log_activity($db, $userId, getCurrentUserEmail(), 'Lesson Progress Update Failed', "Lesson ID: {$lessonId}, Error: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred while updating lesson progress.");
}
?>