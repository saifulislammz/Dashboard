<?php
require_once __DIR__ . '/../../../src/config/meetings_bootstrap.php';

requireRole(ROLE_ADMIN);

use App\Controllers\Admin\AttendanceController;

$controller = new AttendanceController($container->get(App\Services\AttendanceService::class), $container->get(App\Repositories\ClassroomRepository::class), $container->get(App\Repositories\ClassSessionRepository::class));
$controller->overview();

