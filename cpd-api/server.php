<?php

declare(strict_types=1);

// cpd-api/server.php
// This router script mimics the project's .htaccess rule for the PHP built-in server.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// The .htaccess rule redirects any request ending in .php inside the /api/ directory.
// e.g. /api/get_courses.php
if (preg_match('/^\/api\/.+\.php$/', $uri)) {
    // If the request matches the pattern, load the front controller.
    require __DIR__ . '/api/index.php';
} else {
    // For any other request (e.g. for a static file), let the built-in server handle it.
    // If the file doesn't exist, the server will correctly return a 404.
    return false;
}
