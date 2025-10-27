<?php

header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
header("Content-Type: application/json; charset=UTF-8");


session_start();
include_once '../config/database.php';

// SESSION SECURITY CHECK - Ensure only admins can view the full log
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] !== 'admin') {
    http_response_code(403); // Forbidden
    echo json_encode(["message" => "Access Denied: Admin privileges required."]);
    exit();
}

$database = new Database();
$db = $database->getConn(); 

// Optional: Add limit for pagination or just showing recent logs
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Default to last 50 entries
if ($limit <= 0) {
    $limit = 50;
}

try {
    $query = "SELECT id, user_id, user_email, action, details, ip_address, timestamp
              FROM activity_log
              ORDER BY timestamp DESC
              LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode($logs);

} catch (PDOException $e) {
    http_response_code(503); // Service Unavailable
    error_log("Database error fetching activity log: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred while fetching activity log."]);
}
?>