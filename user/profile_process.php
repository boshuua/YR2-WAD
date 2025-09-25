<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// --- ACTION 1: UPDATE PERSONAL DETAILS ---
if ($action === 'update_details') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $job_title = trim($_POST['job_title']);

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, job_title = ? WHERE id = ?");
    $stmt->execute([$first_name, $last_name, $job_title, $user_id]);
    
    $_SESSION['user_name'] = $first_name; // Update session name immediately
    header("Location: profile.php?status=details_updated");
    exit();
}

// --- ACTION 2: CHANGE PASSWORD ---
if ($action === 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch current password hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!password_verify($current_password, $user['password'])) {
        header("Location: profile.php?error=current_password_incorrect");
        exit();
    }
    if ($new_password !== $confirm_password) {
        header("Location: profile.php?error=passwords_do_not_match");
        exit();
    }
    
    // All good, hash and update the new password
    $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$new_hashed_password, $user_id]);

    header("Location: profile.php?status=password_updated");
    exit();
}

// --- ACTION 3: UPDATE THEME PREFERENCE ---
if ($action === 'update_theme') {
    $theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';

    $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
    $stmt->execute([$theme, $user_id]);

    $_SESSION['theme'] = $theme; // Update session immediately
    header("Location: profile.php?status=theme_updated");
    exit();
}

// Redirect back if no action matched
header("Location: profile.php");
exit();