<?php
session_start();

// Check if a user is logged in
if (isset($_SESSION['user_id'])) {
    // If they are, redirect based on access level
    if ($_SESSION['access_level'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
} else {
    // If no one is logged in, send them to the login page
    header("Location: login.php");
    exit();
}