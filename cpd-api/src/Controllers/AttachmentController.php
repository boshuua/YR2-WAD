<?php
// cpd-api/src/Controllers/AttachmentController.php

namespace App\Controllers;

use PDO;

class AttachmentController extends BaseController
{

    public function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        if (!isset($_POST['user_id']) || !isset($_FILES['file'])) {
            $this->error("User ID and File are required.", 400);
        }

        $userId = (int) $_POST['user_id'];
        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error("File upload error code: " . $file['error'], 500);
        }

        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
        $fileName = $file['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExts)) {
            $this->error("Invalid file type. Allowed: " . implode(', ', $allowedExts), 400);
        }

        // Define upload directory relative to API root (or project root)
        // Original code: __DIR__ . '/../uploads/user_attachments/' . $userId . '/';
        $uploadDir = __DIR__ . '/../../uploads/user_attachments/' . $userId . '/';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $this->error("Failed to create upload directory.", 500);
            }
        }

        $newFileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        $destination = $uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->error("Failed to move uploaded file.", 500);
        }

        try {
            // Rel path for DB
            $relativePath = 'uploads/user_attachments/' . $userId . '/' . $newFileName;

            $stmt = $this->db->prepare("INSERT INTO user_attachments (user_id, file_name, file_path, file_type) VALUES (:uid, :name, :path, :type)");
            $stmt->execute([
                ':uid' => $userId,
                ':name' => $fileName,
                ':path' => $relativePath,
                ':type' => $fileExt
            ]);

            $newId = $this->db->lastInsertId();

            $this->json([
                "message" => "File uploaded successfully",
                "attachment" => [
                    "id" => $newId,
                    "file_name" => $fileName,
                    "file_type" => $fileExt,
                    "created_at" => date('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $attId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($attId <= 0) {
            $this->error("Attachment ID is required.", 400);
        }

        try {
            $stmt = $this->db->prepare("SELECT file_path FROM user_attachments WHERE id = :id");
            $stmt->execute([':id' => $attId]);
            $att = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$att) {
                $this->error("Attachment not found.", 404);
            }

            $filePath = __DIR__ . '/../../' . $att['file_path'];

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $del = $this->db->prepare("DELETE FROM user_attachments WHERE id = :id");
            $del->execute([':id' => $attId]);

            $this->json(["message" => "Attachment deleted successfully."]);

        } catch (\Exception $e) {
            $this->error("Error deleting attachment: " . $e->getMessage(), 500);
        }
    }

    public function view()
    {
        // Simple GET - output file content directly (no JSON)

        session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            exit('Unauthorized');
        }

        // Allow Admin or Own User?
        // Original code: Only Admin check.
        if ($_SESSION['access_level'] !== 'admin') {
            // Future: Allow own user
            http_response_code(403);
            exit('Forbidden');
        }

        $attId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($attId <= 0) {
            http_response_code(400);
            exit('ID required');
        }

        try {
            $stmt = $this->db->prepare("SELECT file_path, file_type, file_name FROM user_attachments WHERE id = :id");
            $stmt->execute([':id' => $attId]);
            $att = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$att) {
                http_response_code(404);
                exit('File not found');
            }

            $filePath = __DIR__ . '/../../' . $att['file_path'];

            if (!file_exists($filePath)) {
                http_response_code(404);
                exit('File on disk not found');
            }

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

        } catch (\Exception $e) {
            http_response_code(500);
            exit('Server Error');
        }
    }
}
