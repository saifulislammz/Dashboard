<?php
require_once __DIR__ . '/../../../src/config/meetings_bootstrap.php';

requireRole(ROLE_ADMIN);

use App\Controllers\Admin\AttendanceController;

$controller = new AttendanceController($attendanceService, $classroomRepo, $sessionRepo);
$controller->sessionReport();
