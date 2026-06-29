<?php
require_once __DIR__ . '/../../src/config/meetings_bootstrap.php';

requireRole(ROLE_STUDENT);

use App\Controllers\Student\StudentAttendanceController;

$controller = new StudentAttendanceController($attendanceService, $classroomRepo);
$controller->myReport();
