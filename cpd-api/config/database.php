<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===============================================================
// CRITICAL SECURITY FIX
// ===============================================================
// Allow requests from your specific Angular app URL
header("Access-Control-Allow-Origin: http://localhost:4200");
// Allow the browser to send cookies (for sessions)
header("Access-Control-Allow-Credentials: true");
// ===============================================================

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class Database {
    private $host = 'localhost';
    private $db_name = 'mydb';
    private $username = 'dev';
    private $password = 'pass';
    public $conn; 

    public function getConn() { // Renamed from getConn
        $this->conn = null;
        $dsn = "pgsql:host=" . $this->host . ";dbname=" . $this->db_name;
        try {
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) { 
            http_response_code(500);
            exit(json_encode(["message" => "Connection error: " . $e->getMessage()])); 
        }
        return $this->conn;
    }
}
?>