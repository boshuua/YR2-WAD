<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require GET method
requireMethod('GET');

// Require authentication
requireAuth();

// Get user ID from session
$userId = getCurrentUserId();

// Get course ID from query string
$courseId = isset($_GET['course_id']) ? getInt($_GET['course_id']) : 0;

if ($courseId <= 0) {
    sendBadRequest("Course ID is required.");
}

// Get database connection
$database = new Database();
$db = $database->getConn();

// Get course details (only published courses)
$courseQuery = "SELECT id, title, description, code, duration, category, status
                FROM courses
                WHERE id = :course_id AND status = 'published'";
$courseStmt = $db->prepare($courseQuery);
$courseStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
$courseStmt->execute();

if ($courseStmt->rowCount() === 0) {
    sendNotFound("Published course not found.");
}

$course_data = $courseStmt->fetch(PDO::FETCH_ASSOC);
$course_data['lessons'] = [];

// Get lessons for the course
$lessonsQuery = "SELECT id, title, description, order_index
                 FROM lessons
                 WHERE course_id = :course_id
                 ORDER BY order_index ASC";
$lessonsStmt = $db->prepare($lessonsQuery);
$lessonsStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
$lessonsStmt->execute();

while ($lesson = $lessonsStmt->fetch(PDO::FETCH_ASSOC)) {
    $lesson['questions'] = [];

    // Get questions for each lesson
    $questionsQuery = "SELECT id, question_text, question_type
                       FROM questions
                       WHERE lesson_id = :lesson_id
                       ORDER BY id ASC";
    $questionsStmt = $db->prepare($questionsQuery);
    $questionsStmt->bindParam(':lesson_id', $lesson['id'], PDO::PARAM_INT);
    $questionsStmt->execute();

    while ($question = $questionsStmt->fetch(PDO::FETCH_ASSOC)) {
        // Get options for each question
        $optionsQuery = "SELECT id, option_text
                        FROM question_options
                        WHERE question_id = :question_id
                        ORDER BY id ASC";
        $optionsStmt = $db->prepare($optionsQuery);
        $optionsStmt->bindParam(':question_id', $question['id'], PDO::PARAM_INT);
        $optionsStmt->execute();
        $question['options'] = $optionsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get user's previous answer for this question
        $userAnswerQuery = "SELECT selected_option_id
                           FROM user_question_answers
                           WHERE user_id = :user_id AND question_id = :question_id";
        $userAnswerStmt = $db->prepare($userAnswerQuery);
        $userAnswerStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $userAnswerStmt->bindParam(':question_id', $question['id'], PDO::PARAM_INT);
        $userAnswerStmt->execute();

        $userAnswer = $userAnswerStmt->fetch(PDO::FETCH_ASSOC);
        $question['user_selected_option_id'] = $userAnswer ? (int)$userAnswer['selected_option_id'] : null;

        array_push($lesson['questions'], $question);
    }
    array_push($course_data['lessons'], $lesson);
}

sendOk($course_data);
?>