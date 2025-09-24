<?php
require_once '../includes/auth_check.php';
require_admin();
require_once '../includes/db_connect.php';

$user_id_to_delete = $_GET['id'];

// CRITICAL: Prevent an admin from deleting their own account
if ($user_id_to_delete == $_SESSION['user_id']) {
    // Redirect with an error message (optional)
    header("Location: users.php?error=selfdelete");
    exit();
}

// Proceed with deletion
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$user_id_to_delete]);

header("Location: users.php?status=deleted");
exit();