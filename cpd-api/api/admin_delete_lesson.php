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
$lessonId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- Validate Input ---
if ($lessonId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid lesson ID provided."]);
    exit();
}

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

// Fetch lesson title for better logging
$lessonTitleToLog = 'Unknown (Lesson not found)';
try {
    $fetchTitleQuery = "SELECT title FROM lessons WHERE id = :id";
    $fetchStmt = $db->prepare($fetchTitleQuery);
    $fetchStmt->bindParam(':id', $lessonId, PDO::PARAM_INT);
    $fetchStmt->execute();
    if ($fetchStmt->rowCount() > 0) {
        $lessonTitleToLog = $fetchStmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch lesson title before delete: " . $e->getMessage());
}

// --- Prepare and Execute Delete Query ---
try {
    $query = "DELETE FROM lessons WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(":id", $lessonId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["message" => "Lesson deleted successfully."]);
            log_activity($db, null, null, 'Lesson Deleted', "Lesson ID: {$lessonId}, Title: {$lessonTitleToLog}");
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Lesson not found or already deleted."]);
            log_activity($db, null, null, 'Lesson Deletion Failed', "Lesson ID: {$lessonId} not found.");
        }
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Unable to delete lesson."]);
        log_activity($db, null, null, 'Lesson Deletion Failed', "Lesson ID: {$lessonId}, DB execution error.");
    }
} catch (PDOException $e) {
    http_response_code(503);
    error_log("Database error during lesson delete: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred during deletion."]);
    log_activity($db, null, null, 'Lesson Deletion Failed', "Lesson ID: {$lessonId}, Error: " . $e->getMessage());
}

?>