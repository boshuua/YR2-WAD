<?php
session_start();
include_once '../config/database.php';
include_once '../helpers/log_helper.php';

// --- Security Check ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["message" => "Access Denied: User not logged in."]);
    exit();
}

// --- Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$data = json_decode(file_get_contents("php://input"));

// --- Validate Input ---
if (!isset($data->question_id) || !is_numeric($data->question_id) || !isset($data->selected_option_id) || !is_numeric($data->selected_option_id)) {
    http_response_code(400);
    echo json_encode(["message" => "Question ID and selected option ID must be provided as numbers."]);
    exit();
}

$userId = $_SESSION['user_id'];
$questionId = (int)$data->question_id;
$selectedOptionId = (int)$data->selected_option_id;

// 1. Check if the selected option is correct
$isCorrect = false;
try {
    $checkOptionQuery = "SELECT is_correct FROM question_options WHERE id = :selected_option_id AND question_id = :question_id";
    $checkOptionStmt = $db->prepare($checkOptionQuery);
    $checkOptionStmt->bindParam(':selected_option_id', $selectedOptionId, PDO::PARAM_INT);
    $checkOptionStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
    $checkOptionStmt->execute();

    if ($checkOptionStmt->rowCount() > 0) {
        $option = $checkOptionStmt->fetch(PDO::FETCH_ASSOC);
        $isCorrect = (bool)$option['is_correct'];
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Selected option not found for this question."]);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(503);
    error_log("Database error checking option correctness: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred."]);
    exit();
}

// 2. Store/Update user's answer
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

    http_response_code(200);
    echo json_encode(["message" => "Answer submitted successfully.", "is_correct" => $isCorrect]);
    log_activity($db, $userId, $_SESSION['user_email'], 'Answer Submitted', "Question ID: {$questionId}, Correct: " . ($isCorrect ? 'Yes' : 'No'));

} catch (PDOException $e) {
    http_response_code(503);
    error_log("Database error submitting answer: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred while submitting answer."]);
    log_activity($db, $userId, $_SESSION['user_email'], 'Answer Submission Failed', "Question ID: {$questionId}, Error: " . $e->getMessage());
}

?>