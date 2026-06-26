<?php
require_once __DIR__ . '/../../../src/config/meetings_bootstrap.php';

requireRole(ROLE_ADMIN);

use App\Controllers\Admin\AdminSessionController;

$controller = new AdminSessionController($sessionService, $sessionRepo, $classroomRepo);
$controller->create();
