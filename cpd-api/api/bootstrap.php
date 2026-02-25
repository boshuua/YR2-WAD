<?php
// cpd-api/api/bootstrap.php

// 1. Error Handling (Environment dependent)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Composer Autoloader
$composer_autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

// 2.1 PSR-4 simplified autoloader for App\\ namespace
spl_autoload_register(function ($class) {
    // Project prefix
    $prefix = 'App\\';

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/../src/';

    // Does the class use the prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// 3. Load Helpers & Config (Legacy Support)
require_once __DIR__ . '/../helpers/env_helper.php';

// Load Environment variables early
// (Assumes .env is loaded by env_helper or handled there)

// 4. CORS Handling
$allowed_origin = "https://ws369808-wad.remote.ac"; // In production, this might come from env
header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-CSRF-Token");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request immediately
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 5. Database Connection (Global instance for now, or use Dependency Injection later)
require_once __DIR__ . '/../config/database.php';

// 6. CSRF & Auth Helpers (Legacy Support for now)
require_once __DIR__ . '/../helpers/response_helper.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/CSRF_helper.php';
require_once __DIR__ . '/../helpers/validation_helper.php';
require_once __DIR__ . '/../helpers/log_helper.php'; // Log helper

// CSRF Check (Centralized)
$method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
$unsafe = in_array($method, ['POST', 'PUT', 'DELETE'], true);
// When using a Front Controller (index.php), the actual requested file is often the entire REQUEST_URI path
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$parsedUri = parse_url($requestUri);
$path = $parsedUri['path'] ?? '';
$endpoint = basename($path);

// Allow login + csrf token bootstrap without CSRF header
// Exempt endpoints
$csrfExempt = in_array($endpoint, ['user_login.php', 'csrf.php', 'forgot_password.php', 'approve_reset.php'], true);

if ($unsafe && !$csrfExempt) {
    if (!isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        // Fallback for some legacy calls if needed, or strictly enforce
        requireCsrfToken('CSRF token missing.');
    } else {
        // Validate token (using helper which checks and exits on failure)
        requireCsrfToken('Invalid CSRF token.');
    }
}
