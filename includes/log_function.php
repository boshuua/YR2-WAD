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
/**
 * Writes a record of a sent email to a separate log file.
 *
 * @param string $recipient The email address of the recipient.
 * @param string $subject The subject line of the email.
 * @param string $body The full HTML content of the email.
 */
function log_email($recipient, $subject, $body) {
    $user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'System';
    $user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'N/A';
    
    $logs_dir = __DIR__ . '/../logs';
    $log_file = $logs_dir . '/email_log.json';

    // Ensure the logs directory exists and is writable
    if (!is_dir($logs_dir)) {
        @mkdir($logs_dir, 0755, true);
    }
    if (!is_writable($logs_dir)) {
        return; // Cannot write, so exit silently
    }

    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'sent_by_user' => "{$user_name} ({$user_email})",
        'recipient' => $recipient,
        'subject' => $subject,
        'body' => $body // Store the full HTML body
    ];

    // Read existing logs, add the new one, and save back to the file
    $logs = file_exists($log_file) ? json_decode(file_get_contents($log_file), true) : [];
    $logs[] = $log_entry;
    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));
}