<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

// --- PAGINATION LOGIC FOR UPCOMING COURSES ---
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Fetch IDs for paginated upcoming courses/series
$id_fetch_sql = "
    SELECT id FROM (
        SELECT MIN(id) as id, MIN(course_date) as first_date 
        FROM courses 
        WHERE series_id IS NOT NULL AND id IN (SELECT MIN(id) FROM courses WHERE course_date >= CURDATE() GROUP BY series_id)
        GROUP BY series_id
        UNION ALL
        SELECT id, course_date as first_date
        FROM courses 
        WHERE series_id IS NULL AND course_date >= CURDATE()
    ) as ids
    ORDER BY first_date ASC
    LIMIT :limit OFFSET :offset
";
$id_stmt = $pdo->prepare($id_fetch_sql);
$id_stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$id_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$id_stmt->execute();
$ids_to_fetch = $id_stmt->fetchAll(PDO::FETCH_COLUMN);

$upcoming_courses = [];
if (!empty($ids_to_fetch)) {
    // Fetch full details for the paginated IDs
    $placeholders = implode(',', array_fill(0, count($ids_to_fetch), '?'));
    $details_sql = "
        SELECT c.*, (SELECT COUNT(*) FROM courses c2 WHERE c2.series_id = c.series_id) as recurrence_count
        FROM courses c WHERE c.id IN ($placeholders) ORDER BY c.course_date ASC
    ";
    $details_stmt = $pdo->prepare($details_sql);
    $details_stmt->execute($ids_to_fetch);
    $upcoming_courses = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Count total pages
$total_stmt = $pdo->prepare("SELECT COUNT(DISTINCT series_id) FROM courses WHERE series_id IS NOT NULL AND course_date >= CURDATE()");
$total_stmt->execute();
$total_series = $total_stmt->fetchColumn();
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE series_id IS NULL AND course_date >= CURDATE()");
$total_stmt->execute();
$total_singles = $total_stmt->fetchColumn();
$total_records = $total_series + $total_singles;
$total_pages = ceil($total_records / $records_per_page);

// --- FETCH COURSE HISTORY (NOT PAGINATED) ---
$stmt_history = $pdo->prepare("SELECT * FROM courses WHERE course_date < CURDATE() ORDER BY course_date DESC");
$stmt_history->execute();
$course_history = $stmt_history->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Courses</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Manage Courses</h1></header>
            <div class="app-content">
                <a href="course_add.php" class="btn" style="width: auto; margin-bottom: 20px;">Add New Course</a>
                <div class="card">
                    <h3>Upcoming Courses</h3>
                    <table>
                        <thead><tr><th>Title</th><th>First Occurrence</th><th>Recurrence</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($upcoming_courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($course['course_date'])); ?></td>
                                    <td><?php echo ($course['recurrence_count'] > 1) ? 'Yes (' . $course['recurrence_count'] . ' times)' : 'No'; ?></td>
                                    <td>
                                        <?php if ($course['series_id']): ?><a href="view_series.php?series_id=<?php echo $course['series_id']; ?>">View Series</a> | <?php endif; ?>
                                        <a href="course_edit.php?id=<?php echo $course['id']; ?>">Edit First</a> |
                                        <a href="course_delete.php?id=<?php echo $course['id']; ?>">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <?php if ($total_pages > 1): ?>
                            <?php if ($page > 1): ?><a href="courses.php?page=<?php echo $page - 1; ?>">&laquo; Prev</a><?php endif; ?>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?><a href="courses.php?page=<?php echo $i; ?>" class="<?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?></a><?php endfor; ?>
                            <?php if ($page < $total_pages): ?><a href="courses.php?page=<?php echo $page + 1; ?>">Next &raquo;</a><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" style="margin-top: 30px;">
                    <h3>Course History</h3>
                    <table>
                        <thead><tr><th>Title</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach($course_history as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo date('d M Y, H:i', strtotime($course['course_date'])); ?></td>
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