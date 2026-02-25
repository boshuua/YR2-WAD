<?php
/**
 * Authentication Helper Functions
 *
 * Provides session management and authentication utilities
 */

/**
 * Start session if not already started
 */
function ensureSessionStarted()
{
    if (session_status() === PHP_SESSION_NONE) {

        // Detect HTTPS (important for SameSite=None cookies)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

        // In production cross-site requests, cookies must be SameSite=None; Secure
        // For local HTTP dev, Secure cookies won't work, so fall back to Lax.
        $sameSite = $isHttps ? 'None' : 'Lax';
        $secure = $isHttps;

        // Must be set BEFORE session_start()
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',      // default: current host (api subdomain)
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        session_start();
    }
}

/**
 * Check if user is authenticated
 *
 * @return bool
 */
function isAuthenticated()
{
    ensureSessionStarted();
    return isset($_SESSION['user_id']) && isset($_SESSION['access_level']);
}

/**
 * Get current user's ID from session
 *
 * @return int|null
 */
function getCurrentUserId()
{
    ensureSessionStarted();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's access level from session
 *
 * @return string|null
 */
function getCurrentUserAccessLevel()
{
    ensureSessionStarted();
    return $_SESSION['access_level'] ?? null;
}

/**
 * Get current user's email from session
 *
 * @return string|null
 */
function getCurrentUserEmail()
{
    ensureSessionStarted();
    return $_SESSION['email'] ?? null;
}

/**
 * Check if current user is admin
 *
 * @return bool
 */
function isAdmin()
{
    return getCurrentUserAccessLevel() === 'admin';
}

/**
 * Get a setting value from the system_settings table
 * @param string $key
 * @param string $default
 * @return string
 */
function getSetting($key, $default = '')
{
    global $pdo;
    if (!$pdo)
        return $default;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $row['setting_value'] : $default;
    } catch (\Exception $e) {
        return $default;
    }
}

/**
 * Require authentication - exits with 401 if not authenticated
 * Also enforces maintenance mode lockout.
 *
 * @param string $message Custom error message
 */
function requireAuth($message = "Authentication required.")
{
    if (!isAuthenticated()) {
        sendJsonResponse(["message" => $message], 401);
    }

    // Maintenance mode: block non-admins
    if (!isAdmin()) {
        $maintenance = getSetting('maintenance_mode', 'false');
        if ($maintenance === 'true') {
            sendJsonResponse([
                "message" => "The platform is currently under maintenance. Please try again later.",
                "maintenance" => true
            ], 503);
        }
    }
}

/**
 * Require admin privileges - exits with 403 if not admin
 *
 * @param string $message Custom error message
 */
function requireAdmin($message = "Access Denied: Admin privileges required.")
{
    requireAuth();

    if (!isAdmin()) {
        sendJsonResponse(["message" => $message], 403);
    }
}

/**
 * Set user session data after successful login
 *
 * @param int $userId
 * @param string $email
 * @param string $firstName
 * @param string $lastName
 * @param string $accessLevel
 */
function setUserSession($userId, $email, $firstName, $lastName, $accessLevel)
{
    ensureSessionStarted();

    $_SESSION['user_id'] = $userId;
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    $_SESSION['access_level'] = $accessLevel;

    // Regenerate session ID for security
    session_regenerate_id(true);
}

/**
 * Clear user session (logout)
 */
function clearUserSession()
{
    ensureSessionStarted();

    $_SESSION = array();

    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Get all current user session data
 *
 * @return array
 */
function getCurrentUserData()
{
    ensureSessionStarted();

    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? null,
        'access_level' => $_SESSION['access_level'] ?? null
    ];
}
