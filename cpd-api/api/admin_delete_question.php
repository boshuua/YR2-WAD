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
$questionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- Validate Input ---
if ($questionId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid question ID provided."]);
    exit();
}

// --- Database Connection ---
$database = new Database();
$db = $database->getConn();

// Fetch question text for better logging
$questionTextToLog = 'Unknown (Question not found)';
try {
    $fetchTextQuery = "SELECT question_text FROM questions WHERE id = :id";
    $fetchStmt = $db->prepare($fetchTextQuery);
    $fetchStmt->bindParam(':id', $questionId, PDO::PARAM_INT);
    $fetchStmt->execute();
    if ($fetchStmt->rowCount() > 0) {
        $questionTextToLog = $fetchStmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch question text before delete: " . $e->getMessage());
}

// --- Prepare and Execute Delete Query ---
try {
    $db->beginTransaction();

    // Delete associated options first (due to foreign key constraint if ON DELETE CASCADE is not set)
    $deleteOptionsQuery = "DELETE FROM question_options WHERE question_id = :question_id";
    $deleteOptionsStmt = $db->prepare($deleteOptionsQuery);
    $deleteOptionsStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
    $deleteOptionsStmt->execute();

    // Delete the question
    $query = "DELETE FROM questions WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(":id", $questionId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            $db->commit();
            http_response_code(200);
            echo json_encode(["message" => "Question deleted successfully."]);
            log_activity($db, null, null, 'Question Deleted', "Question ID: {$questionId}, Text: {$questionTextToLog}");
        } else {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(["message" => "Question not found or already deleted."]);
            log_activity($db, null, null, 'Question Deletion Failed', "Question ID: {$questionId} not found.");
        }
    } else {
        $db->rollBack();
        http_response_code(503);
        echo json_encode(["message" => "Unable to delete question."]);
        log_activity($db, null, null, 'Question Deletion Failed', "Question ID: {$questionId}, DB execution error.");
    }
} catch (PDOException $e) {
    $db->rollBack();
    http_response_code(503);
    error_log("Database error during question delete: " . $e->getMessage());
    echo json_encode(["message" => "Database error occurred during deletion."]);
    log_activity($db, null, null, 'Question Deletion Failed', "Question ID: {$questionId}, Error: " . $e->getMessage());
}

?>