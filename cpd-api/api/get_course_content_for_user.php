<?php
session_start();
include_once '../config/database.php';

// --- Security Check ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["message" => "Access Denied: User not logged in."]);
    exit();
}

// --- Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed."]);
    exit();
}

$database = new Database();
$db = $database->getConn();

$userId = $_SESSION['user_id'];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Course ID is required."]);
    exit();
}

$course_data = null;

// 1. Get Course Details
$courseQuery = "SELECT id, title, description, code, duration, category, status FROM courses WHERE id = :course_id AND status = 'published'";
$courseStmt = $db->prepare($courseQuery);
$courseStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
$courseStmt->execute();

if ($courseStmt->rowCount() > 0) {
    $course_data = $courseStmt->fetch(PDO::FETCH_ASSOC);
    $course_data['lessons'] = [];
} else {
    http_response_code(404);
    echo json_encode(["message" => "Published course not found."]);
    exit();
}

// 2. Get Lessons for the Course
$lessonsQuery = "SELECT id, title, description, order_index FROM lessons WHERE course_id = :course_id ORDER BY order_index ASC";
$lessonsStmt = $db->prepare($lessonsQuery);
$lessonsStmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
$lessonsStmt->execute();

while ($lesson = $lessonsStmt->fetch(PDO::FETCH_ASSOC)) {
    $lesson['questions'] = [];

    // 3. Get Questions for each Lesson
    $questionsQuery = "SELECT id, question_text, question_type FROM questions WHERE lesson_id = :lesson_id ORDER BY id ASC";
    $questionsStmt = $db->prepare($questionsQuery);
    $questionsStmt->bindParam(':lesson_id', $lesson['id'], PDO::PARAM_INT);
    $questionsStmt->execute();

    while ($question = $questionsStmt->fetch(PDO::FETCH_ASSOC)) {
        $question['options'] = [];

        // 4. Get Options for each Question
        $optionsQuery = "SELECT id, option_text FROM question_options WHERE question_id = :question_id ORDER BY id ASC";
        $optionsStmt = $db->prepare($optionsQuery);
        $optionsStmt->bindParam(':question_id', $question['id'], PDO::PARAM_INT);
        $optionsStmt->execute();
        $options = $optionsStmt->fetchAll(PDO::FETCH_ASSOC);
        $question['options'] = is_array($options) ? $options : [];

        // 5. Get User's Previous Answer for this Question (if any)
        $userAnswerQuery = "SELECT selected_option_id FROM user_question_answers WHERE user_id = :user_id AND question_id = :question_id";
        $userAnswerStmt = $db->prepare($userAnswerQuery);
        $userAnswerStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $userAnswerStmt->bindParam(':question_id', $question['id'], PDO::PARAM_INT);
        $userAnswerStmt->execute();
        $userAnswer = $userAnswerStmt->fetch(PDO::FETCH_ASSOC);
        $question['user_selected_option_id'] = null;
        if (is_array($userAnswer) && isset($userAnswer['selected_option_id'])) {
            $question['user_selected_option_id'] = (int)$userAnswer['selected_option_id'];
        }

        array_push($lesson['questions'], $question);
    }
    array_push($course_data['lessons'], $lesson);
}

http_response_code(200);
echo json_encode($course_data);

?>