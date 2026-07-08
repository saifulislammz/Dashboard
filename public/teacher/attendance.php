<?php
require_once __DIR__ . '/../../src/config/meetings_bootstrap.php';

requireRole(ROLE_TEACHER);

use App\Controllers\Teacher\TeacherAttendanceController;

$controller = new TeacherAttendanceController($container->get(App\Services\AttendanceService::class), $container->get(App\Repositories\ClassroomRepository::class));

$action = $_GET['action'] ?? 'classroom';

if ($action === 'session') {
    $controller->sessionReport();
} else {
    $controller->classroomReport();
}

