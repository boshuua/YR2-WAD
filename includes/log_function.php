<?php
/**
 * Writes a message to the user activity log.
 *
 * @param string $message The action performed by the user.
 */
function log_activity($message) {
    // Session data should be set at login
    $user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'System';
    $user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'N/A';

    $logs_dir = __DIR__ . '/../logs';
    $log_file = $logs_dir . '/user_activity.log';

    // --- ROBUSTNESS CHECKS ---
    // 1. Check if the logs directory exists. If not, try to create it.
    if (!is_dir($logs_dir)) {
        // The @ suppresses errors, allowing us to handle it manually
        if (!@mkdir($logs_dir, 0755, true)) {
            // If creation fails, we can't proceed. In a real app, you might log this error elsewhere.
            return; // Exit the function silently
        }
    }

    // 2. Check if the directory is writable.
    if (!is_writable($logs_dir)) {
        // Directory exists but we can't write to it.
        return; 
    }
    // --- END CHECKS ---

    // Format the log entry
    $log_entry = sprintf(
        "[%s] [User: %s (%s)] %s" . PHP_EOL,
        date('Y-m-d H:i:s'),
        $user_name,
        $user_email,
        $message
    );

    // Append the entry to the file
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}