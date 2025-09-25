<?php
require_once '../includes/auth_check.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="<?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') echo 'dark-mode'; ?>">
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header">
                <h1>Admin Dashboard</h1>
                <div class="header-user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                </div>
            </header>
            <div class="app-content">
                <h2>Overview</h2>
                <div class="dashboard-grid">
                    <div class="card">
                        <h3>Courses</h3>
                        <p>Manage all upcoming and past training courses.</p>
                        <a href="courses.php" class="btn">View Courses</a>
                    </div>
                    <div class="card">
                        <h3>Users</h3>
                        <p>Add, edit, and remove staff user accounts.</p>
                        <a href="users.php" class="btn">View Users</a>
                    </div>
                    <div class="card">
                        <h3>Reports (Future)</h3>
                        <p>View enrolment statistics and attendance records.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>