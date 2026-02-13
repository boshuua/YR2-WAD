<?php
require_once __DIR__ . '/bootstrap.php';

use App\Controllers\ActivityController;

$controller = new ActivityController();
$controller->index();
?>