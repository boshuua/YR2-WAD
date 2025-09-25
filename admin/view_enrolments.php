<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';
require_once '../includes/breadcrumb.php';

if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header("Location: /admin/courses");
    exit();
}

$course_id = $_GET['course_id'];
$series_id_for_back_link = $_GET['series_id'] ?? null;

// Fetch course details
$course_stmt = $pdo->prepare("SELECT title, course_date, series_id FROM courses WHERE id = ?");
$course_stmt->execute([$course_id]);
$course = $course_stmt->fetch();

if (!$course) {
    header("Location: /admin/courses");
    exit();
}

// If series_id wasn't in the URL, try to get it from the course itself
if (!$series_id_for_back_link) {
    $series_id_for_back_link = $course['series_id'];
}

// Fetch enrolled users
$enrolment_stmt = $pdo->prepare(
    "SELECT u.id as user_id, u.first_name, u.last_name, u.email, e.id as enrolment_id
     FROM users u JOIN enrolments e ON u.id = e.user_id WHERE e.course_id = ?"
);
$enrolment_stmt->execute([$course_id]);
$enrolled_users = $enrolment_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Enrolments</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>View Enrolments</h1></header>
            <div class="app-content">
                <?php 
                $breadcrumbs = [
                    '/admin/dashboard' => 'Dashboard',
                    '/admin/courses' => 'Manage Courses'
                ];
                if ($series_id_for_back_link) {
                    $breadcrumbs['/admin/view_series.php?series_id=' . htmlspecialchars($series_id_for_back_link)] = htmlspecialchars($course['title']);
                }
                $breadcrumbs['#'] = 'Enrolments';
                display_breadcrumbs($breadcrumbs);
                ?>
                
                <div class="card">
                    <h3>Enrolled Staff for '<?php echo htmlspecialchars($course['title']); ?>' on <?php echo date('d M Y', strtotime($course['course_date'])); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($enrolled_users)): ?>
                                <tr><td colspan="3">No users are currently enrolled.</td></tr>
                            <?php else: ?>
                                <?php foreach ($enrolled_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <a href="/admin/remove_enrolment.php?enrolment_id=<?php echo $user['enrolment_id']; ?>&course_id=<?php echo $course_id; ?>&series_id=<?php echo $series_id_for_back_link; ?>"
                                               class="open-confirm-modal"
                                               data-message="Are you sure you want to remove <?php echo htmlspecialchars($user['first_name']); ?> from this course?"
                                               data-title="Confirm Removal"
                                               data-btn-text="Yes, Remove"
                                               data-btn-class="btn-danger">Remove</a>
                                        </td>
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