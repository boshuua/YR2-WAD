<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/log_function.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../includes/phpmailer/src/Exception.php';
require '../includes/phpmailer/src/PHPMailer.php';
require '../includes/phpmailer/src/SMTP.php';

if (!isset($_GET['course_id'])) {
    header("Location: /courses?error=missingid");
    exit();
}

$course_id = $_GET['course_id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT c.*, u.first_name as trainer_first_name, u.last_name as trainer_last_name,
    (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id) AS enrolled_count,
    (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id AND user_id = :user_id) AS is_user_enrolled
    FROM courses c 
    LEFT JOIN users u ON c.trainer_id = u.id
    WHERE c.id = :course_id
");
$stmt->execute(['user_id' => $user_id, 'course_id' => $course_id]);
$course = $stmt->fetch();

// Server-side validation
if (!$course || $course['is_user_enrolled'] || $course['enrolled_count'] >= $course['max_attendees'] || new DateTime($course['course_date']) < new DateTime()) {
    header("Location: /courses?error=enrolmentfailed");
    exit();
}

// Process enrolment
try {
    $stmt = $pdo->prepare("INSERT INTO enrolments (user_id, course_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $course_id]);
    log_activity("Enrolled in course: '" . $course['title'] . "'");

    // --- SEND EMAIL ---
    $user_stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();

    // Calculate duration for the email
    $start = new DateTime($course['course_date']);
    $end = new DateTime($course['end_date']);
    $interval = $start->diff($end);
    $duration_formatted = '';
    if ($interval->d > 0) $duration_formatted .= $interval->d . ' days, ';
    if ($interval->h > 0) $duration_formatted .= $interval->h . ' hours, ';
    if ($interval->i > 0) $duration_formatted .= $interval->i . ' minutes';
    $duration_formatted = rtrim($duration_formatted, ', ');

    // Use an output buffer to "render" the email template into a variable
    ob_start();
    include '../includes/email_template.php';
    $email_body = ob_get_clean();

    $mail = new PHPMailer(true);
    // ... (Your SMTP settings go here) ...
    $mail->isSMTP();
    $mail->Host       = 'plesk.remote.ac';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'admin@WS369808-wad.remote.ac';
    $mail->Password   = 'b~686Sxy8';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('admin@WS369808-wad.remote.ac', 'CPD System');
    $mail->addAddress($user['email'], $user['first_name']);
    
    $mail->isHTML(true);
    $mail->Subject = 'Course Enrolment Confirmation: ' . $course['title'];
    $mail->Body    = $email_body;
    
    $mail->send();

    log_email($user['email'], $mail->Subject, $email_body);

} catch (Exception $e) {
    header("Location: /my-courses?error=email_failed");
    exit();
}

// Redirect to "My Courses" page on success
header("Location: /my-courses?status=enrolled");
exit();