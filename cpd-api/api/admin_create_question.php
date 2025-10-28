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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$data = json_decode(file_get_contents("php://input"));

// Basic validation
if (empty($data->lesson_id) || empty($data->question_text) || empty($data->options) || !is_array($data->options)) {
    http_response_code(400);
    echo json_encode(["message" => "Unable to create question. Lesson ID, question text, and options are required."]);
    log_activity($db, null, null, "Question Creation Failed", "Incomplete data provided.");
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
    echo json_encode(["message" => "Unable to create question. At least one option must be marked as correct."]);
    log_activity($db, null, null, "Question Creation Failed", "No correct option provided.");
    exit();
}

try {
    $db->beginTransaction();

    // Insert Question
    $questionQuery = "INSERT INTO questions (
                        lesson_id, 
                        question_text, 
                        question_type
                      ) VALUES (
                        :lesson_id, 
                        :question_text, 
                        :question_type
                      )";

    $questionStmt = $db->prepare($questionQuery);

    $lesson_id = (int)$data->lesson_id;
    $question_text = htmlspecialchars(strip_tags($data->question_text));
    $question_type = isset($data->question_type) ? htmlspecialchars(strip_tags($data->question_type)) : 'multiple_choice';

    $questionStmt->bindParam(':lesson_id', $lesson_id, PDO::PARAM_INT);
    $questionStmt->bindParam(':question_text', $question_text);
    $questionStmt->bindParam(':question_type', $question_type);

    $questionStmt->execute();
    $question_id = $db->lastInsertId();

    // Insert Options
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

        $optionStmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
        $optionStmt->bindParam(':option_text', $option_text);
        $optionStmt->bindParam(':is_correct', $is_correct, PDO::PARAM_BOOL);
        $optionStmt->execute();
    }

    $db->commit();

    http_response_code(201);
    echo json_encode(["message" => "Question was created.", "id" => $question_id]);
    log_activity($db, null, null, "Question Created", "Question ID: {$question_id} for Lesson ID: {$lesson_id}");

} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(500);
    error_log("Database error during question creation: " . $e->getMessage());
    echo json_encode(["message" => "Unable to create question."]);
    log_activity($db, null, null, "Question Creation Failed", "Lesson ID: {$lesson_id}, Error: " . $e->getMessage());
}

?>