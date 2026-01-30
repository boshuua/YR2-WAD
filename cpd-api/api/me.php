<?php
include_once '../config/database.php';
include_once '../helpers/auth_helper.php';
include_once '../helpers/response_helper.php';

requireMethod('GET');
requireAuth('Please log in.');

sendOk([
    'user' => getCurrentUserData()
]);
?>