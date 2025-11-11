<?php
/**
 * Response Helper Functions
 *
 * Provides standardized JSON response formatting
 */

/**
 * Send JSON response and exit
 *
 * @param mixed $data Data to send (array or string)
 * @param int $statusCode HTTP status code
 * @param bool $exit Whether to exit after sending (default: true)
 */
function sendJsonResponse($data, $statusCode = 200, $exit = true) {
    http_response_code($statusCode);

    // If data is a string, wrap it in message
    if (is_string($data)) {
        $data = ["message" => $data];
    }

    echo json_encode($data);

    if ($exit) {
        exit();
    }
}

/**
 * Send success response
 *
 * @param mixed $data Data to send
 * @param int $statusCode HTTP status code (default: 200)
 */
function sendSuccess($data, $statusCode = 200) {
    sendJsonResponse($data, $statusCode);
}

/**
 * Send error response
 *
 * @param string $message Error message
 * @param int $statusCode HTTP status code (default: 400)
 */
function sendError($message, $statusCode = 400) {
    sendJsonResponse(["message" => $message], $statusCode);
}

/**
 * Send 200 OK response
 *
 * @param mixed $data
 */
function sendOk($data) {
    sendJsonResponse($data, 200);
}

/**
 * Send 201 Created response
 *
 * @param mixed $data
 */
function sendCreated($data) {
    sendJsonResponse($data, 201);
}

/**
 * Send 204 No Content response
 */
function sendNoContent() {
    http_response_code(204);
    exit();
}

/**
 * Send 400 Bad Request response
 *
 * @param string $message
 */
function sendBadRequest($message = "Bad Request") {
    sendJsonResponse(["message" => $message], 400);
}

/**
 * Send 401 Unauthorized response
 *
 * @param string $message
 */
function sendUnauthorized($message = "Unauthorized") {
    sendJsonResponse(["message" => $message], 401);
}

/**
 * Send 403 Forbidden response
 *
 * @param string $message
 */
function sendForbidden($message = "Forbidden") {
    sendJsonResponse(["message" => $message], 403);
}

/**
 * Send 404 Not Found response
 *
 * @param string $message
 */
function sendNotFound($message = "Not Found") {
    sendJsonResponse(["message" => $message], 404);
}

/**
 * Send 405 Method Not Allowed response
 *
 * @param string $message
 */
function sendMethodNotAllowed($message = "Method Not Allowed") {
    sendJsonResponse(["message" => $message], 405);
}

/**
 * Send 422 Unprocessable Entity response (validation errors)
 *
 * @param array $errors Array of validation errors
 */
function sendValidationError($errors, $message = "Validation failed") {
    sendJsonResponse([
        "message" => $message,
        "errors" => $errors
    ], 422);
}

/**
 * Send 500 Internal Server Error response
 *
 * @param string $message
 */
function sendServerError($message = "Internal Server Error") {
    sendJsonResponse(["message" => $message], 500);
}

/**
 * Send 503 Service Unavailable response
 *
 * @param string $message
 */
function sendServiceUnavailable($message = "Service Unavailable") {
    sendJsonResponse(["message" => $message], 503);
}

/**
 * Check if request method matches expected method(s)
 * Sends 405 error and exits if not matching
 *
 * @param string|array $allowedMethods Single method or array of methods
 */
function requireMethod($allowedMethods) {
    $allowedMethods = (array) $allowedMethods;
    $currentMethod = $_SERVER['REQUEST_METHOD'];

    if (!in_array($currentMethod, $allowedMethods)) {
        sendMethodNotAllowed("Method Not Allowed. Expected: " . implode(', ', $allowedMethods));
    }
}

/**
 * Get JSON input from request body
 *
 * @param bool $assoc Return as associative array instead of object
 * @return mixed
 */
function getJsonInput($assoc = false) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, $assoc);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendBadRequest("Invalid JSON: " . json_last_error_msg());
    }

    return $data;
}

/**
 * Handle CORS preflight request
 */
function handleCorsPrelight() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
