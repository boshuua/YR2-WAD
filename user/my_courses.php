<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
$user_id = $_SESSION['user_id'];

// Fetch UPCOMING enrolments
$stmt_upcoming = $pdo->prepare(
    "SELECT c.*, e.id as enrolment_id FROM courses c
     JOIN enrolments e ON c.id = e.course_id
     WHERE e.user_id = ? AND c.course_date >= CURDATE() ORDER BY c.course_date ASC"
);
$stmt_upcoming->execute([$user_id]);
$upcoming_enrolments = $stmt_upcoming->fetchAll();

// Fetch PAST enrolments
$stmt_history = $pdo->prepare(
    "SELECT c.* FROM courses c
     JOIN enrolments e ON c.id = e.course_id
     WHERE e.user_id = ? AND c.course_date < CURDATE() ORDER BY c.course_date DESC"
);
$stmt_history->execute([$user_id]);
$past_enrolments = $stmt_history->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>My Enrolments</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="app-container">
        <?php include '../includes/user_sidebar.php'; ?>
        <?php include '../includes/header.php'; ?>
        <main class="app-main">
            <header class="app-header">
                <h1>My Enrolments</h1>
            </header>
            <div class="app-content">
                <div class="card">
                    <h3>My Upcoming Courses</h3>
                    <table>
                        <tbody>
                            <?php foreach ($upcoming_enrolments as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($course['course_date'])); ?></td>
                                    <td>
                                        <a href="/user/cancel_enrolment.php?enrolment_id=<?php echo $course['enrolment_id']; ?>"
                                            class="open-confirm-modal"
                                            data-message="Are you sure you want to cancel your enrolment for '<?php echo htmlspecialchars($course['title']); ?>'?"
                                            data-title="Cancel Enrolment" data-btn-text="Yes, Cancel Booking"
                                            data-btn-class="btn-danger">Cancel Booking</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card" style="margin-top: 30px;">
                    <h3>My Course History</h3>
                    <table>
                        <tbody>
                            <?php foreach ($past_enrolments as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($course['course_date'])); ?></td>
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