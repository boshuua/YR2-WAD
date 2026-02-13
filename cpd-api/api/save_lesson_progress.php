<?php
// cpd-api/api/save_lesson_progress.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/csrf.php';
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

requireAuth();

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$courseId = $input['course_id'] ?? null;
$lessonId = $input['lesson_id'] ?? null;

if (!$courseId || !$lessonId) {
    http_response_code(400);
    echo json_encode(['error' => 'Course ID and Lesson ID are required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConn();

    // Clear cache for this user's dashboard
    $cacheFile = sys_get_temp_dir() . "/dashboard_user_{$userId}.json";
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }

    // 1. Update or create user_course_progress (set to 'in_progress')
    $stmt = $db->prepare("
        INSERT INTO user_course_progress (user_id, course_id, status, enrolled_at, updated_at)
        VALUES (:uid, :cid, 'in_progress', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE 
            status = IF(status = 'completed', 'completed', 'in_progress'),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([':uid' => $userId, ':cid' => $courseId]);

    // 2. Mark lesson as completed in user_lesson_progress
    $stmt = $db->prepare("
        INSERT INTO user_lesson_progress (user_id, lesson_id, status, completed_at)
        VALUES (:uid, :lid, 'completed', CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE 
            status = 'completed',
            completed_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([':uid' => $userId, ':lid' => $lessonId]);

    // 3. Get updated progress for this course
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT l.id) as total_lessons,
            COUNT(DISTINCT CASE WHEN ulp.status = 'completed' THEN ulp.lesson_id END) as completed_lessons
        FROM lessons l
        LEFT JOIN user_lesson_progress ulp ON l.id = ulp.lesson_id AND ulp.user_id = :uid
        WHERE l.course_id = :cid
    ");
    $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Progress saved',
        'progress' => [
            'total_lessons' => (int) $progress['total_lessons'],
            'completed_lessons' => (int) $progress['completed_lessons'],
            'percentage' => $progress['total_lessons'] > 0
                ? round(($progress['completed_lessons'] / $progress['total_lessons']) * 100)
                : 0
        ]
    ]);

} catch (Exception $e) {
    error_log("Save lesson progress error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save progress']);
}
