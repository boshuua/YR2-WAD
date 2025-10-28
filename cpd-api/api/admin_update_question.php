<?php
session_start();
include_once '../config/database.php';
include_once '../helpers/log_helper.php';

// --- Security Check ---
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

// --- Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed. Use PUT."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$questionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = json_decode(file_get_contents("php://input"));

// Basic validation
if ($questionId <= 0 || empty($data->lesson_id) || empty($data->question_text) || empty($data->options) || !is_array($data->options)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update question. Question ID, lesson ID, question text, and options are required."]);
    log_activity($db, null, null, "Question Update Failed", "Incomplete data provided for Question ID: {$questionId}.");
    exit();
}

// Check if at least one option is marked as correct
$hasCorrectOption = false;
foreach ($data->options as $option) {
    if (isset($option->is_correct) && $option->is_correct) {
        $hasCorrectOption = true;
        break;
    }
}

if (!$hasCorrectOption) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to update question. At least one option must be marked as correct."]);
    log_activity($db, null, null, "Question Update Failed", "No correct option provided for Question ID: {$questionId}.");
    exit();
}

try {
    $db->beginTransaction();

    // 1. Update Question
    $questionQuery = "UPDATE questions
                      SET lesson_id = :lesson_id,
                          question_text = :question_text,
                          question_type = :question_type,
                          updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";

    $questionStmt = $db->prepare($questionQuery);

    $lesson_id = (int)$data->lesson_id;
    $question_text = htmlspecialchars(strip_tags($data->question_text));
    $question_type = isset($data->question_type) ? htmlspecialchars(strip_tags($data->question_type)) : 'multiple_choice';

    $questionStmt->bindParam(':lesson_id', $lesson_id, PDO::PARAM_INT);
    $questionStmt->bindParam(':question_text', $question_text);
    $questionStmt->bindParam(':question_type', $question_type);
    $questionStmt->bindParam(':id', $questionId, PDO::PARAM_INT);
    $questionStmt->execute();

    // 2. Delete existing options for this question
    $deleteOptionsQuery = "DELETE FROM question_options WHERE question_id = :question_id";
    $deleteOptionsStmt = $db->prepare($deleteOptionsQuery);
    $deleteOptionsStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
    $deleteOptionsStmt->execute();

    // 3. Insert new/updated options
    $optionQuery = "INSERT INTO question_options (
                       question_id, 
                       option_text, 
                       is_correct
                     ) VALUES (
                       :question_id, 
                       :option_text, 
                       :is_correct
                     )";
    $optionStmt = $db->prepare($optionQuery);

    foreach ($data->options as $option) {
        $option_text = htmlspecialchars(strip_tags($option->option_text));
        $is_correct = (bool)$option->is_correct;

        $optionStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        $optionStmt->bindParam(':option_text', $option_text);
        $optionStmt->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);
        $optionStmt->execute();
    }

    $db->commit();

    http_response_code(200);
    echo json_encode(["message" => "Question updated successfully.", "id" => $questionId]);
    log_activity($db, null, null, "Question Updated", "Question ID: {$questionId} for Lesson ID: {$lesson_id}");

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    error_log("Database error during question update: " . $e->getMessage());
    echo json_encode(["message" => "Unable to update question."]);
    log_activity($db, null, null, "Question Update Failed", "Question ID: {$questionId}, Error: " . $e->getMessage());
}

?>