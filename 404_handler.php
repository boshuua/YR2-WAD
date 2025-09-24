<?php
// Start the session so we can check the user's login status
session_start();

// Check if a user is currently logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['access_level'])) {
    
    // If they are logged in, redirect them to their appropriate dashboard.
    // This prevents logged-in users from feeling like they've been logged out.
    if ($_SESSION['access_level'] == 'admin') {
        header("Location: /admin/dashboard.php");
    } else {
        header("Location: /user/dashboard.php");
    }
    exit();

} else {
    
    // If no one is logged in, the safest place to send them is the login page.
    header("Location: /login.php");
    exit();
}