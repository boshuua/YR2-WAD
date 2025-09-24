<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Check if an ID was passed in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: courses.php');
    exit();
}

$course_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch the details for this specific course
$sql = "SELECT c.*, 
       (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id) AS enrolled_count,
       (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id AND user_id = :user_id) AS is_user_enrolled
FROM courses c WHERE c.id = :course_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id, 'course_id' => $course_id]);
$course = $stmt->fetch();

// If no course was found with that ID, redirect away
if (!$course) {
    header('Location: courses.php');
    exit();
}
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
                        <p><strong>Date & Time:</strong> <?php echo date('l, jS F Y \a\t H:i', strtotime($course['course_date'])); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($course['duration']); ?></p>
                        <p><strong>Spaces Available:</strong> <?php echo $course['enrolled_count'] . ' of ' . $course['max_attendees'] . ' booked'; ?></p>
                        <hr style="border: 0; border-top: 1px solid var(--border-grey); margin: 20px 0;">
                        <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                    </div>
                    <?php
                        $is_full = $course['enrolled_count'] >= $course['max_attendees'];
                        if ($course['is_user_enrolled']) {
                            echo '<button class="btn" disabled>Already Enrolled</button>';
                        } elseif ($is_full) {
                            echo '<button class="btn" disabled>Course Full</button>';
                        } else {
                            echo '<a href="enrol.php?course_id=' . $course['id'] . '" class="btn">Enrol Now</a>';
                        }
                    ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>