<?php

require 'includes/db_connect.php';

session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;   
} 


$error_message = '';
if (isset($_GET['error']) && $_GET['error'] === 'invalid') {
    $error_message = 'Invalid username or password.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error_message): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="login_process.php" method="post">
            <p><input type="email" name="email" placeholder="Email Address" required></p>
            <p><input type="password" name="password" placeholder="Password" required></p>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>