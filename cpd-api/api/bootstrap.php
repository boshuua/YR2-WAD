<?php

declare(strict_types=1);

// cpd-api/api/bootstrap.php

/**
 * 1. CORS Headers FIRST
 * This ensures that even if the script crashes later, the browser gets the CORS headers.
 */
$allowed_origin = 'https://ws369808-wad.remote.ac';
header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-CSRF-Token, Access-Control-Allow-Private-Network");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request immediately
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Private-Network: true");
    http_response_code(200);
    exit();
}

/**
 * 2. Robust Error & Exception Handling
 * We want to return JSON errors, not HTML, to avoid breaking the frontend parser.
 */
ini_set('display_errors', 0); // Disable HTML errors
error_reporting(E_ALL);

// Custom Exception Handler to return JSON
set_exception_handler(function ($e) {
    http_response_code(500);
    // Ensure CORS headers are present even in error
    $allowed_origin = 'https://ws369808-wad.remote.ac';
    header("Access-Control-Allow-Origin: $allowed_origin");
    header("Access-Control-Allow-Credentials: true");
    header('Content-Type: application/json; charset=UTF-8');
    
    $env = getenv('APP_ENV') ?: 'production';
    $isDev = ($env === 'development' || $env === 'dev' || $env === 'local');

    $response = [
        "status" => "error",
        "message" => "Internal Server Error"
    ];

    if ($isDev) {
        $response["message"] .= ": " . $e->getMessage();
        $response["file"] = basename($e->getFile());
        $response["line"] = $e->getLine();
    }

    echo json_encode($response);
    exit;
});

// Convert PHP Warnings/Notices into Exceptions so they are caught by the handler above
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * 3. Load Helpers & Config early
 */
require_once __DIR__ . '/../helpers/env_helper.php';

/**
 * 4. Composer Autoloader
 * Wrapped in try-catch to handle missing/corrupted vendor files gracefully.
 */
$composer_autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer_autoload)) {
    try {
        require_once $composer_autoload;
    } catch (Throwable $t) {
        // Log the error locally if possible, then continue or exit with JSON
        // For now, we exit with a clear message.
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Composer autoloader failed. The 'vendor' directory may be incomplete or corrupted.",
            "details" => $t->getMessage()
        ]);
        exit;
    }
}

/**
 * 5. PSR-4 simplified autoloader for App\\ namespace
 */
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * 6. Database Connection
 */
try {
    require_once __DIR__ . '/../config/database.php';
} catch (Throwable $t) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database configuration error.",
        "details" => $t->getMessage()
    ]);
    exit;
}

/**
 * 7. Load Additional Helpers
 */
require_once __DIR__ . '/../helpers/response_helper.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/CSRF_helper.php';
require_once __DIR__ . '/../helpers/validation_helper.php';
require_once __DIR__ . '/../helpers/log_helper.php';

/**
 * 8. CSRF Check (Centralized)
 */
$method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
$unsafe = in_array($method, ['POST', 'PUT', 'DELETE'], true);
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);
$endpoint = basename($path);

// Exempt endpoints
$csrfExempt = in_array($endpoint, ['user_login.php', 'csrf.php', 'forgot_password.php', 'approve_reset.php'], true);

if ($unsafe && !$csrfExempt) {
    if (!isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        requireCsrfToken('CSRF token missing.');
    } else {
        requireCsrfToken('Invalid CSRF token.');
    }
}
