<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/NoticeRepository.php';
require_once __DIR__ . '/../../src/Services/NoticeService.php';
require_once __DIR__ . '/../../src/Controllers/Teacher/TeacherDashboardController.php';

requireRole(ROLE_TEACHER);

$repository = new \App\Repositories\NoticeRepository($db);
$service    = new \App\Services\NoticeService($repository);
$controller = new \App\Controllers\Teacher\TeacherDashboardController($service);

$controller->index();
