<?php
require_once __DIR__ . '/../../src/config/meetings_bootstrap.php';

requireRole(ROLE_TEACHER);

use App\Controllers\Teacher\TeacherAttendanceController;

$controller = new TeacherAttendanceController($attendanceService, $classroomRepo);

$action = $_GET['action'] ?? 'classroom';

if ($action === 'session') {
    $controller->sessionReport();
} else {
    $controller->classroomReport();
}
