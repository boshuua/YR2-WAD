<?php
session_start();
include_once '../config/database.php';

// --- Security Check ---
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

// --- Get Input Data ---
$lessonId = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
$questionId = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Optional: to get a single question

// --- Validate Input ---
if ($lessonId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Lesson ID is required."]);
    exit();
}

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

$questions_arr = array();

if ($questionId > 0) {
    // Fetch a single question by ID
    $questionQuery = "SELECT id, lesson_id, question_text, question_type FROM questions WHERE id = :id AND lesson_id = :lesson_id LIMIT 1 OFFSET 0";
    $questionStmt = $db->prepare($questionQuery);
    $questionStmt->bindParam(':id', $questionId, PDO::PARAM_INT);
    $questionStmt->bindParam(':lesson_id', $lessonId, PDO::PARAM_INT);
    $questionStmt->execute();

    if ($questionStmt->rowCount() > 0) {
        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);

        // Fetch options for this question
        $optionsQuery = "SELECT id, option_text, is_correct FROM question_options WHERE question_id = :question_id ORDER BY id ASC";
        $optionsStmt = $db->prepare($optionsQuery);
        $optionsStmt->bindParam(':question_id', $question['id'], PDO::PARAM_INT);
        $optionsStmt->execute();
        $question['options'] = $optionsStmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($question);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Question not found for this lesson."]);
    }

} else {
    // Fetch all questions for a specific lesson
    $questionQuery = "SELECT id, lesson_id, question_text, question_type FROM questions WHERE lesson_id = :lesson_id ORDER BY id ASC";
    $questionStmt = $db->prepare($questionQuery);
    $questionStmt->bindParam(':lesson_id', $lessonId, PDO::PARAM_INT);
    $questionStmt->execute();

    if ($questionStmt->rowCount() > 0) {
        while ($question = $questionStmt->fetch(PDO::FETCH_ASSOC)) {
            // Fetch options for each question
            $optionsQuery = "SELECT id, option_text, is_correct FROM question_options WHERE question_id = :question_id ORDER BY id ASC";
            $optionsStmt = $db->prepare($optionsQuery);
            $optionsStmt->bindParam(':question_id', $question['id'], PDO::PARAM_INT);
            $optionsStmt->execute();
            $question['options'] = $optionsStmt->fetchAll(PDO::FETCH_ASSOC);
            array_push($questions_arr, $question);
        }
        http_response_code(200);
        echo json_encode($questions_arr);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "No questions found for this lesson."]);
    }
}

?>