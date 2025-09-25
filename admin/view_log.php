<?php
require_once '../includes/auth_check.php';
require_admin();

// Initial data load is still done with PHP for the first page view
$log_file_path = __DIR__ . '/../logs/user_activity.log';
$log_content = [];
$permission_error = '';

// --- PERMISSION CHECK ---
if (!is_dir(dirname($log_file_path))) {
    $permission_error = "The 'logs' directory does not exist. Please create it in the root of your project.";
} elseif (!is_writable(dirname($log_file_path))) {
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
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="log-table-body">
                                <?php if (empty($log_content)): ?>
                                    <tr><td colspan="3">No activity has been logged yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($log_content as $line): ?>
                                        <?php
                                        $pattern = '/^\[(.*?)\]\s\[User:\s(.*?)\]\s(.*)$/';
                                        if (preg_match($pattern, $line, $matches)) {
                                            $timestamp = htmlspecialchars($matches[1]);
                                            $user_info = htmlspecialchars($matches[2]);
                                            $message = htmlspecialchars($matches[3]);
                                            echo "<tr><td>{$timestamp}</td><td>{$user_info}</td><td>{$message}</td></tr>";
                                        }
                                        ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logTableBody = document.getElementById('log-table-body');

            // Function to fetch logs and update the table
            const fetchLogs = async () => {
                try {
                    const response = await fetch('/api/get_logs.php');
                    const logs = await response.json();

                    // Clear the current table body
                    logTableBody.innerHTML = '';

                    if (logs.length === 0) {
                        logTableBody.innerHTML = '<tr><td colspan="3">No activity has been logged yet.</td></tr>';
                    } else {
                        // Create and append new rows from the fetched data
                        logs.forEach(log => {
                            const row = logTableBody.insertRow();
                            row.insertCell(0).textContent = log.timestamp;
                            row.insertCell(1).textContent = log.user;
                            row.insertCell(2).textContent = log.action;
                        });
                    }
                } catch (error) {
                    console.error('Failed to fetch logs:', error);
                }
            };

            // Fetch the logs every 5 seconds (5000 milliseconds)
            setInterval(fetchLogs, 5000);
        });
    </script>

</body>
</html>