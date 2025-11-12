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
$required = ['course_id', 'score'];
validateRequired($input, $required);

$courseId = $input['course_id'];
$score = $input['score'];
$userId = getCurrentUserId();

// Validate score is between 0 and 100
if ($score < 0 || $score > 100) {
  sendBadRequest("Score must be between 0 and 100.");
}

// Get database connection
$database = new Database();
$pdo = $database->getConn();

try {
  // Check if user is enrolled
  $checkStmt = $pdo->prepare("
    SELECT id, status FROM user_course_progress
    WHERE user_id = :user_id AND course_id = :course_id
  ");
  $checkStmt->execute([
    'user_id' => $userId,
    'course_id' => $courseId
  ]);

  $progress = $checkStmt->fetch(PDO::FETCH_ASSOC);

  if (!$progress) {
    sendBadRequest("You must be enrolled in this course to submit a quiz.");
  }

  // Determine new status based on score
  // Must get 80% or higher to complete the course
  $newStatus = $score >= 80 ? 'completed' : 'in_progress';
  $completionDate = $score >= 80 ? date('Y-m-d H:i:s') : null;

  // Update user progress with score
  $stmt = $pdo->prepare("
    UPDATE user_course_progress
    SET score = :score,
        status = :status,
        completion_date = :completion_date,
        updated_at = NOW()
    WHERE user_id = :user_id AND course_id = :course_id
  ");

  $stmt->execute([
    'score' => $score,
    'status' => $newStatus,
    'completion_date' => $completionDate,
    'user_id' => $userId,
    'course_id' => $courseId
  ]);

  // Log activity
  $statusText = $score >= 80 ? "passed" : "attempted";
  log_activity($pdo, $userId, getCurrentUserEmail(), 'submit_quiz', "Quiz $statusText for course ID: $courseId with score: $score%");

  $message = $score >= 80
    ? "Congratulations! You passed the course with a score of {$score}%."
    : "Quiz submitted with score of {$score}%. You need 80% or higher to complete the course.";

  sendOk([
    "message" => $message,
    "score" => $score,
    "status" => $newStatus,
    "passed" => $score >= 80
  ]);

} catch (PDOException $e) {
  error_log("Quiz submission failed: " . $e->getMessage());
  sendServerError("Failed to submit quiz.");
}
