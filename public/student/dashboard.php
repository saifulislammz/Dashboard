<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/NoticeRepository.php';
require_once __DIR__ . '/../../src/Services/NoticeService.php';
require_once __DIR__ . '/../../src/Controllers/Student/StudentDashboardController.php';

requireRole(ROLE_STUDENT);

$repository = new \App\Repositories\NoticeRepository($db);
$service    = new \App\Services\NoticeService($repository);
$controller = new \App\Controllers\Student\StudentDashboardController($service);

$controller->index();
