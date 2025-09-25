<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';
require_once '../includes/log_function.php'; 

$user_id_to_delete = $_GET['id'];

// Prevent an admin from deleting their own account
if ($user_id_to_delete == $_SESSION['user_id']) {
    header("Location: /admin/users?error=selfdelete");
    exit();
}

// Fetch user info BEFORE deleting for the log message
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id_to_delete]);
$user = $stmt->fetch();

// Proceed with deletion
$delete_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$delete_stmt->execute([$user_id_to_delete]);

// Log the action if we found the user's info
if ($user) {
    log_activity("Deleted user: '{$user['first_name']} {$user['last_name']}' (ID: {$user_id_to_delete}).");
}

header("Location: /admin/users?status=deleted");
exit();