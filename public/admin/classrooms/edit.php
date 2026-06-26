<?php
require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/ClassroomRepository.php';
require_once __DIR__ . '/../../../src/Services/ClassroomService.php';

requireRole(ROLE_ADMIN);

$id         = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$repository = new \App\Repositories\ClassroomRepository($db);
$service    = new \App\Services\ClassroomService($repository);
$classroom  = $repository->findById($id);

if (!$classroom) {
    http_response_code(404);
    die('<h1>404 Not Found</h1><p>Classroom not found.</p>');
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        $service->updateClassroom($id, $_POST);
        $success   = 'Classroom updated successfully.';
        $classroom = $repository->findById($id); // refresh
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle  = 'Edit Classroom';
$activeMenu = 'classrooms_manage';

require __DIR__ . '/../../../views/admin/classrooms/edit.php';
