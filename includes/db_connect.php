<?php
// Your database credentials from Plesk
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'ws369808_JOSHK'); //
define('DB_PASSWORD', 'QW34fNx6KBK3p8m'); //
define('DB_NAME', 'ws369808_WAD');       //

// --- DSN (Data Source Name) ---
$dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4"; //

// --- PDO Connection Options ---
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements for security
]; //

// --- Create PDO Instance ---
try {
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options); //
} catch (\PDOException $e) {
    // For a real-world app, you would log this error, not show it to the user.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}