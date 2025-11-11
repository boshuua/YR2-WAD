<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require PUT method
requireMethod('PUT');

// Require admin authentication
requireAdmin();

// Get and validate input
$data = getJsonInput();
requireFields($data, ['current_password', 'new_password']);

// Validate password length
if (strlen($data->new_password) < 6) {
    sendBadRequest("New password must be at least 6 characters long.");
}

// Get current user from session
$userId = getCurrentUserId();
$userEmail = getCurrentUserEmail();

if (!$userId) {
    sendUnauthorized("Unauthorized: User ID not found in session.");
}

// Get database connection
$database = new Database();
$db = $database->getConn();

// Verify current password
try {
    $query = "SELECT password FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        log_activity($db, $userId, $userEmail, 'Password Change Failed', "User ID {$userId} not found during password change.");
        sendNotFound("User not found.");
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stored_password_hash = $row['password'];

    // Verify current password using crypt
    if (crypt($data->current_password, $stored_password_hash) !== $stored_password_hash) {
        log_activity($db, $userId, $userEmail, 'Password Change Failed', "Invalid current password provided.");
        sendUnauthorized("Invalid current password.");
    }

    // Hash new password and update
    $new_password_hash = crypt($data->new_password, '$2a$10$' . bin2hex(random_bytes(22)));

    $updateQuery = "UPDATE users SET password = :password WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':password', $new_password_hash);
    $updateStmt->bindParam(':id', $userId, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        log_activity($db, $userId, $userEmail, 'Password Changed', "Admin user ID {$userId} changed password.");
        sendOk(["message" => "Password updated successfully."]);
    } else {
        log_activity($db, $userId, $userEmail, 'Password Change Failed', "DB execution error for user ID {$userId}.");
        sendServiceUnavailable("Unable to update password.");
    }
} catch (PDOException $e) {
    error_log("Database error during password update: " . $e->getMessage());
    log_activity($db, $userId, $userEmail, 'Password Change Failed', "DB exception for user ID {$userId}: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred during password update.");
}
?>