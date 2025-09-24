<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

if(!isset($_GET['enrolment_id'])) {
    header("Location: my_courses.php");
    exit();
}

$enrolment_id = $_GET['enrolment_id'];
$user_id = $_SESSION['user_id'];

// By selecting the enrolment ID AND the user ID, we ensure users can only delete their OWN enrolments.
$stmt = $pdo->prepare("DELETE FROM enrolments WHERE id = ? AND user_id = ?");
$stmt->execute([$enrolment_id, $user_id]);

header("Location: my_courses.php?status=cancelled");
exit();