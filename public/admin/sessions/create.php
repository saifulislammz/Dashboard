<?php
require_once __DIR__ . '/../../../src/config/meetings_bootstrap.php';

requireRole(ROLE_ADMIN);

use App\Controllers\Admin\AdminSessionController;

$controller = new AdminSessionController($container->get(App\Services\Sessions\ClassSessionService::class), $container->get(App\Repositories\ClassSessionRepository::class), $container->get(App\Repositories\ClassroomRepository::class));
$controller->create();

