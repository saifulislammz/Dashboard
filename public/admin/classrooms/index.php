<?php
require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/ClassroomRepository.php';
require_once __DIR__ . '/../../../src/Services/ClassroomService.php';

requireRole(ROLE_ADMIN);

$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search  = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit   = 20;
$offset  = ($page - 1) * $limit;

$repository = new \App\Repositories\ClassroomRepository($db);
$service    = new \App\Services\ClassroomService($repository);

$classrooms  = $repository->getPaginatedClassrooms($limit, $offset, $search);
$total       = $repository->countTotalClassrooms($search);
$totalPages  = (int) ceil($total / $limit);
$currentPage = $page;

$pageTitle  = 'Manage Classrooms';
$activeMenu = 'classrooms_manage';

require __DIR__ . '/../../../views/admin/classrooms/index.php';
