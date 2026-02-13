<?php
require_once __DIR__ . '/bootstrap.php';

use App\Controllers\LessonController;

$controller = new LessonController();
$controller->index();
?>