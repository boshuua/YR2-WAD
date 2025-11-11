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

// Get limit parameter (default to 50 entries)
$limit = isset($_GET['limit']) ? getInt($_GET['limit'], 50) : 50;
if ($limit <= 0) {
    $limit = 50;
}

// Fetch activity log
try {
    $query = "SELECT id, user_id, user_email, action, details, ip_address, timestamp
              FROM activity_log
              ORDER BY timestamp DESC
              LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendOk($logs);
} catch (PDOException $e) {
    error_log("Database error fetching activity log: " . $e->getMessage());
    sendServiceUnavailable("Database error occurred while fetching activity log.");
}
?>