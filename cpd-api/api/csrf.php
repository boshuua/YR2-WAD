<?php
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/CSRF_helper.php';

requireMethod(['GET', 'POST']);

// Ensure a session exists and return the token.
// (Useful after login; can also be called at app start.)
$token = getCsrfToken();

sendOk([
    'csrfToken' => $token
]);
?>