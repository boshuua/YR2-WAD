<?php
session_start();
require_once 'db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Email and password are required.";
        header("Location: ../login.php");
        exit();
    }

    // Prepare and execute statement to find user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify user exists and password is correct
    // password_verify() is the secure way to check a hashed password
    if ($user && password_verify($password, $user['password'])) {
        // --- Login successful! ---
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        // Store user data in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'];
        $_SESSION['access_level'] = $user['access_level'];

        // Redirect based on access level
        if ($user['access_level'] == 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../user/dashboard.php");
        }
        exit();

    } else {
        // --- Login failed ---
        $_SESSION['error_message'] = "Invalid email or password.";
        header("Location: ../login.php");
        exit();
    }
} else {
    // Not a POST request, redirect to login
    header("Location: ../login.php");
    exit();
}