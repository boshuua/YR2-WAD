<?php

declare(strict_types=1);

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
        $password = $data->password;

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Anti-bruteforce check via session FIRST
        if (isset($_SESSION['lockout_until']) && $_SESSION['lockout_until'] > time()) {
            $wait = ceil(($_SESSION['lockout_until'] - time()) / 60);
            $this->error("Account locked due to too many failed attempts. Please try again in $wait minutes.", 401);
            return;
        }

        // 1. Fetch User (and check DB lockout)
        $query = "SELECT id, first_name, last_name, password, access_level, failed_login_attempts, lockout_until, requires_password_reset FROM users WHERE email = :email LIMIT 1";
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
                return;
            }

            // Verify Password (pgcrypto)
            $verifyStmt = $this->db->prepare("SELECT (password = crypt(:input_pass, password)) as is_valid FROM users WHERE id = :id");
            $verifyStmt->execute([':input_pass' => $password, ':id' => $row['id']]);
            $res = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            $valid = ($res['is_valid'] === true || $res['is_valid'] === 't');

            if ($valid) {
                // Check if password reset is required
                if ($row['requires_password_reset'] === true || $row['requires_password_reset'] === 't' || $row['requires_password_reset'] == 1) {
                    $this->json([
                        'message' => 'Password reset required.',
                        'reset_required' => true,
                        'user_id' => $row['id'],
                        'email' => $email
                    ], 403);
                    return;
                }

                // Reset counters
                $update = $this->db->prepare("UPDATE users SET failed_login_attempts = 0, lockout_until = NULL WHERE id = :id");
                $update->execute([':id' => $row['id']]);

                log_activity($this->db, $row['id'], $email, 'login_success', 'User logged in successfully.');

                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['access_level'] = $row['access_level'];

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
                $this->handleLoginFailure($row, $email);
            }

        } else {
            $_SESSION['failed_attempts'] = isset($_SESSION['failed_attempts']) ? $_SESSION['failed_attempts'] + 1 : 1;

            if ($_SESSION['failed_attempts'] >= 3) {
                $_SESSION['lockout_until'] = time() + (5 * 60);
                log_activity($this->db, null, $email, 'login_lockout', 'Session locked IP due to 3 failed attempts.');
                $this->error("Account locked due to too many failed attempts. Please try again in 5 minutes.", 401);
                return;
            }

            log_activity($this->db, null, $email, 'login_failed', 'Invalid credentials - attempt ' . $_SESSION['failed_attempts']);
            $this->error("Login failed. Invalid credentials.", 401);
        }
    }

    private function handleLoginFailure($row, $email)
    {
        $attempts = $row['failed_login_attempts'] + 1;
        $lockout = null;
        $msg = "Login failed. Invalid credentials.";

        if ($attempts >= 3) {
            $lockout = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            $msg = "Account locked due to too many failed attempts. Please try again in 5 minutes.";
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
        $userData = getCurrentUserData();
        if ($userData) {
            $this->json(['user' => $userData]);
        } else {
            $this->error("User not found.", 404);
        }
    }

    public function csrf()
    {
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

        try {
            // 1. Check if user exists
            $stmt = $this->db->prepare("SELECT id, first_name, last_name FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Generic response to prevent email enumeration
            $genericMsg = "If your email is registered in our system, a temporary password has been sent to you.";

            if (!$user) {
                $this->json(["message" => $genericMsg]);
                return;
            }

            // 2. Generate secure temporary password
            $tempPassword = bin2hex(random_bytes(4)); // 8 chars

            // 3. Update User: Set temp password and reset flag
            $update = $this->db->prepare("UPDATE users SET password = crypt(:pass, gen_salt('bf')), requires_password_reset = TRUE WHERE id = :id");
            $update->execute([':pass' => $tempPassword, ':id' => $user['id']]);

            // 4. Send Email to User (Industry Styled)
            $siteName = getSetting('site_name', 'CPD Portal');
            $frontendUrl = $_ENV['CORS_ALLOWED_ORIGIN'] ?? 'http://localhost:4200';
            $loginUrl = rtrim($frontendUrl, '/') . '/login';

            $subject = "Security Notice: Temporary Access Credentials - {$siteName}";
            $userBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
                    <div style='background-color: #1e293b; color: #ffffff; padding: 25px; text-align: center;'>
                        <h1 style='margin: 0; font-size: 24px; letter-spacing: 1px;'>{$siteName}</h1>
                        <p style='margin: 5px 0 0; opacity: 0.8; font-size: 14px;'>Automotive Compliance & Professional Development</p>
                    </div>
                    <div style='padding: 30px; color: #334155; line-height: 1.6;'>
                        <h2 style='color: #0f172a; margin-top: 0; font-size: 20px;'>Password Reset Initiated</h2>
                        <p>Hello {$user['first_name']},</p>
                        <p>As requested, a temporary access key has been generated for your account. This key allows you to access the portal and establish a new permanent password.</p>
                        
                        <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 6px; margin: 25px 0; text-align: center;'>
                            <p style='margin: 0 0 10px; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold;'>Temporary Access Password</p>
                            <span style='font-family: \"Courier New\", monospace; font-size: 28px; font-weight: bold; color: #2563eb; letter-spacing: 2px;'>{$tempPassword}</span>
                        </div>

                        <p><strong>Required Action:</strong></p>
                        <p>Upon your next login, the system will require you to update your password to maintain account security.</p>

                        <div style='text-align: center; margin: 35px 0;'>
                            <a href='{$loginUrl}' style='background-color: #2563eb; color: #ffffff; padding: 14px 35px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>Go to Login Page</a>
                        </div>

                        <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                        <p style='font-size: 12px; color: #94a3b8;'><strong>Security Protocol:</strong> This is an automated security notification. If you did not initiate this request, please contact your training coordinator or IT department immediately. For your protection, this temporary password is for single-use only.</p>
                    </div>
                    <div style='background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                        <p style='margin: 0;'>&copy; " . date('Y') . " {$siteName} | Technical Support Division</p>
                    </div>
                </div>
            ";
            sendEmail($this->db, $email, $subject, $userBody);

            // 5. Notify Admin (Simple Alert)
            $adminEmail = 'admin@ws369808-wad.remote.ac';
            $adminSubject = "Alert: Password Reset Performed - {$user['first_name']} {$user['last_name']}";
            $adminBody = "
                <p>The password for <strong>{$user['first_name']} {$user['last_name']}</strong> ({$email}) has been reset via the self-service portal.</p>
                <p>A temporary password was issued, and the user will be required to update it upon their next login.</p>
                <p>Timestamp: " . date('Y-m-d H:i:s') . "</p>
            ";
            sendEmail($this->db, $adminEmail, $adminSubject, $adminBody);

            log_activity($this->db, $user['id'], $email, 'password_reset_auto', 'User performed self-service password reset. Temp password emailed.');

            $this->json(["message" => $genericMsg]);
        } catch (\Exception $e) {
            error_log("Forgot Password Error: " . $e->getMessage());
            $this->error("An error occurred while processing your request.", 500);
        }
    }

    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        $data = $this->getJsonInput();

        if (!$data || !isset($data->user_id) || !isset($data->temp_password) || !isset($data->new_password)) {
            $this->error("Missing required fields.");
        }

        $userId = (int) $data->user_id;
        $tempPass = $data->temp_password;
        $newPass = $data->new_password;

        try {
            $stmt = $this->db->prepare("SELECT id, email, first_name, last_name, access_level, requires_password_reset FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->error("User not found.", 404);
            }

            if (!($row['requires_password_reset'] === true || $row['requires_password_reset'] === 't' || $row['requires_password_reset'] == 1)) {
                $this->error("Password reset not required for this user.", 400);
            }

            $verifyStmt = $this->db->prepare("SELECT (password = crypt(:input_pass, password)) as is_valid FROM users WHERE id = :id");
            $verifyStmt->execute([':input_pass' => $tempPass, ':id' => $userId]);
            $res = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            $valid = ($res['is_valid'] === true || $res['is_valid'] === 't');

            if (!$valid) {
                $this->error("Invalid temporary password.", 401);
            }

            $update = $this->db->prepare("UPDATE users SET password = crypt(:pass, gen_salt('bf')), requires_password_reset = FALSE, failed_login_attempts = 0, lockout_until = NULL WHERE id = :id");
            $update->execute([':pass' => $newPass, ':id' => $userId]);

            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['access_level'] = $row['access_level'];

            log_activity($this->db, $userId, $row['email'], 'password_reset_success', 'User successfully updated password and logged in.');

            $this->json([
                'message' => 'Password reset successful.',
                'user' => [
                    "id" => $row['id'],
                    "first_name" => $row['first_name'],
                    "last_name" => $row['last_name'],
                    "access_level" => $row['access_level'],
                    "email" => $row['email']
                ]
            ]);

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }
}
