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
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed. Use DELETE."]);
    exit();
}

// --- Get Input Data ---
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- Validate Input ---
if ($courseId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid course ID provided."]);
    exit();
}

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

// Get admin details from session for logging
$adminUserId = $_SESSION['user_id'] ?? null;
$adminUserEmail = $_SESSION['user_email'] ?? 'Unknown Admin';

// Fetch course title for better logging
$courseTitleToLog = 'Unknown (Course not found)';
try {
    $fetchTitleQuery = "SELECT title FROM courses WHERE id = :id";
    $fetchStmt = $db->prepare($fetchTitleQuery);
    $fetchStmt->bindParam(':id', $courseId, PDO::PARAM_INT);
    $fetchStmt->execute();
    if ($fetchStmt->rowCount() > 0) {
        $courseTitleToLog = $fetchStmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch course title before delete: " . $e->getMessage());
}

// --- Prepare and Execute Delete Query ---
try {
    $query = "DELETE FROM courses WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(":id", $courseId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["message" => "Course deleted successfully."]);
            log_activity($db, $adminUserId, $adminUserEmail, 'Course Deleted', "Course ID: {$courseId}, Title: {$courseTitleToLog}");
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Course not found or already deleted."]);
            log_activity($db, $adminUserId, $adminUserEmail, 'Course Deletion Failed', "Course ID: {$courseId} not found.");
        }
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to delete course."]);
        log_activity($db, $adminUserId, $adminUserEmail, 'Course Deletion Failed', "Course ID: {$courseId}, DB execution error.");
    }
} catch (PDOException $e) {
    http_response_code(503);
    error_log("Database error during course delete: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred during deletion."]);
    log_activity($db, $adminUserId, $adminUserEmail, 'Course Deletion Failed', "Course ID: {$courseId}, Error: " . $e->getMessage());
}

?>