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

// --- Get Input Data ---
// Expecting ID in query string like ?id=123
// Expecting user data in JSON body for PUT request
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = json_decode(file_get_contents("php://input"));

// --- Validate Input ---
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid user ID provided."]);
    exit();
}

if (!isset($data->first_name) || !isset($data->email) || !isset($data->access_level)) {
    http_response_code(400);
    echo json_encode(["message" => "Incomplete data. First name, email, and access level are required."]);
    exit();
}

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

// --- Prepare Update Query (excluding password for now) ---
$query = "UPDATE users
          SET first_name = :first_name,
              last_name = :last_name,
              email = :email,
              job_title = :job_title,
              access_level = :access_level
          WHERE id = :id";

$stmt = $db->prepare($query);

// Handle optional fields safely
$last_name = $data->last_name ?? '';
$job_title = $data->job_title ?? '';

// Bind parameters
$stmt->bindParam(':first_name', $data->first_name);
$stmt->bindParam(':last_name', $last_name);
$stmt->bindParam(':email', $data->email);
$stmt->bindParam(':job_title', $job_title);
$stmt->bindParam(':access_level', $data->access_level);
$stmt->bindParam(':id', $userId, PDO::PARAM_INT);

// --- Execute and Respond ---
try {
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["message" => "User updated successfully."]);
        } else {
            // Check if user exists but no changes were made or user not found
            $checkQuery = "SELECT COUNT(*) FROM users WHERE id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $checkStmt->execute();
            if ($checkStmt->fetchColumn() > 0) {
                http_response_code(200); // OK, but no changes needed
                echo json_encode(["message" => "No changes detected for the user."]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(["message" => "User not found."]);
            }
        }
    } else {
        http_response_code(503); // Service Unavailable
        echo json_encode(["message" => "Unable to update user."]);
    }
} catch (PDOException $e) {
    http_response_code(503);
    // Be careful about echoing raw error messages in production
    error_log("Database error: " . $e->getMessage()); // Log error instead
    echo json_encode(["message" => "Database error occurred during update."]);
}
