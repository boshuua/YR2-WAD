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

// Require admin authentication
requireAdmin();

// Get and validate input
$data = getJsonInput();

// Validate required fields
requireFields($data, ['first_name', 'email', 'password', 'access_level']);

// Validate email format
requireValidEmail($data->email);

// Validate access level
requireInList($data->access_level, ['admin', 'user'], 'access_level');

// Get database connection
$database = new Database();
$db = $database->getConn();

// Prepare query
$query = "INSERT INTO users (first_name, last_name, email, password, job_title, access_level)
          VALUES (:first_name, :last_name, :email, crypt(:password, gen_salt('bf')), :job_title, :access_level)";

$stmt = $db->prepare($query);

// Handle optional fields
$last_name = getValue($data, 'last_name', '');
$job_title = getValue($data, 'job_title', '');

// Bind parameters
$stmt->bindParam(':first_name', $data->first_name);
$stmt->bindParam(':last_name', $last_name);
$stmt->bindParam(':email', $data->email);
$stmt->bindParam(':password', $data->password);
$stmt->bindParam(':job_title', $job_title);
$stmt->bindParam(':access_level', $data->access_level);

// Execute and respond
if ($stmt->execute()) {
    log_activity($db, getCurrentUserId(), getCurrentUserEmail(), "Created User", "User: {$data->email}");
    sendCreated(["message" => "User created successfully."]);
} else {
    sendServiceUnavailable("Unable to create user.");
}
?>