<?php
require_once __DIR__ . '/bootstrap.php';

use App\Controllers\AttachmentController;

$controller = new AttachmentController();
$controller->delete();
?>