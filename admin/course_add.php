<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $title = trim($_POST['title']);
    $course_date = trim($_POST['course_date']);
    $duration = trim($_POST['duration']);
    $max_attendees = filter_input(INPUT_POST, 'max_attendees', FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);

    if (empty($title) || empty($course_date) || empty($duration) || $max_attendees === false || empty($description)) {
        $error = "All fields are required.";
    } elseif ($max_attendees <= 0) {
        $error = "Max attendees must be a positive number.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (title, course_date, duration, max_attendees, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $course_date, $duration, $max_attendees, $description]);
            $success = "Course added successfully! <a href='courses.php'>View Courses</a>";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Course</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Add New Course</h1></header>
            <div class="app-content">
                <div class="card">
                    <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>
                    <?php if ($success): ?><p class="success-message"><?php echo $success; ?></p><?php endif; ?>
                    
                    <form action="course_add.php" method="post">
                        <div class="form-group">
                            <label for="title">Course Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="course_date">Course Date & Time</label>
                            <input type="datetime-local" id="course_date" name="course_date" required>
                        </div>
                        <div class="form-group">
                            <label for="duration">Duration (e.g., 2 hours, 1 day)</label>
                            <input type="text" id="duration" name="duration" required>
                        </div>
                        <div class="form-group">
                            <label for="max_attendees">Max Attendees</label>
                            <input type="number" id="max_attendees" name="max_attendees" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn">Add Course</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>