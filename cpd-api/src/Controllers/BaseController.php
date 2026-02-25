<?php
// cpd-api/src/Controllers/BaseController.php

namespace App\Controllers;

use Database;

class BaseController
{
    protected $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConn();

        // Expose $pdo globally so helpers (e.g. getSetting()) can access the connection
        global $pdo;
        $pdo = $this->db;
    }

    /**
     * Send a JSON response
     * @param mixed $data
     * @param int $statusCode
     */
    protected function json($data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Send an error response
     * @param string $message
     * @param int $statusCode
     */
    protected function error(string $message, int $statusCode = 400)
    {
        $this->json(['message' => $message], $statusCode);
    }

    /**
     * Get JSON input as object or array
     * @param bool $assoc Return associative array instead of object
     * @return mixed
     */
    protected function getJsonInput(bool $assoc = false)
    {
        $input = json_decode(file_get_contents("php://input"), $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $assoc ? [] : null;
        }
        return $input;
    }

    /**
     * Validate required fields
     * @param mixed $data Input data (object or array)
     * @param array $fields List of required field names
     * @return bool|string True if valid, or error message string if missing
     */
    protected function validateRequired($data, array $fields)
    {
        $isObject = is_object($data);
        foreach ($fields as $field) {
            if ($isObject) {
                if (!isset($data->$field) || empty($data->$field)) {
                    return "Missing required field: $field";
                }
            } else {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return "Missing required field: $field";
                }
            }
        }
        return true;
    }
}
