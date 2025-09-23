<?php
// login_process.php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit('POST method required.');
}

require 'includes/db_connect.php';

$email = trim($_POST['email']);
$password = trim($_POST['password']);

if (empty($email) || empty($password)) {
    header("Location: index.php?error=invalid");
    exit();
}

try {
    $sql = "SELECT id, first_name, password, access_level FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_start();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['access_level'] = $user['access_level'];
        $_SESSION['logged_in'] = true;

        header("Location: dashboard.php");
        exit();
    } else {
        // Redirect back to the login page with an error
        header("Location: index.php?error=invalid");
        exit();
    }

} catch (PDOException $e) {
    // Log the error and redirect
    error_log($e->getMessage());
    header("Location: index.php?error=invalid");
    exit();
}
?>