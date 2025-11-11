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

// Validate numeric fields
if (!isset($data->question_id) || !is_numeric($data->question_id) ||
    !isset($data->selected_option_id) || !is_numeric($data->selected_option_id)) {
    sendBadRequest("Question ID and selected option ID must be provided as numbers.");
}

$questionId = getInt($data->question_id);
$selectedOptionId = getInt($data->selected_option_id);

// Get database connection
$database = new Database();
$db = $database->getConn();

// Check if the selected option is correct
try {
    $checkOptionQuery = "SELECT is_correct FROM question_options WHERE id = :selected_option_id AND question_id = :question_id";
    $checkOptionStmt = $db->prepare($checkOptionQuery);
    $checkOptionStmt->bindParam(':selected_option_id', $selectedOptionId, PDO::PARAM_INT);
    $checkOptionStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
    $checkOptionStmt->execute();

    if ($checkOptionStmt->rowCount() === 0) {
        sendNotFound("Selected option not found for this question.");
    }

    $option = $checkOptionStmt->fetch(PDO::FETCH_ASSOC);
    $isCorrect = (bool)$option['is_correct'];
} catch (PDOException $e) {
    error_log("Database error checking option correctness: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred.");
}

// Store or update user's answer
try {
    // Check if user has already answered this question
    $existingAnswerQuery = "SELECT id FROM user_question_answers WHERE user_id = :user_id AND question_id = :question_id";
    $existingAnswerStmt = $db->prepare($existingAnswerQuery);
    $existingAnswerStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $existingAnswerStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
    $existingAnswerStmt->execute();

    if ($existingAnswerStmt->rowCount() > 0) {
        // Update existing answer
        $updateAnswerQuery = "UPDATE user_question_answers
                              SET selected_option_id = :selected_option_id,
                                  is_correct = :is_correct,
                                  answered_at = CURRENT_TIMESTAMP
                              WHERE user_id = :user_id AND question_id = :question_id";
        $updateAnswerStmt = $db->prepare($updateAnswerQuery);
        $updateAnswerStmt->bindParam(':selected_option_id', $selectedOptionId, PDO::PARAM_INT);
        $updateAnswerStmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);
        $updateAnswerStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $updateAnswerStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        $updateAnswerStmt->execute();
    } else {
        // Insert new answer
        $insertAnswerQuery = "INSERT INTO user_question_answers (
                                user_id,
                                question_id,
                                selected_option_id,
                                is_correct
                              ) VALUES (
                                :user_id,
                                :question_id,
                                :selected_option_id,
                                :is_correct
                              )";
        $insertAnswerStmt = $db->prepare($insertAnswerQuery);
        $insertAnswerStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $insertAnswerStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        $insertAnswerStmt->bindParam(':selected_option_id', $selectedOptionId, PDO::PARAM_INT);
        $insertAnswerStmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);
        $insertAnswerStmt->execute();
    }

    log_activity($db, $userId, getCurrentUserEmail(), 'Answer Submitted', "Question ID: {$questionId}, Correct: " . ($isCorrect ? 'Yes' : 'No'));
    sendOk(["message" => "Answer submitted successfully.", "is_correct" => $isCorrect]);
} catch (PDOException $e) {
    error_log("Database error submitting answer: " . $e->getMessage());
    log_activity($db, $userId, getCurrentUserEmail(), 'Answer Submission Failed', "Question ID: {$questionId}, Error: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred while submitting answer.");
}
?>