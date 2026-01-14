<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';

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
  // Get all questions for the course
  $stmt = $pdo->prepare("
    SELECT
      q.id,
      q.course_id,
      q.question_text,
      q.question_type,
      q.created_at
    FROM questions q
    WHERE q.course_id = :course_id AND q.lesson_id IS NULL
    ORDER BY q.id ASC
  ");

  $stmt->execute(['course_id' => $courseId]);
  $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // If no questions, return empty array
  if (empty($questions)) {
    sendOk([]);
    return;
  }

  // Get options for each question
  foreach ($questions as &$question) {
    $optStmt = $pdo->prepare("
      SELECT
        id,
        question_id,
        option_text,
        is_correct
      FROM question_options
      WHERE question_id = :question_id
      ORDER BY id ASC
    ");

    $optStmt->execute(['question_id' => $question['id']]);
    $question['options'] = $optStmt->fetchAll(PDO::FETCH_ASSOC);
  }

  sendOk($questions);

} catch (PDOException $e) {
  error_log("Get course questions failed: " . $e->getMessage());
  sendServerError("Failed to retrieve questions.");
}
