<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require admin authentication
requireAdmin();

// Get question ID from query parameter
$questionId = $_GET['id'] ?? null;

if (!$questionId || !is_numeric($questionId)) {
  sendBadRequest("Question ID is required and must be numeric.");
}

// Get database connection
$database = new Database();
$pdo = $database->getConn();

try {
  // Start transaction
  $pdo->beginTransaction();

  // Delete options first (foreign key constraint)
  $stmt = $pdo->prepare("DELETE FROM question_options WHERE question_id = :question_id");
  $stmt->execute(['question_id' => $questionId]);

  // Delete question
  $stmt = $pdo->prepare("DELETE FROM questions WHERE id = :id");
  $stmt->execute(['id' => $questionId]);

  $rowsAffected = $stmt->rowCount();

  if ($rowsAffected === 0) {
    $pdo->rollBack();
    sendNotFound("Question not found.");
  }

  // Commit transaction
  $pdo->commit();

  // Log activity
  log_activity($pdo, getCurrentUserId(), getCurrentUserEmail(), 'delete_question', "Deleted question ID: $questionId");

  sendOk(["message" => "Question deleted successfully."]);

} catch (PDOException $e) {
  $pdo->rollBack();
  error_log("Delete question failed: " . $e->getMessage());
  sendServerError("Failed to delete question.");
}
