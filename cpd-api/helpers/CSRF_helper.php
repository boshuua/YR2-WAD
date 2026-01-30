<?php
/**
 * CSRF Helper Functions
 *
 * Session-based CSRF token generation/validation for cookie-authenticated requests.
 */

function getCsrfToken() {
    ensureSessionStarted();

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function requireCsrfToken($message = 'Invalid CSRF token.') {
    ensureSessionStarted();

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($sessionToken) || $sessionToken === '' || !is_string($headerToken) || $headerToken === '') {
        sendForbidden($message);
    }

    if (!hash_equals($sessionToken, $headerToken)) {
        sendForbidden($message);
    }
}