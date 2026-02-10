<?php
// Load configuration
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';

// Handle CORS
// Simple CORS for GET image/pdf resources
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
}

// Require Admin (or own user)
// For simplicity, we reuse the session check, but for an img src/link, 
// the browser might not send the custom header 'X-CSRF-Token'.
// However, it WILL send the cookie.
// So we just need to verify the session.

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Verify Admin (Optional: or allow user to view own files)
if ($_SESSION['access_level'] !== 'admin') {
    // Check if user is viewing their own file... 
    // For now, strict admin.
    http_response_code(403);
    exit('Forbidden');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('ID required');
}

$attId = intval($_GET['id']);

$database = new Database();
$db = $database->getConn();

try {
    $stmt = $db->prepare("SELECT file_path, file_type, file_name FROM user_attachments WHERE id = :id");
    $stmt->execute([':id' => $attId]);
    $att = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$att) {
        http_response_code(404);
        exit('File not found');
    }

    $filePath = __DIR__ . '/../' . $att['file_path'];

    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('File on disk not found');
    }

    // Determine MIME type
    $mimeType = 'application/octet-stream';
    switch (strtolower($att['file_type'])) {
        case 'jpg':
            $mimeType = 'image/jpeg';
            break;
        case 'jpeg':
            $mimeType = 'image/jpeg';
            break;
        case 'png':
            $mimeType = 'image/png';
            break;
        case 'gif':
            $mimeType = 'image/gif';
            break;
        case 'pdf':
            $mimeType = 'application/pdf';
            break;
        case 'txt':
            $mimeType = 'text/plain';
            break;
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: inline; filename="' . $att['file_name'] . '"');
    header('Content-Length: ' . filesize($filePath));

    readfile($filePath);

} catch (Exception $e) {
    http_response_code(500);
    exit('Server Error');
}
?>