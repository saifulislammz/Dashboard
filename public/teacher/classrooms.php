<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/ClassroomRepository.php';
require_once __DIR__ . '/../../src/Services/ClassroomService.php';

requireRole(ROLE_TEACHER);

$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit   = 20;
$offset  = ($page - 1) * $limit;
$teacherId = $auth->getUserId();

$repository  = new \App\Repositories\ClassroomRepository($db);
$classrooms  = $repository->getTeacherClassrooms($teacherId, $limit, $offset);
$total       = $repository->countTeacherClassrooms($teacherId);
$totalPages  = (int) ceil($total / $limit);
$currentPage = $page;

$pageTitle  = 'My Classes';
$activeMenu = 'classes';

require __DIR__ . '/../../views/teacher/classrooms.php';
