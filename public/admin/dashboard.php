<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';

// Manual require for the new classes to ensure they load
require_once __DIR__ . '/../../src/Repositories/AnalyticsRepository.php';
require_once __DIR__ . '/../../src/Services/AnalyticsService.php';
require_once __DIR__ . '/../../src/Controllers/Admin/DashboardController.php';

requireRole(ROLE_ADMIN);

$repository = new \App\Repositories\AnalyticsRepository($db);
$service = new \App\Services\AnalyticsService($repository);
$controller = new \App\Controllers\Admin\DashboardController($service);

$controller->index();
