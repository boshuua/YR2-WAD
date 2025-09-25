<?php
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_admin();

$log_file_path = __DIR__ . '/../logs/user_activity.log';
$logs_data = [];
$records_to_fetch = 10; // Match the number from view_log.php

if (file_exists($log_file_path)) {
    $lines = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Newest first
    $lines = array_slice($lines, 0, $records_to_fetch); // Only get the first page worth

    foreach ($lines as $line) {
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

echo json_encode($logs_data);