<?php
session_start();
include_once '../config/database.php';
include_once '../helpers/log_helper.php';

// --- Security Check ---
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

// --- Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed. Use PUT."]);
    exit();
}

// --- Get Input Data ---
$data = json_decode(file_get_contents("php://input"));

// --- Validate Input ---
if (empty($data->current_password) || empty($data->new_password)) {
    http_response_code(400);
    echo json_encode(["message" => "Current password and new password are required."]);
    exit();
}

if (strlen($data->new_password) < 6) {
    http_response_code(400);
    echo json_encode(["message" => "New password must be at least 6 characters long."]);
    exit();
}

$userId = $_SESSION['user_id'] ?? null;
$userEmail = $_SESSION['user_email'] ?? 'Unknown Admin';

if (!$userId) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized: User ID not found in session."]);
    exit();
}

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

// --- Verify Current Password ---
try {
    $query = "SELECT password FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["message" => "User not found."]);
        log_activity($db, $userId, $userEmail, 'Password Change Failed', "User ID {$userId} not found during password change.");
        exit();
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stored_password_hash = $row['password'];

    // Verify current password using crypt
    if (crypt($data->current_password, $stored_password_hash) !== $stored_password_hash) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid current password."]);
        log_activity($db, $userId, $userEmail, 'Password Change Failed', "Invalid current password provided.");
        exit();
    }

    // --- Hash New Password and Update ---
    $new_password_hash = crypt($data->new_password, '$2a$10$' . bin2hex(random_bytes(22))); // Generate new salt

    $updateQuery = "UPDATE users SET password = :password WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':password', $new_password_hash);
    $updateStmt->bindParam(':id', $userId, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        http_response_code(200);
        echo json_encode(["message" => "Password updated successfully."]);
        log_activity($db, $userId, $userEmail, 'Password Changed', "Admin user ID {$userId} changed password.");
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to update password."]);
        log_activity($db, $userId, $userEmail, 'Password Change Failed', "DB execution error for user ID {$userId}.");
    }

} catch (PDOException $e) {
    http_response_code(503);
    error_log("Database error during password update: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred during password update."]);
    log_activity($db, $userId, $userEmail, 'Password Change Failed', "DB exception for user ID {$userId}: " . $e->getMessage());
}

?>