<?php
session_start(); //

// If no user is logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) { //
    header("Location: /login.php"); // Using absolute path
    exit(); //
}

// This function checks if the logged-in user is an admin
// We'll call it on admin-only pages
function require_admin() {
    if ($_SESSION['access_level'] !== 'admin') { //
        // If not an admin, kick them out to the user dashboard
        header("Location: /user/dashboard.php"); //
        exit(); //
    }
}