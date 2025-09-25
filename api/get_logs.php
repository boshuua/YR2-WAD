<?php
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_admin();

$log_file_path = __DIR__ . '/../logs/user_activity.log';
$logs_data = [];

if (file_exists($log_file_path)) {
    $lines = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Show newest first

    foreach ($lines as $line) {
        // Use the same regex from your view_log page to parse the line
        $pattern = '/^\[(.*?)\]\s\[User:\s(.*?)\]\s(.*)$/';
        if (preg_match($pattern, $line, $matches)) {
            $logs_data[] = [
                'timestamp' => $matches[1],
                'user'      => $matches[2],
                'action'    => $matches[3]
            ];
        }
    }
}

// Send the data back as a JSON response
echo json_encode($logs_data);