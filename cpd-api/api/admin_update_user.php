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

// Get user ID from query string
$userId = isset($_GET['id']) ? getInt($_GET['id']) : 0;

if ($userId <= 0) {
    sendBadRequest("Invalid user ID provided.");
}

// Get and validate input
$data = getJsonInput();
requireFields($data, ['first_name', 'email', 'access_level']);
requireValidEmail($data->email);
requireInList($data->access_level, ['admin', 'user'], 'access_level');

// Get database connection
$database = new Database();
$db = $database->getConn();

// Prepare update query
$query = "UPDATE users
          SET first_name = :first_name,
              last_name = :last_name,
              email = :email,
              job_title = :job_title,
              access_level = :access_level
          WHERE id = :id";

$stmt = $db->prepare($query);

// Handle optional fields
$last_name = getValue($data, 'last_name', '');
$job_title = getValue($data, 'job_title', '');

// Bind parameters
$stmt->bindParam(':first_name', $data->first_name);
$stmt->bindParam(':last_name', $last_name);
$stmt->bindParam(':email', $data->email);
$stmt->bindParam(':job_title', $job_title);
$stmt->bindParam(':access_level', $data->access_level);
$stmt->bindParam(':id', $userId, PDO::PARAM_INT);

// Execute and respond
try {
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            log_activity($db, getCurrentUserId(), getCurrentUserEmail(), "Updated User", "User ID: {$userId}");
            sendOk(["message" => "User updated successfully."]);
        } else {
            // Check if user exists but no changes were made
            $checkQuery = "SELECT COUNT(*) FROM users WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->fetchColumn() > 0) {
                sendOk(["message" => "No changes detected for the user."]);
            } else {
                sendNotFound("User not found.");
            }
        }
    } else {
        sendServiceUnavailable("Unable to update user.");
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred during update.");
}
?>
