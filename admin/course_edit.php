<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

$course_id = $_GET['id'];
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $start_date_str = trim($_POST['start_date']);
    $duration_hours = filter_input(INPUT_POST, 'duration_hours', FILTER_VALIDATE_INT) ?: 0;
    $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT) ?: 0;
    $max_attendees = filter_input(INPUT_POST, 'max_attendees', FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);

    // Calculate the new end date
    $start_date = new DateTime($start_date_str);
    $end_date = clone $start_date;
    $end_date->modify("+$duration_hours hours +$duration_minutes minutes");

    $stmt = $pdo->prepare("UPDATE courses SET title = ?, course_date = ?, end_date = ?, max_attendees = ?, description = ? WHERE id = ?");
    $stmt->execute([$title, $start_date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $max_attendees, $description, $course_id]);
    $success = "Course updated successfully! <a href='courses.php'>Back to list</a>";
}

// Fetch current data to pre-fill the form
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

// Calculate current duration for the form
$start = new DateTime($course['course_date']);
$end = new DateTime($course['end_date']);
$interval = $start->diff($end);
$current_hours = ($interval->days * 24) + $interval->h;
$current_minutes = $interval->i;
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Edit Course</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Edit Course</h1></header>
            <div class="app-content">
                <div class="card">
                     <?php if ($success): ?><p class="success-message"><?php echo $success; ?></p><?php endif; ?>
                    <form method="post">
                        <div class="form-group"><label>Title</label><input type="text" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required></div>
                        <div class="form-group"><label>Start Date & Time</label><input type="datetime-local" name="start_date" value="<?php echo date('Y-m-d\TH:i', strtotime($course['course_date'])); ?>" required></div>
                        <div class="form-group"><label>Duration</label>
                             <input type="number" name="duration_hours" value="<?php echo $current_hours; ?>" min="0" style="width: 60px;"> Hours
                             <input type="number" name="duration_minutes" value="<?php echo $current_minutes; ?>" min="0" max="59" style="width: 60px;"> Minutes
                        </div>
                        <div class="form-group"><label>Max Attendees</label><input type="number" name="max_attendees" value="<?php echo htmlspecialchars($course['max_attendees']); ?>" required></div>
                        <div class="form-group"><label>Description</label><textarea name="description" rows="5" required><?php echo htmlspecialchars($course['description']); ?></textarea></div>
                        <button type="submit" class="btn">Update Course</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>