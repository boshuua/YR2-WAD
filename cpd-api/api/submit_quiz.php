<?php
require_once __DIR__ . '/bootstrap.php';

use App\Controllers\CourseController;

$controller = new CourseController();
$controller->submitQuiz();
?>