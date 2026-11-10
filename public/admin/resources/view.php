<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/PermissionMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/ClassroomResourceRepository.php';
require_once __DIR__ . '/../../../src/Services/ClassroomResourceService.php';
require_once __DIR__ . '/../../../src/Controllers/Admin/AdminClassroomResourceController.php';

requireRole(ROLE_ADMIN);
requirePermission('classroom_resources.view');

$repository = new \App\Repositories\ClassroomResourceRepository($db);
$service    = new \App\Services\ClassroomResourceService($repository, $db);
$controller = new \App\Controllers\Admin\AdminClassroomResourceController($service, $repository);

$controller->view();

