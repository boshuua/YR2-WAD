<?php
// cpd-api/helpers/log_helper.php

if (!function_exists('log_activity')) { // Prevent redefinition errors
    function log_activity(PDO $db, ?int $userId, ?string $userEmail, string $action, ?string $details = null): void {
        // Get IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        // Basic check for common proxy headers (adjust if needed)
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        }

        try {
            $query = "INSERT INTO activity_log (user_id, user_email, action, details, ip_address)
                      VALUES (:user_id, :user_email, :action, :details, :ip_address)";
            $stmt = $db->prepare($query);

            // Bind parameters carefully, handling potential NULLs
            $stmt->bindParam(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':user_email', $userEmail, $userEmail === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':details', $details, $details === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':ip_address', $ipAddress);

            $stmt->execute();
        } catch (PDOException $e) {
            // Log the error internally, don't show details to the user
            error_log("Error in log_activity for action {$action}: " . $e->getMessage());
        }
    }
}
?>