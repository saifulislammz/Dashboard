<?php
require_once __DIR__ . '/../../../src/config/meetings_bootstrap.php';

requireRole(ROLE_ADMIN);

use App\Controllers\Admin\AdminSessionController;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/classrooms/index.php');
    exit;
}

$controller = new AdminSessionController($sessionService, $sessionRepo, $classroomRepo);
$controller->retry();
