<?php
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_admin();

$log_file_path = __DIR__ . '/../logs/email_log.json';
$logs_data = [];
$records_to_fetch = 10; // Match the number from your view_log.php

if (file_exists($log_file_path)) {
    $all_logs = json_decode(file_get_contents($log_file_path), true);
    if (is_array($all_logs)) {
        $all_logs = array_reverse($all_logs); // Newest first
        $logs_data = array_slice($all_logs, 0, $records_to_fetch); // Only get the first page worth
    }
}

echo json_encode($logs_data);