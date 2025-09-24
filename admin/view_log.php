<?php
require_once '../includes/auth_check.php';
require_admin();

$logs_dir = __DIR__ . '/../logs';
$log_file_path = $logs_dir . '/user_activity.log';
$log_content = [];
$permission_error = '';

// --- PERMISSION CHECK ---
if (!is_dir($logs_dir)) {
    $permission_error = "The 'logs' directory does not exist. Please create it in the root of your project.";
} elseif (!is_writable($logs_dir)) {
    $permission_error = "The 'logs' directory is not writable. Please check the server permissions (try setting to 755).";
} elseif (file_exists($log_file_path) && !is_writable($log_file_path)) {
    $permission_error = "The log file 'user_activity.log' exists but is not writable. Please check its permissions.";
}
// --- END CHECK ---

if (empty($permission_error) && file_exists($log_file_path)) {
    $lines = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log_content = array_reverse($lines);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Activity Log</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .log-container { background-color: #2d3436; color: #dfe6e9; font-family: monospace; padding: 20px; border-radius: 5px; max-height: 600px; overflow-y: auto; white-space: pre-wrap; }
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
                    
                    <?php if ($permission_error): ?>
                        <p class="error-message"><?php echo $permission_error; ?></p>
                    <?php endif; ?>

                    <div class="log-container">
                        <?php if (empty($log_content) && empty($permission_error)): ?>
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