<?php
/**
 * Writes a message to the user activity log.
 *
 * @param string $message The action performed by the user.
 */
function log_activity($message) {
    // Get user details from the session if available
    $user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'System';
    $user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'N/A'; // We'll add user_email to the session later

    // Define the path for the log file
    $log_file = __DIR__ . '/../logs/user_activity.log'; // Place logs in a new /logs folder

    // Format the log entry
    // Example: [2025-09-24 22:45:00] [User: Admin User (admin@example.com)] Enrolled in course.
    $log_entry = sprintf(
        "[%s] [User: %s (%s)] %s" . PHP_EOL,
        date('Y-m-d H:i:s'), // Current timestamp
        $user_name,
        $user_email,
        $message
    );

    // Ensure the /logs directory exists
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }

    // Append the entry to the file. FILE_APPEND prevents overwriting the file.
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}