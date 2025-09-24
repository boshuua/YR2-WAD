<?php

require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

$stmt_upcoming = $pdo->prepare("SELECT * FROM courses WHERE course_date >= CURDATE() ORDER BY course_date ASC");
$stmt_upcoming->execute();
$upcoming_courses = $stmt_upcoming->fetchAll(PDO::FETCH_ASSOC);


$stmt_history = $pdo->prepare("SELECT * FROM courses WHERE course_date < CURDATE() ORDER BY course_date DESC");
$stmt_history->execute();
$history_courses = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Admin: Manage Courses</h1>
        <nav><a href="dashboard.php">Dashboard</a> | <a href="./logout.php">Logout</a></nav>
    </header>
    <main>
        <h2>Upcoming Courses</h2>
        <a href="course_add.php">Add New Course</a>
        <table>
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Course Date</th>
                    <th>Attendees</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcoming_courses as $course): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($course['course_date'])); ?></td>
                        <td></td>
                        <td>
                            <a href="course_participants.php?id=<?php echo $course['id']; ?>">View Participants</a>
                            <a href="course_edit.php?id=<?php echo $course['id']; ?>">Edit</a>
                            <a href="course_delete.php?id=<?php echo $course['id']; ?>" onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Course History</h2>
        <table>
            
        </table>
    </main>
</body>
</html>