<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

// --- PAGINATION LOGIC ---
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// First, count the total number of upcoming courses to calculate total pages
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE course_date >= CURDATE()");
$total_stmt->execute();
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);
// --- END PAGINATION LOGIC ---


// Fetch Upcoming Courses for the current page
$stmt_upcoming = $pdo->prepare(
    "SELECT c.*, COUNT(e.id) AS enrolled_count 
     FROM courses c
     LEFT JOIN enrolments e ON c.id = e.course_id
     WHERE c.course_date >= CURDATE()
     GROUP BY c.id
     ORDER BY c.course_date ASC
     LIMIT :limit OFFSET :offset"
);
// Bind values to the prepared statement
$stmt_upcoming->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt_upcoming->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_upcoming->execute();
$upcoming_courses = $stmt_upcoming->fetchAll();

// Fetch Course History (we won't paginate this for now, but you could apply the same logic)
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
                                <th>Date</th>
                                <th>Attendees</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($upcoming_courses)): ?>
                                <tr><td colspan="4">No upcoming courses found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($upcoming_courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($course['course_date'])); ?></td>
                                    <td><?php echo $course['enrolled_count'] . ' / ' . $course['max_attendees']; ?></td>
                                    <td>
                                        <a href="course_participants.php?id=<?php echo $course['id']; ?>">View</a> |
                                        <a href="course_edit.php?id=<?php echo $course['id']; ?>">Edit</a> |
                                        <a href="course_delete.php?id=<?php echo $course['id']; ?>">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="courses.php?page=<?php echo $page - 1; ?>">&laquo; Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="courses.php?page=<?php echo $i; ?>" class="<?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="courses.php?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" style="margin-top: 30px;">
                    <h3>Course History</h3>
                     <table>
                        <tbody>
                             <?php if (empty($course_history)): ?>
                                <tr><td colspan="3">No past courses found.</td></tr>
                             <?php else: ?>
                                <?php foreach ($course_history as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($course['course_date'])); ?></td>
                                    <td><?php echo $course['enrolled_count'] . ' / ' . $course['max_attendees']; ?></td>
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