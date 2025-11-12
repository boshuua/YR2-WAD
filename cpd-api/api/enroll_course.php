<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require authentication
requireAuth();

// Get JSON input
$input = getJsonInput(true);

// Validate required fields
$required = ['course_id'];
validateRequired($input, $required);

$courseId = $input['course_id'];
$userId = getCurrentUserId();

// Get database connection
$database = new Database();
$pdo = $database->getConn();

try {
  // Check if user is already enrolled
  $checkStmt = $pdo->prepare("
    SELECT id FROM user_course_progress
    WHERE user_id = :user_id AND course_id = :course_id
  ");
  $checkStmt->execute([
    'user_id' => $userId,
    'course_id' => $courseId
  ]);

  if ($checkStmt->fetch()) {
    sendBadRequest("You are already enrolled in this course.");
  }

  // Enroll user in the course
  $stmt = $pdo->prepare("
    INSERT INTO user_course_progress (user_id, course_id, status, enrolled_at)
    VALUES (:user_id, :course_id, 'not_started', NOW())
  ");

  $stmt->execute([
    'user_id' => $userId,
    'course_id' => $courseId
  ]);

  // Log activity
  log_activity($pdo, $userId, getCurrentUserEmail(), 'enroll_course', "Enrolled in course ID: $courseId");

  sendOk([
    "message" => "Successfully enrolled in the course.",
    "course_id" => $courseId
  ]);

} catch (PDOException $e) {
  error_log("Course enrollment failed: " . $e->getMessage());
  sendServerError("Failed to enroll in course.");
}
