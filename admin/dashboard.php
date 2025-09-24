<?php
// Gatekeeper: Make sure user is logged in AND is an admin
require_once '../includes/auth_check.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css"> </head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <nav>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            <a href="courses.php">Manage Courses</a> |
            <a href="users.php">Manage Users</a> |
            <a href="../logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <p>This is the main control panel for administrators.</p>
    </main>
</body>
</html>