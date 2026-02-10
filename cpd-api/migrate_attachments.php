<?php
// Load configuration
include_once 'config/database.php';

// Handle CORS
// handleCorsPrelight(); // Not needed for CLI

echo "Starting Database Migration for User Attachments...\n";

$database = new Database();
$db = $database->getConn();

if (!$db) {
    die("Could not connect to database.\n");
}

try {
    echo "Creating 'user_attachments' table if not exists...\n";
    $sql = "CREATE TABLE IF NOT EXISTS user_attachments (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        file_name VARCHAR(255) NOT NULL,
        file_path TEXT NOT NULL,
        file_type VARCHAR(50),
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "Table 'user_attachments' created successfully.\n";

} catch (Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
?>