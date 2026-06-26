<?php
require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/ClassroomRepository.php';
require_once __DIR__ . '/../../../src/Services/ClassroomService.php';

requireRole(ROLE_ADMIN);

$error   = '';
$success = '';

// Fetch teachers and students for dropdowns
$teachers = $db->query("SELECT id, username, email FROM users WHERE (roles_mask & " . ROLE_TEACHER . ") > 0 AND status = 0")->fetchAll(PDO::FETCH_ASSOC);
$students = $db->query("SELECT id, username, email FROM users WHERE (roles_mask & " . ROLE_STUDENT . ") > 0 AND status = 0")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        $repository = new \App\Repositories\ClassroomRepository($db);
        $service    = new \App\Services\ClassroomService($repository);
        $service->createClassroom($_POST, $auth->getUserId());
        $success = 'Classroom created successfully.';
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle  = 'Create Classroom';
$activeMenu = 'classrooms_create';

require __DIR__ . '/../../../views/admin/classrooms/create.php';
