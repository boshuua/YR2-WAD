<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    </head>
<body class="<?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') echo 'dark-mode'; ?>">