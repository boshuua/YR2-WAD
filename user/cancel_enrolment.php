<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/log_function.php'; 

if(!isset($_GET['enrolment_id'])) {
    header("Location: my_courses.php");
    exit();
}

$enrolment_id = $_GET['enrolment_id'];
$user_id = $_SESSION['user_id'];

// --- FETCH COURSE DETAILS FOR LOGGING ---
$stmt_log = $pdo->prepare(
    "SELECT c.title, c.id FROM courses c 
     JOIN enrolments e ON c.id = e.course_id 
     WHERE e.id = ? AND e.user_id = ?"
);
$stmt_log->execute([$enrolment_id, $user_id]);
$course_info = $stmt_log->fetch();
// --- END FETCH ---

// Securely delete the enrolment
$stmt_delete = $pdo->prepare("DELETE FROM enrolments WHERE id = ? AND user_id = ?");
$stmt_delete->execute([$enrolment_id, $user_id]);

// If we found the course info, log the cancellation
if ($course_info) {
    log_activity("Cancelled enrolment for course: '" . $course_info['title'] . "' (ID: " . $course_info['id'] . ")");
}

header("Location: my_courses.php?status=cancelled");
exit();