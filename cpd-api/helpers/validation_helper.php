<?php
/**
 * Validation Helper Functions
 *
 * Provides input validation and sanitization utilities
 */

/**
 * Validate required fields in data object/array
 *
 * @param object|array $data Data to validate
 * @param array $requiredFields Array of required field names
 * @return array Empty array if valid, array of missing fields if invalid
 */
function validateRequired($data, $requiredFields) {
    $missing = [];

    foreach ($requiredFields as $field) {
        if (is_object($data)) {
            if (!isset($data->$field) || empty($data->$field)) {
                $missing[] = $field;
            }
        } else {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
    }

    return $missing;
}

/**
 * Require fields and send error if missing
 *
 * @param object|array $data Data to validate
 * @param array $requiredFields Array of required field names
 */
function requireFields($data, $requiredFields) {
    $missing = validateRequired($data, $requiredFields);

    if (!empty($missing)) {
        sendValidationError(
            $missing,
            "Missing required fields: " . implode(', ', $missing)
        );
    }
}

/**
 * Validate email format
 *
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate email and send error if invalid
 *
 * @param string $email
 * @param string $fieldName Field name for error message
 */
function requireValidEmail($email, $fieldName = 'email') {
    if (!isValidEmail($email)) {
        sendBadRequest("Invalid email format for field: $fieldName");
    }
}

/**
 * Sanitize string for safe output (prevent XSS)
 *
 * @param string $string
 * @return string
 */
function sanitizeString($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 *
 * @param string $email
 * @return string
 */
function sanitizeEmail($email) {
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

/**
 * Validate integer
 *
 * @param mixed $value
 * @return bool
 */
function isValidInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

/**
 * Validate positive integer
 *
 * @param mixed $value
 * @return bool
 */
function isPositiveInt($value) {
    return isValidInt($value) && $value > 0;
}

/**
 * Validate string length
 *
 * @param string $string
 * @param int $min Minimum length
 * @param int $max Maximum length
 * @return bool
 */
function isValidLength($string, $min = 0, $max = PHP_INT_MAX) {
    $length = strlen($string);
    return $length >= $min && $length <= $max;
}

/**
 * Validate that value is in allowed list
 *
 * @param mixed $value
 * @param array $allowedValues
 * @return bool
 */
function isInList($value, $allowedValues) {
    return in_array($value, $allowedValues, true);
}

/**
 * Require value to be in allowed list
 *
 * @param mixed $value
 * @param array $allowedValues
 * @param string $fieldName
 */
function requireInList($value, $allowedValues, $fieldName = 'value') {
    if (!isInList($value, $allowedValues)) {
        sendBadRequest("Invalid $fieldName. Allowed values: " . implode(', ', $allowedValues));
    }
}

/**
 * Validate date format (YYYY-MM-DD)
 *
 * @param string $date
 * @return bool
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate datetime format (YYYY-MM-DD HH:MM:SS)
 *
 * @param string $datetime
 * @return bool
 */
function isValidDateTime($datetime) {
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    return $d && $d->format('Y-m-d H:i:s') === $datetime;
}

/**
 * Extract value from object or array with default
 *
 * @param object|array $data
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getValue($data, $key, $default = null) {
    if (is_object($data)) {
        return $data->$key ?? $default;
    } else {
        return $data[$key] ?? $default;
    }
}

/**
 * Sanitize HTML content (allow safe HTML tags)
 *
 * @param string $html
 * @return string
 */
function sanitizeHtml($html) {
    // Allow only safe HTML tags
    $allowed_tags = '<p><br><strong><em><u><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
    return strip_tags($html, $allowed_tags);
}

/**
 * Validate and sanitize integer input
 *
 * @param mixed $value
 * @param int $default Default value if invalid
 * @return int
 */
function getInt($value, $default = 0) {
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    return $filtered !== false ? $filtered : $default;
}

/**
 * Validate password strength
 *
 * @param string $password
 * @param int $minLength Minimum length (default: 8)
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePassword($password, $minLength = 8) {
    $errors = [];

    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least $minLength characters long";
    }

    // Add more rules as needed
    // if (!preg_match('/[A-Z]/', $password)) {
    //     $errors[] = "Password must contain at least one uppercase letter";
    // }
    // if (!preg_match('/[a-z]/', $password)) {
    //     $errors[] = "Password must contain at least one lowercase letter";
    // }
    // if (!preg_match('/[0-9]/', $password)) {
    //     $errors[] = "Password must contain at least one number";
    // }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get client IP address
 *
 * @return string
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
