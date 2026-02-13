<?php
// cpd-api/src/Controllers/AuthController.php

namespace App\Controllers;

use PDO;

class AuthController extends BaseController
{

    public function login()
    {
        // Only POST allowed
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        $data = $this->getJsonInput();

        if (!$data || !isset($data->email) || !isset($data->password)) {
            $this->error("Incomplete data.");
            return;
        }

        $email = $data->email;
        $password = $data->password; // In production, sanitize further if needed, but parameter binding handles SQLi

        // 1. Fetch User (and check lockout)
        $query = "SELECT id, first_name, last_name, password, access_level, failed_login_attempts, lockout_until FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check Lockout
            if ($row['lockout_until'] && strtotime($row['lockout_until']) > time()) {
                $wait = ceil((strtotime($row['lockout_until']) - time()) / 60);
                http_response_code(401);
                echo json_encode(["message" => "Account locked due to too many failed attempts. Please try again in $wait minutes."]);
                // Log attempt (optional, or rely on global logger)
                return;
            }

            // Verify Password (pgcrypto)
            // We use the database 'crypt' function comparison logic as before
            $verifyStmt = $this->db->prepare("SELECT (password = crypt(:input_pass, password)) as is_valid FROM users WHERE id = :id");
            $verifyStmt->execute([':input_pass' => $password, ':id' => $row['id']]);
            $res = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            $valid = ($res['is_valid'] === true || $res['is_valid'] === 't');

            if ($valid) {
                // Reset counters
                $update = $this->db->prepare("UPDATE users SET failed_login_attempts = 0, lockout_until = NULL WHERE id = :id");
                $update->execute([':id' => $row['id']]);

                // LOGGING
                log_activity($this->db, $row['id'], $email, 'login_success', 'User logged in successfully.');

                // Generate Tokens (Session based or JWT - sticking to session/cookie for now based on existing frontend)
                // Existing frontend uses PHP Session + Custom 'user' return

                // Start Session if not started
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['access_level'] = $row['access_level'];

                // Return User Data
                $user_arr = array(
                    "id" => $row['id'],
                    "first_name" => $row['first_name'],
                    "last_name" => $row['last_name'],
                    "access_level" => $row['access_level'],
                    "email" => $email
                );

                $this->json($user_arr);

            } else {
                // Increment Failures
                $this->handleLoginFailure($row, $email);
            }

        } else {
            log_activity($this->db, null, $email, 'login_failed', 'Invalid credentials (User not found)');
            $this->error("Login failed. Invalid credentials.", 401);
        }
    }

    private function handleLoginFailure($row, $email)
    {
        $attempts = $row['failed_login_attempts'] + 1;
        $lockout = null;
        $msg = "Invalid credentials.";

        if ($attempts >= 3) {
            $lockout = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            $msg = "Invalid credentials. Account locked for 5 minutes.";
            log_activity($this->db, $row['id'], $email, 'login_lockout', "Account locked after 3 failed attempts.");
        } else {
            log_activity($this->db, $row['id'], $email, 'login_failed', "Failed attempt $attempts/3");
        }

        $update = $this->db->prepare("UPDATE users SET failed_login_attempts = :attempts, lockout_until = :lockout WHERE id = :id");
        $update->execute([':attempts' => $attempts, ':lockout' => $lockout, ':id' => $row['id']]);

        $this->error($msg, 401);
    }

    public function me()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        requireAuth();

        // helpers/auth_helper.php function
        $userData = getCurrentUserData();

        if ($userData) {
            $this->json(['user' => $userData]);
        } else {
            $this->error("User not found.", 404);
        }
    }

    public function csrf()
    {
        // Allow GET or POST
        // if ($_SERVER['REQUEST_METHOD'] !== 'GET') ... 

        $token = getCsrfToken();
        $this->json(['csrfToken' => $token]);
    }
}
