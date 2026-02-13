<?php
require_once __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;

$controller = new AuthController();
$controller->me();
?>