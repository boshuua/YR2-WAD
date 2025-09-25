<?php
session_start();

// If no user is logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {
    header("Location: /login"); 
    exit();
}

// This function checks if the logged-in user is an admin
function require_admin() {
    if ($_SESSION['access_level'] !== 'admin') {
        // If not an admin, kick them out to the user dashboard
        header("Location: /dashboard"); 
        exit();
    }
}