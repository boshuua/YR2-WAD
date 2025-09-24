<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
$user_id = $_SESSION['user_id'];

// SQL to get all future courses, their enrolled count, and whether the current user is already enrolled.
$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id) AS enrolled_count,
               (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id AND user_id = :user_id) AS is_user_enrolled
        FROM courses c
        WHERE c.course_date >= CURDATE()
        ORDER BY c.course_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Available Courses</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/user_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Available Courses</h1></header>
            <div class="app-content">
                <div class="dashboard-grid">
                    <?php foreach ($courses as $course): ?>
                    <div class="card">
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p><strong>Date:</strong> <?php echo date('d M Y, H:i', strtotime($course['course_date'])); ?></p>
                            <p><strong>Duration:</strong> <?php echo htmlspecialchars($course['duration']); ?></p>
                            <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                            <p><strong>Spaces:</strong> <?php echo $course['enrolled_count'] . ' of ' . $course['max_attendees'] . ' booked'; ?></p>
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
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>