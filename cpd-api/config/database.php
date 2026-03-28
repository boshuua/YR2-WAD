<?php

declare(strict_types=1);

// cpd-api/config/database.php

/**
 * Database Configuration & Connection
 *
 * This file handles the PDO connection to the PostgreSQL database.
 * CORS and CSRF are handled globally in bootstrap.php.
 */

class Database
{
    private static ?Database $instance = null;
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private string $port;
    public ?PDO $conn = null;

    public function __construct()
    {
        $this->host = env('DB_HOST', 'localhost');
        $this->db_name = env('DB_NAME', 'mydb');
        $this->username = env('DB_USER', 'dev');
        $this->password = env('DB_PASS', 'pass');
        $this->port = env('DB_PORT', '5432');
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConn(): ?PDO
    {
        $this->conn = null;

        try {
            $this->conn = new PDO("pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Use standard JSON error response
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                "status" => "error",
                "message" => "Database connection error.",
                "details" => $e->getMessage()
            ]);
            exit;
        }

        return $this->conn;
    }
}
