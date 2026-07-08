<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../../src/Services/UserService.php';
require_once __DIR__ . '/../../src/Controllers/Admin/AdminUserController.php';

requireRole(ROLE_ADMIN);

$repository = new \App\Repositories\UserRepository($db);
$service    = new \App\Services\UserService($repository, $auth);
$controller = new \App\Controllers\Admin\AdminUserController(
    $service,
    ROLE_STUDENT,
    'Student',
    '/admin/students.php'
);

$controller->handle();
