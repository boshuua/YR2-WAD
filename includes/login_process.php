<?php
session_start();
require_once 'db_connect.php';
require_once 'log_function.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Email and password are required.";
        header("Location: ../login.php");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        // Store user data in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'];
        $_SESSION['access_level'] = $user['access_level'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['theme'] = $user['theme'];

     
        // The log_activity function gets user info from the session, so only pass the message.
        log_activity("Logged in.");

        if ($user['access_level'] == 'admin') {
            header("Location: ../admin/dashboard.php");
        } elseif ($user['access_level'] == 'trainer') {
            header("Location: ../trainer/dashboard.php");
        } else {
            header("Location: ../user/dashboard.php");
        }
        exit();
    } else {
        $_SESSION['error_message'] = "Invalid email or password.";
        header("Location: ../login.php");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}