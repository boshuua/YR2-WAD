<?php
// Load configuration
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';

// Handle CORS
handleCorsPrelight();

// Require DELETE
requireMethod('DELETE');

// Require Admin
requireAdmin();

if (!isset($_GET['id'])) {
    sendBadRequest("Attachment ID is required.");
}

$attId = intval($_GET['id']);

$database = new Database();
$db = $database->getConn();

try {
    // Get file path first
    $stmt = $db->prepare("SELECT file_path FROM user_attachments WHERE id = :id");
    $stmt->execute([':id' => $attId]);
    $att = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$att) {
        sendNotFound("Attachment not found.");
    }

    $filePath = __DIR__ . '/../' . $att['file_path'];

    // Delete file from disk
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Delete from DB
    $delStmt = $db->prepare("DELETE FROM user_attachments WHERE id = :id");
    $delStmt->execute([':id' => $attId]);

    sendOk(["message" => "Attachment deleted successfully."]);

} catch (Exception $e) {
    sendServerError("Error deleting attachment: " . $e->getMessage());
}
?>