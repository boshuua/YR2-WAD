<?php
/**
 * Environment Configuration Helper
 *
 * Loads environment variables from .env file
 * Works with or without vlucas/phpdotenv library
 */

/**
 * Load environment variables from .env file
 */
function loadEnv() {
    $envFile = __DIR__ . '/../.env';

    if (!file_exists($envFile)) {
        throw new Exception('.env file not found. Please create one based on .env.example');
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Set environment variable if not already set
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

/**
 * Get environment variable value
 *
 * @param string $key The environment variable key
 * @param mixed $default Default value if key doesn't exist
 * @return mixed
 */
function env($key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        $value = $_ENV[$key] ?? $default;
    }

    // Convert string booleans
    if (is_string($value)) {
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }
    }

    return $value;
}

// Auto-load environment variables when this file is included
loadEnv();
