<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

$course_id = $_GET['id'];

// Fetch the course details, including the series_id
$stmt = $pdo->prepare("SELECT title, series_id FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: courses.php");
    exit();
}

// Handle form submission for deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_one'])) {
        // Delete only this instance
        $delete_stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $delete_stmt->execute([$course_id]);
    } elseif (isset($_POST['delete_series']) && !empty($course['series_id'])) {
        // Delete the entire series
        $delete_stmt = $pdo->prepare("DELETE FROM courses WHERE series_id = ?");
        $delete_stmt->execute([$course['series_id']]);
    }
    header("Location: courses.php?status=deleted");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Delete Course</title><link rel="stylesheet" href="../css/style.css"></head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>Confirm Deletion</h1></header>
            <div class="app-content">
                <div class="card">
                    <h3>Delete Course: <?php echo htmlspecialchars($course['title']); ?></h3>
                    <p>Please choose an option below. This action cannot be undone.</p>
                    <form method="post">
                        <button type="submit" name="delete_one" class="btn">Delete Only This Instance</button>
                        
                        <?php if (!empty($course['series_id'])): ?>
                            <button type="submit" name="delete_series" class="btn" style="background-color: #c0392b; margin-top: 10px;">Delete The Entire Recurring Series</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>