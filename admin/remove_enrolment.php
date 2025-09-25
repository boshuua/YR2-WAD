<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';
require_once '../includes/log_function.php';

// Check if an enrolment ID was passed
if (!isset($_GET['enrolment_id']) || !is_numeric($_GET['enrolment_id'])) {
    header("Location: /admin/courses?error=removefailed");
    exit();
}

$enrolment_id = $_GET['enrolment_id'];
$course_id = $_GET['course_id'] ?? null; // For redirecting back

// Fetch details for logging before deleting
$stmt_log = $pdo->prepare(
    "SELECT u.first_name, u.last_name, c.title
     FROM enrolments e
     JOIN users u ON e.user_id = u.id
     JOIN courses c ON e.course_id = c.id
     WHERE e.id = ?"
);
$stmt_log->execute([$enrolment_id]);
$log_info = $stmt_log->fetch();

// Proceed with deletion
$stmt = $pdo->prepare("DELETE FROM enrolments WHERE id = ?");
$stmt->execute([$enrolment_id]);

if ($log_info) {
    $user_name = "{$log_info['first_name']} {$log_info['last_name']}";
    log_activity("Admin removed user '{$user_name}' from course '{$log_info['title']}'.");
}

// Redirect back to the enrolments page with a success message
$redirect_url = $course_id ? "/admin/enrolments/{$course_id}?status=deleted" : "/admin/courses?status=deleted";
header("Location: " . $redirect_url);
exit();