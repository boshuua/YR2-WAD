<?php
session_start();
include_once '../config/database.php';

// SESSION SECURITY CHECK
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

$database = new Database();
$db = $database->getConn(); 

// Check if an ID is provided in the query string
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId > 0) {
    // Fetch a single user by ID
    $query = "SELECT id, email, first_name, last_name, job_title, access_level FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        http_response_code(200);
        // Return a single user object, not an array
        echo json_encode($user);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(["message" => "User not found."]);
    }

} else {
    // Fetch all users (original functionality)
    $query = "SELECT id, email, first_name, last_name, job_title, access_level FROM users ORDER BY last_name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    // Return an array of users
    echo json_encode($users);
}
?>