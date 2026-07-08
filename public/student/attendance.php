<?php
require_once __DIR__ . '/../../src/config/meetings_bootstrap.php';

requireRole(ROLE_STUDENT);

use App\Controllers\Student\StudentAttendanceController;

$controller = new StudentAttendanceController($container->get(App\Services\AttendanceService::class), $container->get(App\Repositories\ClassroomRepository::class));
$controller->myReport();

