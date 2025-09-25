<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Ensure the user is either a trainer or an admin to view this page
if ($_SESSION['access_level'] !== 'trainer' && $_SESSION['access_level'] !== 'admin') {
    header("Location: /dashboard"); // Redirect regular users
    exit();
}

$trainer_id = $_SESSION['user_id'];

// Fetch upcoming courses assigned to this trainer
$stmt = $pdo->prepare(
    "SELECT c.title, c.course_date, (SELECT COUNT(*) FROM enrolments WHERE course_id = c.id) as enrolled_count 
     FROM courses c
     WHERE c.trainer_id = ? AND c.course_date >= CURDATE() 
     ORDER BY c.course_date ASC"
);
$stmt->execute([$trainer_id]);
$upcoming_courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Trainer Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="<?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') echo 'dark-mode'; ?>">
    <div class="app-container">
        <?php include '../includes/trainer_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header">
                <h1>Trainer Dashboard</h1>
                <div class="header-user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                </div>
            </header>
            <div class="app-content">
                <div class="card">
                    <h3>My Upcoming Assigned Courses</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Title</th>
                                <th>Date</th>
                                <th>Enrolled Attendees</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($upcoming_courses)): ?>
                                <tr><td colspan="3">You have no upcoming courses assigned to you.</td></tr>
                            <?php else: ?>
                                <?php foreach($upcoming_courses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                                        <td><?php echo date('d M Y, H:i', strtotime($course['course_date'])); ?></td>
                                        <td><?php echo $course['enrolled_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>