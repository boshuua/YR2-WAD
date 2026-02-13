<?php
// cpd-api/src/Controllers/ActivityController.php

namespace App\Controllers;

use PDO;

class ActivityController extends BaseController
{

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        if ($limit <= 0)
            $limit = 50;

        try {
            $stmt = $this->db->prepare("
                SELECT id, user_id, user_email, action, details, ip_address, timestamp
                FROM activity_log
                ORDER BY timestamp DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json($logs);

        } catch (\Exception $e) {
            $this->error("Database error fetching activity log: " . $e->getMessage(), 500);
        }
    }
}
