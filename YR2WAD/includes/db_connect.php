<?php

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'ws369808_JOSHK');
define('DB_PASSWORD', 'QW34fNx6KBK3p8m');
define('DB_NAME', 'ws369808_WAD');

/* Attempt to connect to MySQL database */
$dsn = "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4";


$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}