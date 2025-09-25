<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: courses.php');
    exit();
}

$course_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$sql = "SELECT c.*, u.first_name, u.last_name,
       (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id) AS enrolled_count,
       (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id AND user_id = :user_id) AS is_user_enrolled
FROM courses c 
LEFT JOIN users u ON c.trainer_id = u.id
WHERE c.id = :course_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id, 'course_id' => $course_id]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: courses.php');
    exit();
}

// --- FIX: Calculate Duration from start and end dates ---
$start = new DateTime($course['course_date']);
$end = new DateTime($course['end_date']);
$interval = $start->diff($end);
// Format the duration nicely. Example: "2 hours, 30 minutes"
$duration_formatted = '';
if ($interval->d > 0) $duration_formatted .= $interval->d . ' days, ';
if ($interval->h > 0) $duration_formatted .= $interval->h . ' hours, ';
if ($interval->i > 0) $duration_formatted .= $interval->i . ' minutes';
$duration_formatted = rtrim($duration_formatted, ', '); // Remove trailing comma and space
// --- End of Fix ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Course Details</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/user_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1><?php echo htmlspecialchars($course['title']); ?></h1></header>
            <div class="app-content">
                <a href="courses.php" style="margin-bottom: 20px; display:inline-block;">&larr; Back to Calendar</a>
                <div class="card">
                    <div class="card-content">
    <p><strong>Start Time:</strong> <?php echo $start->format('l, jS F Y \a\t H:i'); ?></p>
    <p><strong>Duration:</strong> <?php echo $duration_formatted; ?></p>
    
    <?php if ($course['trainer_id']): ?>
        <p><strong>Trainer:</strong> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></p>
    <?php endif; ?>

    <p><strong>Spaces Available:</strong> <?php echo $course['enrolled_count'] . ' of ' . $course['max_attendees'] . ' booked'; ?></p>
    <hr style="border: 0; border-top: 1px solid var(--border-grey); margin: 20px 0;">
    <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
</div>
            </div>
        </main>
    </div>
</body>
</html>