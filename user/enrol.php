<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// --- PHPMailer Setup ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../includes/phpmailer/src/Exception.php';
require '../includes/phpmailer/src/PHPMailer.php';
require '../includes/phpmailer/src/SMTP.php';
// --- End PHPMailer Setup ---

// 1. Basic Validation
if (!isset($_GET['course_id'])) {
    header("Location: courses.php?error=missingid");
    exit();
}
$course_id = $_GET['course_id'];
$user_id = $_SESSION['user_id'];

// 2. Server-side validation (CRITICAL)
$stmt = $pdo->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id) AS enrolled_count,
    (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id AND user_id = ?) AS is_user_enrolled
    FROM courses c WHERE c.id = ?");
$stmt->execute([$user_id, $course_id]);
$course = $stmt->fetch();

if (!$course || $course['is_user_enrolled'] || $course['enrolled_count'] >= $course['max_attendees'] || new DateTime($course['course_date']) < new DateTime()) {
    header("Location: courses.php?error=enrolmentfailed");
    exit(); // Stop if user is already enrolled, course is full, or in the past
}

// 3. All checks passed, process enrolment
try {
    $stmt = $pdo->prepare("INSERT INTO enrolments (user_id, course_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $course_id]);
    
    // 4. Send Email Confirmation
    $user_stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();

    $mail = new PHPMailer(true);
    // -- CONFIGURE YOUR SMTP SETTINGS HERE --
    $mail->isSMTP();
    $mail->Host       = 'plesk.remote.ac'; // Your Plesk SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'admin@WS369808-wad.remote.ac'; // Your email address
    $mail->Password   = 'e2zQ4$8j0';    // Your email password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 465;
    // -- END CONFIGURATION --

    $mail->setFrom('no-reply@yourdomain.co.uk', 'Logical View CPD System');
    $mail->addAddress($user['email'], $user['first_name']);
    $mail->isHTML(true);
    $mail->Subject = 'Course Enrolment Confirmation: ' . $course['title'];
    $mail->Body    = 'Hi ' . $user['first_name'] . ',<br><br>You have successfully enrolled on the course: <b>' . $course['title'] . '</b> on ' . date('d/m/Y', strtotime($course['course_date'])) . '.<br><br>Thank you.';
    $mail->send();

} catch (Exception $e) {
    // Enrolment still succeeded, but email failed. Redirect with a different message.
    header("Location: my_courses.php?status=enrolled_email_failed");
    exit();
}

// Redirect to "My Courses" page on success
header("Location: my_courses.php?status=enrolled");
exit();