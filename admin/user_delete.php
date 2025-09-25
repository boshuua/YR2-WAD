<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';
require_once '../includes/log_function.php'; 

$user_id_to_delete = $_GET['id'];

// Prevent an admin from deleting their own account
if ($user_id_to_delete == $_SESSION['user_id']) {
    // Redirect with an error message (optional)
    header("Location: users.php?error=selfdelete");
    exit();
}

// Proceed with deletion
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$user_id_to_delete]);

if ($user) {
    log_activity("Deleted user: '{$user['first_name']} {$user['last_name']}' (ID: {$user_id_to_delete})."); // <<< ADD THIS
}

header("Location: users.php?status=deleted");
exit();