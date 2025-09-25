<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';
require_once '../includes/breadcrumb.php';

if (!isset($_GET['series_id'])) {
    header("Location: /admin/courses");
    exit();
}

$series_id = $_GET['series_id'];

// Fetch all courses belonging to this series
$stmt = $pdo->prepare(
    "SELECT c.*, COUNT(e.id) AS enrolled_count
     FROM courses c
     LEFT JOIN enrolments e ON c.id = e.course_id
     WHERE c.series_id = ?
     GROUP BY c.id
     ORDER BY c.course_date ASC"
);
$stmt->execute([$series_id]);
$courses_in_series = $stmt->fetchAll();

$series_title = !empty($courses_in_series) ? $courses_in_series[0]['title'] : 'Unknown Series';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Course Series</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Course Series</h1></header>
            <div class="app-content">
                <?php display_breadcrumbs([
                    '/admin/dashboard' => 'Dashboard',
                    '/admin/courses' => 'Manage Courses',
                    '#' => htmlspecialchars($series_title)
                ]); ?>
                <div class="card">
                    <h3>All Occurrences</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Attendees</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses_in_series as $course): ?>
                                <tr>
                                    <td><?php echo date('d M Y, H:i', strtotime($course['course_date'])); ?></td>
                                    <td><?php echo $course['enrolled_count'] . ' / ' . $course['max_attendees']; ?></td>
                                    <td>
                                        <a href="/admin/enrolments/<?php echo $course['id']; ?>?series_id=<?php echo $series_id; ?>">View Enrolments</a> |
                                        <a href="/admin/course_edit.php?id=<?php echo $course['id']; ?>">Edit</a> |
                                        <a href="#" class="open-delete-modal"
                                            data-title="<?php echo htmlspecialchars($course['title']); ?>"
                                            data-url-one="/admin/course_delete_process.php?id=<?php echo $course['id']; ?>&type=one"
                                            data-url-series="/admin/course_delete_process.php?series_id=<?php echo $course['series_id']; ?>&type=series">Delete</a>
                                    </td>
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