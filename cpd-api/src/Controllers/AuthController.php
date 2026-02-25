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

                $this->json([
                    'message' => 'Login successful',
                    'user' => $user_arr
                ]);

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

    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        require_once __DIR__ . '/../../helpers/email_helper.php';

        $data = $this->getJsonInput();

        if (!$data || !isset($data->email)) {
            $this->error("Email address is required.", 400);
            return;
        }

        $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email format.", 400);
            return;
        }

        try {
            // 1. Check if user exists
            $stmt = $this->db->prepare("SELECT id, first_name, last_name FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Respond with success regardless of user existence to prevent enumeration
            if (!$user) {
                $this->json(["message" => "If your email is registered in our system, a password reset request has been sent to the administrator."]);
                return;
            }

            // 2. Clear existing unused tokens
            $this->db->prepare("DELETE FROM password_resets WHERE user_id = :user_id")->execute([':user_id' => $user['id']]);

            // 3. Generate token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // 4. Save token
            $insert = $this->db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
            $insert->execute([
                ':user_id' => $user['id'],
                ':token' => $token,
                ':expires_at' => $expiresAt
            ]);

            // 5. Send Email to Admin
            $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = $scheme . '://' . $host;
            if (empty($host)) {
                $baseUrl = "http://localhost:8000"; // fallback
            }

            $approveUrl = $baseUrl . "/api/approve_reset.php?token=" . $token;

            $adminEmail = 'admin@ws369808-wad.remote.ac';

            $subject = "Password Reset Request: " . $user['first_name'] . " " . $user['last_name'];
            $body = "
                <h2>Password Reset Request</h2>
                <p>User <strong>{$user['first_name']} {$user['last_name']}</strong> ({$email}) has requested a password reset.</p>
                <p>To approve this request and generate a new password for the user, please click the link below:</p>
                <p><a href='{$approveUrl}' style='padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Approve Password Reset</a></p>
                <p><em>If you ignore this email, the request will expire in 24 hours and the user's password will not be changed.</em></p>
            ";

            sendEmail($this->db, $adminEmail, $subject, $body);

            log_activity($this->db, $user['id'], $email, 'password_reset_requested', 'User requested a password reset. Admin notified.');

            $this->json(["message" => "If your email is registered in our system, a password reset request has been sent to the administrator."]);
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine(), 500);
            error_log("Forgot Password Error: " . $e->getMessage());
        }
    }

    public function approveReset()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        require_once __DIR__ . '/../../helpers/email_helper.php';

        $token = $_GET['token'] ?? null;

        if (!$token) {
            echo "<h1>Error</h1><p>Invalid or missing token.</p>";
            exit;
        }

        try {
            // 1. Validate Token
            $stmt = $this->db->prepare("
                SELECT pr.id as reset_id, pr.expires_at, u.id as user_id, u.email, u.first_name 
                FROM password_resets pr
                JOIN users u ON pr.user_id = u.id
                WHERE pr.token = :token
            ");
            $stmt->execute([':token' => $token]);
            $resetData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resetData) {
                echo "<h1>Error</h1><p>Invalid token. It may have already been used.</p>";
                exit;
            }

            // Check expiration
            if (strtotime($resetData['expires_at']) < time()) {
                $this->db->prepare("DELETE FROM password_resets WHERE id = :id")->execute([':id' => $resetData['reset_id']]);
                echo "<h1>Error</h1><p>This password reset request has expired.</p>";
                exit;
            }

            // 2. Generate a new secure temporary password
            $tempPassword = bin2hex(random_bytes(4));

            // 3. Update the user's password using pgcrypto
            $updateStmt = $this->db->prepare("UPDATE users SET password = crypt(:pass, gen_salt('bf')) WHERE id = :id");
            $updateStmt->execute([
                ':pass' => $tempPassword,
                ':id' => $resetData['user_id']
            ]);

            // 4. Delete the used token
            $this->db->prepare("DELETE FROM password_resets WHERE id = :id")->execute([':id' => $resetData['reset_id']]);

            // 5. Send Email to the User with the new password
            $frontendUrl = $_ENV['CORS_ALLOWED_ORIGIN'] ?? 'http://localhost:4200';
            $loginUrl = rtrim($frontendUrl, '/') . '/login';

            $subject = "Your Password Has Been Reset";
            $body = "
                <h2>Password Reset Approved</h2>
                <p>Hi {$resetData['first_name']},</p>
                <p>Your administrator has approved your password reset request.</p>
                <p>Your new temporary password is: <strong>{$tempPassword}</strong></p>
                <p>Please <a href='{$loginUrl}'>click here to log in</a>.</p>
                <p style='color: red;'><strong>Important:</strong> We strongly recommend changing your password immediately after logging in.</p>
            ";

            sendEmail($this->db, $resetData['email'], $subject, $body);

            log_activity($this->db, $resetData['user_id'], $resetData['email'], 'password_reset_approved', 'Admin approved password reset. New password emailed to user.');

            // 6. Show success message to the Admin
            echo "
            <html>
            <body style='font-family: sans-serif; text-align: center; padding: 50px;'>
                <h1 style='color: #28a745;'>Success!</h1>
                <p>The password reset request for <strong>{$resetData['email']}</strong> has been approved.</p>
                <p>A new secure password has been generated and emailed to the user.</p>
                <p>You may now close this window.</p>
            </body>
            </html>
            ";
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo "<h1>Error</h1><p>A server error occurred while processing this request.</p>";
            error_log("Approve Reset Error: " . $e->getMessage());
            exit;
        }
    }
}
