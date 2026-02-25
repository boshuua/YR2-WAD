<?php
// cpd-api/api/index.php

require_once __DIR__ . '/bootstrap.php';

// Parse the requested URI
$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'];

// Extract the endpoint name (e.g., "get_users.php" from "/api/get_users.php")
$endpoint = basename($path);

// Define the route map
// Mapping legacy procedural files to their respective Controllers and Methods
$routes = [
    // Auth
    'user_login.php' => ['App\Controllers\AuthController', 'login'],
    'me.php' => ['App\Controllers\AuthController', 'me'],
    'csrf.php' => ['App\Controllers\AuthController', 'csrf'],
    'forgot_password.php' => ['App\Controllers\AuthController', 'forgotPassword'],
    'approve_reset.php' => ['App\Controllers\AuthController', 'approveReset'],

    // Users
    'get_users.php' => ['App\Controllers\UserController', 'index'],
    'admin_create_user.php' => ['App\Controllers\UserController', 'create'],
    'admin_update_user.php' => ['App\Controllers\UserController', 'update'],
    'admin_delete_user.php' => ['App\Controllers\UserController', 'delete'],
    'admin_update_password.php' => ['App\Controllers\UserController', 'updatePassword'],

    // Courses (Admin/Global)
    'get_courses.php' => ['App\Controllers\CourseController', 'index'],
    'get_course_by_id.php' => ['App\Controllers\CourseController', 'show'],
    'admin_create_course.php' => ['App\Controllers\CourseController', 'create'],
    'admin_create_course_from_template.php' => ['App\Controllers\CourseController', 'createFromTemplate'],
    'admin_update_course.php' => ['App\Controllers\CourseController', 'update'],
    'admin_delete_course.php' => ['App\Controllers\CourseController', 'delete'],
    'assign_course.php' => ['App\Controllers\CourseController', 'assign'],

    // User Course Progress (Enrolment)
    'get_user_courses.php' => ['App\Controllers\CourseController', 'userCourses'],
    'enroll_course.php' => ['App\Controllers\CourseController', 'enroll'],
    'update_course_progress.php' => ['App\Controllers\CourseController', 'updateProgress'],
    'complete_course.php' => ['App\Controllers\CourseController', 'completeCourse'],

    // Lessons/Progress
    'get_course_lessons.php' => ['App\Controllers\LessonController', 'index'],
    'update_lesson_progress.php' => ['App\Controllers\LessonController', 'updateProgress'],
    'save_lesson_progress.php' => ['App\Controllers\LessonController', 'saveProgress'],

    // Questions/Quizzes
    'get_course_questions.php' => ['App\Controllers\QuestionController', 'index'],
    'admin_create_question.php' => ['App\Controllers\QuestionController', 'create'],
    'admin_delete_question.php' => ['App\Controllers\QuestionController', 'delete'],
    'submit_quiz.php' => ['App\Controllers\QuestionController', 'submitQuiz'],

    // Dashboard & Activity
    'get_user_dashboard.php' => ['App\Controllers\DashboardController', 'index'],
    'get_activity_log.php' => ['App\Controllers\ActivityController', 'index'],

    // Attachments
    'upload_user_attachment.php' => ['App\Controllers\AttachmentController', 'upload'],
    'delete_user_attachment.php' => ['App\Controllers\AttachmentController', 'delete'],
    'view_attachment.php' => ['App\Controllers\AttachmentController', 'view']
];

// If the endpoint is empty or is exactly "index.php", return a standard status
if (empty($endpoint) || $endpoint === 'index.php') {
    http_response_code(200);
    echo json_encode(["status" => "CPD API is running", "version" => "2.0"]);
    exit;
}

// Check if the route exists
if (array_key_exists($endpoint, $routes)) {
    $controllerClass = $routes[$endpoint][0];
    $method = $routes[$endpoint][1];

    if (class_exists($controllerClass) && method_exists($controllerClass, $method)) {
        // Instantiate the controller and call the method
        $controller = new $controllerClass();
        $controller->$method();
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Internal Server Error: Implementation missing for $endpoint"]);
    }
} else {
    // Legacy fallback or pure 404
    http_response_code(404);
    echo json_encode(["message" => "Endpoint not found: $endpoint"]);
}
