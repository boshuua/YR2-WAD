<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require authentication
requireAuth();

// Get course ID from query parameter
$courseId = $_GET['course_id'] ?? null;

if (!$courseId || !is_numeric($courseId)) {
  sendBadRequest("Course ID is required and must be numeric.");
}

// Get database connection
$database = new Database();
$pdo = $database->getConn();

try {
  // Get all lessons for the course
  $stmt = $pdo->prepare("
    SELECT id, title, content, order_index
    FROM lessons
    WHERE course_id = :course_id
    ORDER BY order_index ASC, id ASC
  ");

  $stmt->execute(['course_id' => $courseId]);
  $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // If no lessons, return empty array
  if (empty($lessons)) {
    sendOk([]);
    exit();
  }

  // Get checkpoint quiz questions for each lesson
  foreach ($lessons as &$lesson) {
    $qStmt = $pdo->prepare("
      SELECT id, question_text, question_type
      FROM questions
      WHERE lesson_id = :lesson_id
      ORDER BY id ASC
    ");
    $qStmt->execute(['lesson_id' => $lesson['id']]);
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get options for each question
    foreach ($questions as &$question) {
      $optStmt = $pdo->prepare("
        SELECT id, option_text, is_correct
        FROM question_options
        WHERE question_id = :question_id
        ORDER BY id ASC
      ");
      $optStmt->execute(['question_id' => $question['id']]);
      $question['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $lesson['checkpoint_quiz'] = $questions;
  }

  sendOk($lessons);

} catch (PDOException $e) {
  error_log("Get course lessons failed: " . $e->getMessage());
  sendServerError("Failed to retrieve lessons.");
}
?>
