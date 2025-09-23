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
    // ✅ If this line runs without error, you are connected!
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Optional: You can add this line for testing during development
    // echo "Database connection successful!";

} catch (\PDOException $e) {
   
    
    
    // You should log the error and show a generic, user-friendly message.
    error_log("Database Connection Error: " . $e->getMessage());
    die("Error: Could not connect to the database. Please try again later.");
}