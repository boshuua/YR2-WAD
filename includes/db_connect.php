<?php

$db_host = 'localhost';
$db_user = 'ws369808_JOSHK';
$db_pass = '6vu721!Kw';
$db_name = 'ws369808_WAD';
$charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
    // In a real application, you would log this error and show a generic message
    // For now, we'll just display the error.
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}