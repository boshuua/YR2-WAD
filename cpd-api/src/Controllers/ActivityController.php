<?php

declare(strict_types=1);

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

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        if ($page < 1) $page = 1;
        if ($limit <= 0) $limit = 50;
        $offset = ($page - 1) * $limit;

        try {
            // Count Total
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM activity_log");
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            // Fetch Data
            $stmt = $this->db->prepare("
                SELECT id, user_id, user_email, action, details, ip_address, timestamp
                FROM activity_log
                ORDER BY timestamp DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'data' => $logs,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'last_page' => ceil($total / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            $this->error("Database error fetching activity log: " . $e->getMessage(), 500);
        }
    }
}
