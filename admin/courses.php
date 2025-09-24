<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

// Fetch Upcoming Courses with enrolled count
$stmt_upcoming = $pdo->prepare(
    "SELECT c.*, COUNT(e.id) AS enrolled_count 
     FROM courses c
     LEFT JOIN enrolments e ON c.id = e.course_id
     WHERE c.course_date >= CURDATE()
     GROUP BY c.id
     ORDER BY c.course_date ASC"
);
$stmt_upcoming->execute();
$upcoming_courses = $stmt_upcoming->fetchAll();

// Fetch Course History with enrolled count
$stmt_history = $pdo->prepare(
    "SELECT c.*, COUNT(e.id) AS enrolled_count 
     FROM courses c
     LEFT JOIN enrolments e ON c.id = e.course_id
     WHERE c.course_date < CURDATE()
     GROUP BY c.id
     ORDER BY c.course_date DESC"
);
$stmt_history->execute();
$course_history = $stmt_history->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; // We'll create this for reusability ?>

        <main class="app-main">
            <header class="app-header">
                <h1>Manage Courses</h1>
                <div class="header-user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                </div>
            </header>
            <div class="app-content">
                <a href="course_add.php" class="btn" style="width: auto; margin-bottom: 20px;">Add New Course</a>
                
                <div class="card">
                    <h3>Upcoming Courses</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Attendees</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo date('d M Y, H:i', strtotime($course['course_date'])); ?></td>
                                <td><?php echo $course['enrolled_count'] . ' / ' . $course['max_attendees']; ?></td>
                                <td>
                                    <a href="course_participants.php?id=<?php echo $course['id']; ?>">View</a> |
                                    <a href="course_edit.php?id=<?php echo $course['id']; ?>">Edit</a> |
                                    <a href="course_delete.php?id=<?php echo $course['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card" style="margin-top: 30px;">
                    <h3>Course History</h3>
                     <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Attendees</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($course_history as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo date('d M Y', strtotime($course['course_date'])); ?></td>
                                <td><?php echo $course['enrolled_count'] . ' / ' . $course['max_attendees']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>