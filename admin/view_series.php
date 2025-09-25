<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

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

// Get the title from the first course for the header
$series_title = !empty($courses_in_series) ? $courses_in_series[0]['title'] : 'Unknown Series';
?>
<!DOCTYPE html>
<html lang="en">
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <div class="app-content">
                <a href="/admin/courses" style="margin-bottom: 20px; display:inline-block;">&larr; Back to Main List</a>
                <div class="card">
                    <h3>All Occurrences</h3>
                    <table>
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