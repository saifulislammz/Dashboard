<?php
require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/ClassroomRepository.php';
require_once __DIR__ . '/../../../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../../../src/Services/ClassroomService.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/AdminClassroomController.php';

requireRole(ROLE_ADMIN);

$classroomRepo  = new \App\Repositories\ClassroomRepository($db);
$userRepo       = new \App\Repositories\UserRepository($db);
$service        = new \App\Services\ClassroomService($classroomRepo, $userRepo);
$controller     = new \App\Controllers\Admin\AdminClassroomController($service);

$controller->index();
