<?php
require_once __DIR__ . '/../../src/config/meetings_bootstrap.php';

requireRole(ROLE_STUDENT);

use App\Controllers\Student\StudentSessionController;

$controller = new StudentSessionController($db, $container->get(App\Services\Sessions\ClassSessionService::class), $container->get(App\Repositories\ClassroomRepository::class));
$controller->index();

