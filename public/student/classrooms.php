<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/ClassroomRepository.php';

requireRole(ROLE_STUDENT);

$page      = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit     = 20;
$offset    = ($page - 1) * $limit;
$studentId = $auth->getUserId();

$repository  = new \App\Repositories\ClassroomRepository($db);
$classrooms  = $repository->getStudentClassrooms($studentId, $limit, $offset);
$total       = $repository->countStudentClassrooms($studentId);
$totalPages  = (int) ceil($total / $limit);
$currentPage = $page;

$pageTitle  = 'My Classes';
$activeMenu = 'classes';

require __DIR__ . '/../../views/student/classrooms.php';

