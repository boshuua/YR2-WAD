<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';
require_once '../includes/log_function.php';

$type = $_GET['type'] ?? '';
$redirect_url = '/admin/courses?status=deleted';

if ($type === 'one' && isset($_GET['id'])) {
    $course_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    $delete_stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $delete_stmt->execute([$course_id]);
    if ($course) {
        log_activity("Deleted one instance of course: '{$course['title']}' (ID: {$course_id}).");
    }
} elseif ($type === 'series' && isset($_GET['series_id'])) {
    $series_id = $_GET['series_id'];
    $stmt = $pdo->prepare("SELECT title FROM courses WHERE series_id = ? LIMIT 1");
    $stmt->execute([$series_id]);
    $course = $stmt->fetch();

    $delete_stmt = $pdo->prepare("DELETE FROM courses WHERE series_id = ?");
    $delete_stmt->execute([$series_id]);
    if ($course) {
        log_activity("Deleted entire series for course: '{$course['title']}' (Series ID: {$series_id}).");
    }
} else {
    // If parameters are wrong, redirect with an error
    $redirect_url = '/admin/courses?error=deletefailed';
}

header("Location: " . $redirect_url);
exit();