<?php
// cpd-api/src/Controllers/UserController.php

namespace App\Controllers;

use PDO;

require_once __DIR__ . '/../../helpers/email_helper.php';

class UserController extends BaseController
{

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $query = "SELECT id, first_name, last_name, email, access_level, created_at, failed_login_attempts, lockout_until FROM users ORDER BY id ASC";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "Viewed Users", "User list retrieved.");

            $this->json($users);

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $data = $this->getJsonInput();
        if (!isset($data->first_name) || !isset($data->last_name) || !isset($data->email) || !isset($data->password)) {
            $this->error("Missing required fields.");
        }

        $fname = sanitizeString($data->first_name);
        $lname = sanitizeString($data->last_name);
        $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
        $password = $data->password;
        // Use provided access level or fall back to the global default configured in Settings
        $defaultAccess = getSetting('default_access_level', 'user');
        $access = $data->access_level ?? $defaultAccess;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email format.");
        }

        // Check if email exists
        $check = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->rowCount() > 0) {
            $this->error("Email already exists.", 409); // Conflict
        }

        // Pgcrypto insert
        $query = "INSERT INTO users (first_name, last_name, email, password, access_level)
                  VALUES (:fname, :lname, :email, crypt(:pass, gen_salt('bf')), :access)";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':fname', $fname);
            $stmt->bindParam(':lname', $lname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':pass', $password);
            $stmt->bindParam(':access', $access);

            if ($stmt->execute()) {
                log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "User Created", "Created user: {$email}");

                // Send Welcome Email (only if the global setting is enabled)
                if (getSetting('enable_welcome_emails', 'true') === 'true') {
                    $siteName = getSetting('site_name', 'CPD Portal');
                    $frontendUrl = $_ENV['CORS_ALLOWED_ORIGIN'] ?? 'http://localhost:4200';
                    $loginUrl = rtrim($frontendUrl, '/') . '/login';

                    $subject = "Welcome to {$siteName}";
                    $body = "
                        <h2>Welcome, {$fname}!</h2>
                        <p>An administrator has created an account for you on {$siteName}.</p>
                        <p><strong>URL:</strong> <a href='{$loginUrl}'>{$loginUrl}</a><br>
                        <strong>Email:</strong> {$email}<br>
                        <strong>Temporary Password:</strong> {$password}</p>
                        <p style='color: red;'><strong>Important:</strong> We strongly recommend changing your password immediately after your first login.</p>
                    ";
                    sendEmail($this->db, $email, $subject, $body);
                }

                $this->json(["message" => "User created successfully."], 201);
            } else {
                throw new \Exception("Insert failed");
            }

        } catch (\Exception $e) {
            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "User Creation Failed", "Error: " . $e->getMessage());
            $this->error("Unable to create user: " . $e->getMessage(), 500);
        }
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $data = $this->getJsonInput();
        if (!isset($data->id) || !isset($data->first_name) || !isset($data->last_name) || !isset($data->email) || !isset($data->access_level)) {
            $this->error("Missing required fields.");
        }

        $id = (int) $data->id;
        $fname = sanitizeString($data->first_name);
        $lname = sanitizeString($data->last_name);
        $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
        $access = $data->access_level;

        // Check email uniqueness (excluding self)
        $check = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check->execute([':email' => $email, ':id' => $id]);
        if ($check->rowCount() > 0) {
            $this->error("Email already exists.", 409);
        }

        $query = "UPDATE users SET first_name = :fname, last_name = :lname, email = :email, access_level = :access WHERE id = :id";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':fname' => $fname,
                ':lname' => $lname,
                ':email' => $email,
                ':access' => $access,
                ':id' => $id
            ]);

            if ($stmt->rowCount() > 0) {
                log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "User Updated", "Updated user ID: {$id}");
                $this->json(["message" => "User updated successfully."]);
            } else {
                // Check existence
                $exists = $this->db->prepare("SELECT id FROM users WHERE id = :id");
                $exists->execute([':id' => $id]);
                if ($exists->rowCount() === 0) {
                    $this->error("User not found.", 404);
                }
                $this->json(["message" => "No changes made."]);
            }

        } catch (\Exception $e) {
            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "User Update Failed", "Error: " . $e->getMessage());
            $this->error("Unable to update user: " . $e->getMessage(), 500);
        }
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($userId <= 0) {
            $this->error("Invalid user ID.");
        }

        // Prevent deleting self?
        if ($userId === getCurrentUserId()) {
            $this->error("Cannot delete your own account.", 400);
        }

        try {
            // Retrieve email for log
            $email = "Unknown";
            $get = $this->db->prepare("SELECT email FROM users WHERE id = :id");
            $get->execute([':id' => $userId]);
            if ($r = $get->fetch(PDO::FETCH_ASSOC)) {
                $email = $r['email'];
            }

            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);

            if ($stmt->rowCount() > 0) {
                log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "User Deleted", "Deleted user: {$email} (ID: $userId)");
                $this->json(["message" => "User deleted successfully."]);
            } else {
                $this->error("User not found.", 404);
            }

        } catch (\Exception $e) {
            log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "User Deletion Failed", "Error: " . $e->getMessage());
            // Check foreign key constraints
            if (strpos($e->getMessage(), 'constraint') !== false) {
                $this->error("Cannot delete user because they have related records (enrolments, etc.).", 400);
            }
            $this->error("Unable to delete user.", 500);
        }
    }

    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Usually POST or PUT
            $this->error("Method Not Allowed", 405);
        }

        requireAdmin();

        $data = $this->getJsonInput();
        if (!isset($data->user_id) || !isset($data->new_password)) {
            $this->error("Missing user_id or new_password.");
        }

        $userId = (int) $data->user_id;
        $newPass = $data->new_password;

        try {
            $stmt = $this->db->prepare("UPDATE users SET password = crypt(:pass, gen_salt('bf')) WHERE id = :id");
            $stmt->execute([':pass' => $newPass, ':id' => $userId]);

            if ($stmt->rowCount() > 0) {
                log_activity($this->db, getCurrentUserId(), getCurrentUserEmail(), "Password Reset", "Reset password for user ID: $userId");
                $this->json(["message" => "Password updated successfully."]);
            } else {
                $this->error("User not found or password identical.", 404);
            }

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage(), 500);
        }
    }
}
