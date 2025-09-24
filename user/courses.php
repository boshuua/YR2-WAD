<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

// --- PAGINATION LOGIC ---
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// --- QUERY TO COUNT TOTAL UNIQUE UPCOMING COURSES AND SERIES ---
$total_sql = "
    SELECT COUNT(*) FROM (
        SELECT series_id FROM courses WHERE series_id IS NOT NULL AND course_date >= CURDATE() GROUP BY series_id
        UNION ALL
        SELECT id FROM courses WHERE series_id IS NULL AND course_date >= CURDATE()
    ) AS T
";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute();
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);
// --- END COUNT QUERY ---


// --- MAIN QUERY TO FETCH GROUPED COURSES FOR THE CURRENT PAGE ---
$sql = "
    SELECT * FROM (
        -- Select the first instance of each recurring series
        SELECT 
            MIN(id) as id, 
            series_id, 
            title, 
            MIN(course_date) as course_date, 
            max_attendees, 
            description, 
            COUNT(id) as recurrence_count
        FROM courses 
        WHERE series_id IS NOT NULL AND course_date >= CURDATE()
        GROUP BY series_id, title, max_attendees, description

        UNION ALL

        -- Select all non-recurring courses
        SELECT 
            id, 
            NULL as series_id, 
            title, 
            course_date, 
            max_attendees, 
            description, 
            1 as recurrence_count
        FROM courses 
        WHERE series_id IS NULL AND course_date >= CURDATE()
    ) AS upcoming_courses
    ORDER BY course_date ASC
    LIMIT :limit OFFSET :offset
";

$stmt_upcoming = $pdo->prepare($sql);
$stmt_upcoming->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt_upcoming->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_upcoming->execute();
$upcoming_courses = $stmt_upcoming->fetchAll(PDO::FETCH_ASSOC);
// --- END MAIN QUERY ---

// Fetch Course History (remains unchanged)
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
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>First Occurrence</th>
                                <th>Recurrence</th>
                                <th>Total Enrolled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_courses as $course): ?>
                                <?php
                                // Get total enrolled count for the entire series or single course
                                if ($course['series_id']) {
                                    $enrol_stmt = $pdo->prepare("SELECT COUNT(*) FROM enrolments WHERE course_id IN (SELECT id FROM courses WHERE series_id = ?)");
                                    $enrol_stmt->execute([$course['series_id']]);
                                } else {
                                    $enrol_stmt = $pdo->prepare("SELECT COUNT(*) FROM enrolments WHERE course_id = ?");
                                    $enrol_stmt->execute([$course['id']]);
                                }
                                $total_enrolled = $enrol_stmt->fetchColumn();
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($course['course_date'])); ?></td>
                                    <td>
                                        <?php if ($course['recurrence_count'] > 1): ?>
                                            Yes (<?php echo $course['recurrence_count']; ?> times)
                                        <?php else: ?>
                                            No
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $total_enrolled; ?> / <?php echo ($course['max_attendees'] * $course['recurrence_count']); ?></td>
                                    <td>
                                        <a href="course_edit.php?id=<?php echo $course['id']; ?>">Edit</a> |
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
                        </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>