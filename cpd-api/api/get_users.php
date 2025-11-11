<?php
// Load configuration and helpers
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/validation_helper.php';

// Handle CORS preflight
handleCorsPrelight();

// Require GET method
requireMethod('GET');

// Require admin authentication
requireAdmin();

// Get database connection
$database = new Database();
$db = $database->getConn();

// Check if an ID is provided in the query string
$userId = isset($_GET['id']) ? getInt($_GET['id']) : 0;

if ($userId > 0) {
    // Fetch a single user by ID
    $query = "SELECT id, email, first_name, last_name, job_title, access_level
              FROM users
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        sendOk($user);
    } else {
        sendNotFound("User not found.");
    }
} else {
    // Fetch all users
    $query = "SELECT id, email, first_name, last_name, job_title, access_level
              FROM users
              ORDER BY last_name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendOk($users);
}
?>