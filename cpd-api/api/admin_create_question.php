<?php
require_once __DIR__ . '/bootstrap.php';

use App\Controllers\QuestionController;

$controller = new QuestionController();
$controller->create();
?>