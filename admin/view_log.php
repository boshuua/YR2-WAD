<?php
require_once '../includes/auth_check.php';
require_admin();

$log_file_path = __DIR__ . '/../logs/user_activity.log';
$log_content = [];

if (file_exists($log_file_path)) {
    // Read the file into an array of lines
    $lines = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    // Reverse the array to show the most recent entries first
    $log_content = array_reverse($lines);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Log</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .log-container {
            background-color: #2d3436;
            color: #dfe6e9;
            font-family: monospace;
            padding: 20px;
            border-radius: 5px;
            max-height: 600px;
            overflow-y: auto;
            white-space: pre-wrap; /* Allows long lines to wrap */
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        <main class="app-main">
            <header class="app-header"><h1>User Activity Log</h1></header>
            <div class="app-content">
                <div class="card">
                    <h3>Activity Feed (Newest First)</h3>
                    <div class="log-container">
                        <?php if (empty($log_content)): ?>
                            <p>No activity has been logged yet.</p>
                        <?php else: ?>
                            <?php foreach ($log_content as $line): ?>
                                <?php echo htmlspecialchars($line) . "\n"; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>