<?php
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';
include_once '../helpers/CSRF_helper.php';

requireMethod(['GET', 'POST']);

$token = getCsrfToken();

sendOk([
    'csrfToken' => $token
]);
?>