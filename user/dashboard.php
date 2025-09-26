<?php
// Gatekeeper: Make sure the user is logged in
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];


// Query now uses NOW() to be consistent with the my_courses page
$stmt = $pdo->prepare(
    "SELECT COUNT(*) as upcoming_count 
     FROM enrolments e
     JOIN courses c ON e.course_id = c.id
     WHERE e.user_id = ? AND c.course_date >= NOW()"
);
$stmt->execute([$user_id]);
$upcoming_count = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="<?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') echo 'dark-mode'; ?>">
    <div class="app-container">
        <?php include '../includes/user_sidebar.php'; ?>

        <main class="app-main">
            <header class="app-header">
                <h1>User Dashboard</h1>
                <div class="header-user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                </div>
            </header>
            <div class="app-content">
                <h2>Quick Actions</h2>
                <div class="dashboard-grid">
                    
                    <div class="card">
                        <div class="card-content">
                            <h3>Welcome</h3>
                            <p>This is your personal dashboard for managing Continuing Professional Development (CPD) and training courses.</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-content">
                            <h3>My Upcoming Courses</h3>
                            <p>You are currently enrolled in <strong><?php echo $upcoming_count; ?></strong> upcoming course(s).</p>
                        </div>
                        <a href="/my-courses" class="btn">View My Enrolments</a>
                    </div>

                    <div class="card">
                        <div class="card-content">
                            <h3>Browse & Enrol</h3>
                            <p>View all available courses and enrol in new training sessions to enhance your skills.</p>
                        </div>
                        <a href="/courses" class="btn">Browse Available Courses</a>
                    </div>

                </div>
            </div>
        </main>
    </div>
</body>
</html>