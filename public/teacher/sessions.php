<?php
require_once __DIR__ . '/../../src/config/meetings_bootstrap.php';

requireRole(ROLE_TEACHER);

use App\Controllers\Teacher\TeacherSessionController;

$controller = new TeacherSessionController($db, $container->get(App\Services\Sessions\ClassSessionService::class), $container->get(App\Repositories\ClassroomRepository::class));
$controller->index();

