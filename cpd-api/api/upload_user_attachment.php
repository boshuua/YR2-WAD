<?php
// Load configuration
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';

// Handle CORS
handleCorsPrelight();

// Require POST
requireMethod('POST');

// Require Admin
requireAdmin();

if (!isset($_POST['user_id']) || !isset($_FILES['file'])) {
    sendBadRequest("User ID and File are required.");
}

$userId = intval($_POST['user_id']);
$file = $_FILES['file'];

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendServerError("File upload error code: " . $file['error']);
}

// Security: Check file type/ext (Allow PDF, Doc, Images)
$allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
$fileName = $file['name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedExts)) {
    sendBadRequest("Invalid file type. Allowed: " . implode(', ', $allowedExts));
}

// Generate unique filename and path
// STORE IN: cpd-api/uploads/user_attachments/{user_id}/
$uploadDir = __DIR__ . '/../uploads/user_attachments/' . $userId . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
$destination = $uploadDir . $newFileName;

// Move file
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    sendServerError("Failed to move uploaded file.");
}

// Save to DB
$database = new Database();
$db = $database->getConn();

try {
    // We store relative path for security/portability
    $relativePath = 'uploads/user_attachments/' . $userId . '/' . $newFileName;

    $stmt = $db->prepare("INSERT INTO user_attachments (user_id, file_name, file_path, file_type) VALUES (:uid, :name, :path, :type)");
    $stmt->execute([
        ':uid' => $userId,
        ':name' => $fileName,
        ':path' => $relativePath,
        ':type' => $fileExt
    ]);

    $newId = $db->lastInsertId();

    sendCreated([
        "message" => "File uploaded successfully",
        "attachment" => [
            "id" => $newId,
            "file_name" => $fileName,
            "file_type" => $fileExt,
            "created_at" => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    sendServerError("Database error: " . $e->getMessage());
}
?>