<?php

session_start();

if (!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit();
}

function require_admin() {
    if($_SESSION['access_level'] !== 'admin') {
        header("Location: /user/dashboard.php");
        exit();
    }
}
