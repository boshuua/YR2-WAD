<?php
// ===============================================================
// 1. CORS CONFIGURATION (MUST BE FIRST)
// ===============================================================
// In production, we hardcode the fallback to ensure CORS always works even if .env is missing
$allowed_origin = "https://ws369808-wad.remote.ac";

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

// ===============================================================
// 2. LOAD ENVIRONMENT
// ===============================================================
require_once __DIR__ . '/../helpers/env_helper.php';

// Error reporting based on environment
if (function_exists('env') && env('APP_DEBUG', false)) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ===============================================================
// 3. CSRF PROTECTION (SESSION + X-CSRF-Token HEADER)
// ===============================================================
include_once __DIR__ . '/../helpers/response_helper.php';
include_once __DIR__ . '/../helpers/auth_helper.php';
include_once __DIR__ . '/../helpers/CSRF_helper.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
$unsafe = in_array($method, ['POST', 'PUT', 'DELETE'], true);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$endpoint = basename($path);

// Allow login + csrf token bootstrap without CSRF header
$csrfExempt = in_array($endpoint, ['user_login.php', 'csrf.php'], true);

if ($unsafe && !$csrfExempt) {
    requireCsrfToken('CSRF token missing or invalid.');
}

class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct()
    {
        $this->host = env('DB_HOST', 'localhost');
        $this->db_name = env('DB_NAME', 'mydb');
        $this->username = env('DB_USER', 'dev');
        $this->password = env('DB_PASS', 'pass');
        $this->port = env('DB_PORT', '5432');
    }

    public function getConn()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO("pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            http_response_code(500);
            exit(json_encode(["message" => "Connection error: " . $e->getMessage()]));
        }

        return $this->conn;
    }
}
?>