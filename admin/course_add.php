<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Form Data ---
    $title = trim($_POST['title']);
    $start_date_str = trim($_POST['start_date']);
    $duration_hours = filter_input(INPUT_POST, 'duration_hours', FILTER_VALIDATE_INT) ?: 0;
    $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT) ?: 0;
    $max_attendees = filter_input(INPUT_POST, 'max_attendees', FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);
    $recurrence_type = $_POST['recurrence_type'];
    $recurrence_count = filter_input(INPUT_POST, 'recurrence_count', FILTER_VALIDATE_INT) ?: 0;
    
    // --- Validation ---
    if (empty($title) || empty($start_date_str) || !$max_attendees) {
        $error = "Title, start date, and max attendees are required.";
    } else {
        try {
            $pdo->beginTransaction();

            $start_date = new DateTime($start_date_str);
            // Calculate end date based on duration
            $end_date = clone $start_date;
            $end_date->modify("+$duration_hours hours +$duration_minutes minutes");

            // Generate a unique ID for this series of events
            $series_id = uniqid('series_');

            $stmt = $pdo->prepare(
                "INSERT INTO courses (series_id, title, course_date, end_date, max_attendees, description) VALUES (?, ?, ?, ?, ?, ?)"
            );

            // Insert the first course
            $stmt->execute([$series_id, $title, $start_date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $max_attendees, $description]);
            $created_count = 1;

            // Handle recurrence
            if ($recurrence_type !== 'none' && $recurrence_count > 0) {
                for ($i = 0; $i < $recurrence_count; $i++) {
                    // Get the next date based on the selected recurrence type
                    $start_date->modify($recurrence_type);
                    $end_date->modify($recurrence_type);
                    
                    $stmt->execute([$series_id, $title, $start_date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $max_attendees, $description]);
                    $created_count++;
                }
            }
            
            $pdo->commit();
            $success = "Successfully created " . $created_count . " course(s)! <a href='courses.php'>View Courses</a>";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Add Course</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Add New Course</h1></header>
            <div class="app-content">
                <div class="card">
                    <?php if ($error): ?><p class="error-message"><?php echo $error; ?></p><?php endif; ?>
                    <?php if ($success): ?><p class="success-message"><?php echo $success; ?></p><?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group"><label>Course Title</label><input type="text" name="title" required></div>
                        <div class="form-group"><label>Course Start Date & Time</label><input type="datetime-local" name="start_date" required></div>
                        <div class="form-group"><label>Duration</label>
                            <input type="number" name="duration_hours" min="0" value="1" style="width: 60px;"> Hours
                            <input type="number" name="duration_minutes" min="0" max="59" value="0" style="width: 60px;"> Minutes
                        </div>
                        <div class="form-group"><label>Max Attendees</label><input type="number" name="max_attendees" required></div>
                        <div class="form-group"><label>Description</label><textarea name="description" rows="5" required></textarea></div>
                        <hr style="border: 0; border-top: 1px solid var(--border-grey); margin: 20px 0;">
                        
                        <div class="form-group"><label>Recurrence</label>
                            <select name="recurrence_type">
                                <option value="none">None</option>
                                <option value="+1 month">Repeat every month</option>
                                <option value="+2 months">Repeat every 2 months</option>
                                <option value="+1 year">Repeat every year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Number of additional repetitions:</label>
                            <input type="number" name="recurrence_count" min="0" value="0">
                        </div>
                        <button type="submit" class="btn">Add Course(s)</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>