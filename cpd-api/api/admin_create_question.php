<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require admin authentication
requireAdmin();

// Get JSON input
$input = getJsonInput(true);

// Validate required fields
$required = ['course_id', 'question_text', 'question_type', 'options'];
validateRequired($input, $required);

$courseId = $input['course_id'];
$questionText = trim($input['question_text']);
$questionType = trim($input['question_type']);
$options = $input['options']; // Array of options with option_text and is_correct

// Validate question type
$validTypes = ['multiple_choice', 'true_false'];
if (!in_array($questionType, $validTypes)) {
  sendBadRequest("Invalid question type. Must be 'multiple_choice' or 'true_false'.");
}

// Validate options
if (!is_array($options) || count($options) < 2) {
  sendBadRequest("At least 2 options are required.");
}

// Check that at least one option is correct
$hasCorrect = false;
foreach ($options as $option) {
  if (isset($option['is_correct']) && $option['is_correct'] == true) {
    $hasCorrect = true;
    break;
  }
}

if (!$hasCorrect) {
  sendBadRequest("At least one option must be marked as correct.");
}

// Get database connection
$database = new Database();
$pdo = $database->getConn();

try {
  // Start transaction
  $pdo->beginTransaction();

  // Insert question
  $stmt = $pdo->prepare("
    INSERT INTO questions (course_id, question_text, question_type)
    VALUES (:course_id, :question_text, :question_type)
    RETURNING id
  ");

  $stmt->execute([
    'course_id' => $courseId,
    'question_text' => $questionText,
    'question_type' => $questionType
  ]);

  $questionId = $stmt->fetchColumn();

  // Insert options
  $optStmt = $pdo->prepare("
    INSERT INTO question_options (question_id, option_text, is_correct)
    VALUES (:question_id, :option_text, :is_correct)
  ");

  foreach ($options as $option) {
    $optStmt->execute([
      'question_id' => $questionId,
      'option_text' => trim($option['option_text']),
      'is_correct' => isset($option['is_correct']) && $option['is_correct'] ? 't' : 'f'
    ]);
  }

  // Commit transaction
  $pdo->commit();

  // Log activity
  log_activity($pdo, getCurrentUserId(), getCurrentUserEmail(), 'create_question', "Created question for course ID: $courseId");

  sendOk([
    "message" => "Question created successfully.",
    "question_id" => $questionId
  ]);

} catch (PDOException $e) {
  $pdo->rollBack();
  error_log("Create question failed: " . $e->getMessage());
  sendServerError("Failed to create question.");
}
