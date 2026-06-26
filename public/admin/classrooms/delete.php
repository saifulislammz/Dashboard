<?php
require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/ClassroomRepository.php';
require_once __DIR__ . '/../../../src/Services/ClassroomService.php';

requireRole(ROLE_ADMIN);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        $repository = new \App\Repositories\ClassroomRepository($db);
        $service    = new \App\Services\ClassroomService($repository);
        $service->deleteClassroom($id);
    } catch (\Exception $e) {
        http_response_code(403);
        die($e->getMessage());
    }
}

header('Location: /admin/classrooms/index.php');
exit;
