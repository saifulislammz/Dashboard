<?php
require_once __DIR__ . '/../../src/config/security.php';
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/roles.php';
require_once __DIR__ . '/../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../src/Repositories/ClassroomRepository.php';

requireRole(ROLE_TEACHER);

$id         = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$repository = new \App\Repositories\ClassroomRepository($db);
$classroom  = $repository->findById($id);

// Security check: teacher can only view their own classrooms
if (!$classroom || (int)$classroom['teacher_id'] !== $auth->getUserId()) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Classroom not found or unauthorized.</p>');
}

$pageTitle  = 'Classroom Details';
$activeMenu = 'classes';

require __DIR__ . '/../../views/teacher/classroom_details.php';

