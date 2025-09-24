<?php
session_start(); //
require_once 'db_connect.php'; //

if ($_SERVER["REQUEST_METHOD"] == "POST") { //
    $email = trim($_POST['email']); //
    $password = trim($_POST['password']); //

    if (empty($email) || empty($password)) { //
        $_SESSION['error_message'] = "Email and password are required."; //
        header("Location: ../login.php"); //
        exit(); //
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?"); //
    $stmt->execute([$email]); //
    $user = $stmt->fetch(); //

    // This is the key part: verify the input password against the stored hash
    if ($user && password_verify($password, $user['password'])) { //
        
        session_regenerate_id(true); // Prevent session fixation attacks //

        // Store user data in the session
        $_SESSION['user_id'] = $user['id']; //
        $_SESSION['user_name'] = $user['first_name']; //
        $_SESSION['access_level'] = $user['access_level']; //
        $_SESSION['user_email'] = $user['email']; 
        // Redirect based on access level
        if ($user['access_level'] == 'admin') { //
            header("Location: ../admin/dashboard.php"); //
        } else {
            header("Location: ../user/dashboard.php"); //
        }
        exit(); //

    } else {
        // Login failed
        $_SESSION['error_message'] = "Invalid email or password."; //
        header("Location: ../login.php"); //
        exit(); //
    }
} else {
    // Redirect if not a POST request
    header("Location: ../login.php"); //
    exit(); //
}