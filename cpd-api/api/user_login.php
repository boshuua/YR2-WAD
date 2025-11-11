<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';
include_once '../helpers/log_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require POST method
requireMethod('POST');

// Get and validate input
$data = getJsonInput();
requireFields($data, ['email', 'password']);
requireValidEmail($data->email);

// Get database connection
$database = new Database();
$db = $database->getConn();

// Check credentials using pgcrypto
$query = "SELECT id, first_name, last_name, email, password, access_level
          FROM users
          WHERE email = :email AND password = crypt(:password, password)";
$stmt = $db->prepare($query);
$stmt->bindParam(':email', $data->email);
$stmt->bindParam(':password', $data->password);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Set user session
    setUserSession(
        $row['id'],
        $row['email'],
        $row['first_name'],
        $row['last_name'],
        $row['access_level']
    );

    // Log successful login
    log_activity($db, $row['id'], $row['email'], 'login_success');

    // Send response
    sendOk([
        "message" => "Login successful.",
        "user" => [
            "id" => $row['id'],
            "first_name" => $row['first_name'],
            "access_level" => $row['access_level']
        ]
    ]);
} else {
    // Log failed login attempt
    log_activity($db, null, $data->email, 'login_failed', 'Invalid credentials');

    sendUnauthorized("Login failed. Invalid credentials.");
}
?>
