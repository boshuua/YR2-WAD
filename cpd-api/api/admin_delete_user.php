<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require DELETE method
requireMethod('DELETE');

// Require admin authentication
requireAdmin();

// Get user ID from query string
$userID = isset($_GET['id']) ? getInt($_GET['id']) : 0;

if ($userID <= 0) {
    sendBadRequest('Invalid user ID');
}

// Prevent admin from deleting themselves
if (getCurrentUserId() == $userID) {
    sendBadRequest('You cannot delete your own account.');
}

// Get database connection
$database = new Database();
$db = $database->getConn();

// Fetch email of user being deleted for logging
$emailToLog = 'Unknown (User not found)';
try {
    $fetchEmailQuery = "SELECT email FROM users WHERE id = :id";
    $fetchStmt = $db->prepare($fetchEmailQuery);
    $fetchStmt->bindParam(':id', $userID, PDO::PARAM_INT);
    $fetchStmt->execute();
    if ($fetchStmt->rowCount() > 0) {
        $emailToLog = $fetchStmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch email before delete: " . $e->getMessage());
}

// Execute deletion
try {
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(":id", $userID, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            // Log successful deletion
            $details = "Admin deleted user ID: {$userID}, Email: {$emailToLog}";
            log_activity($db, getCurrentUserId(), getCurrentUserEmail(), 'admin_delete_user_success', $details);

            sendOk(["message" => "User deleted successfully"]);
        } else {
            // Log attempt to delete non-existent user
            $details = "Admin attempted to delete non-existent user ID: {$userID}";
            log_activity($db, getCurrentUserId(), getCurrentUserEmail(), 'admin_delete_user_failed_notfound', $details);

            sendNotFound("User not found or already deleted");
        }
    } else {
        // Log database execution failure
        $details = "Database error executing delete for user ID: {$userID}";
        log_activity($db, getCurrentUserId(), getCurrentUserEmail(), 'admin_delete_user_failed_execution', $details);

        sendServiceUnavailable("Unable to delete user.");
    }
} catch (PDOException $e) {
    // Log general database error
    $details = "Database exception deleting user ID {$userID}: " . $e->getMessage();
    log_activity($db, getCurrentUserId(), getCurrentUserEmail(), 'admin_delete_user_error', $details);

    error_log("Database error during delete: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred during deletion.");
}
?>