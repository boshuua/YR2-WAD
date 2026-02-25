<?php
// cpd-api/src/Controllers/SettingsController.php

namespace App\Controllers;

use PDO;

class SettingsController extends BaseController
{
    /**
     * Get all settings (Admin) or public settings (Anyone/User)
     */
    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        // We check if the user is an admin. If not, they only get safe public settings.
        session_start();
        $isAdmin = isset($_SESSION['access_level']) && $_SESSION['access_level'] === 'admin';

        try {
            if ($isAdmin) {
                // Return all settings
                $stmt = $this->db->query("SELECT setting_key, setting_value, description FROM system_settings");
            } else {
                // Return only safe public settings (e.g., site_name, maintenance_mode, support_email)
                $stmt = $this->db->query("SELECT setting_key, setting_value, description FROM system_settings WHERE setting_key IN ('site_name', 'maintenance_mode', 'support_email')");
            }

            $settingsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format into a key-value object
            $settings = [];
            foreach ($settingsData as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            // Also return the raw array for the admin dashboard if needed, but key-value is easier for the frontend.
            $this->json(['settings' => $settings, 'raw' => $isAdmin ? $settingsData : null]);

        } catch (\Exception $e) {
            $this->error("Failed to retrieve settings: " . $e->getMessage(), 500);
        }
    }

    /**
     * Update settings (Admin Only)
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $data = $this->getJsonInput();

        if (!$data) {
            $this->error("Invalid settings data.", 400);
            return;
        }

        try {
            $this->db->beginTransaction();

            // Prepare update statement (PostgreSQL "ON CONFLICT" syntax if using PG, or MySQL "ON DUPLICATE KEY UPDATE" if using MySQL)
            // Assuming PostgreSQL based on earlier 'crypt' usage for passwords, though ON CONFLICT is standard PG.
            $stmt = $this->db->prepare("
                INSERT INTO system_settings (setting_key, setting_value)
                VALUES (:key, :value)
                ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = CURRENT_TIMESTAMP
            ");

            foreach ($data as $key => $value) {
                // Only process primitive values (strings, numbers, booleans)
                if (is_scalar($value)) {
                    // Convert booleans to strings explicitly
                    if (is_bool($value)) {
                        $strValue = $value ? 'true' : 'false';
                    } else {
                        $strValue = (string) $value;
                    }

                    $stmt->execute([
                        ':key' => $key,
                        ':value' => $strValue
                    ]);
                }
            }

            $this->db->commit();

            // Log the action
            log_activity($this->db, $_SESSION['user_id'], null, 'settings_updated', 'Admin updated global platform settings.');

            $this->json(["message" => "Settings updated successfully."]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->error("Failed to update settings: " . $e->getMessage(), 500);
        }
    }
}
