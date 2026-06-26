<?php
require_once __DIR__ . '/../../../src/config/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/config/roles.php';
require_once __DIR__ . '/../../../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../../src/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../../../src/Repositories/NoticeRepository.php';
require_once __DIR__ . '/../../../src/Services/NoticeService.php';

requireRole(ROLE_ADMIN);

$repository = new \App\Repositories\NoticeRepository($db);
$service = new \App\Services\NoticeService($repository);

$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$oldValues = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        
        $oldValues = [
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'status' => $_POST['status'] ?? '',
            'audience_student' => !empty($_POST['audience_student']),
            'audience_teacher' => !empty($_POST['audience_teacher'])
        ];

        $service->createNotice($_POST, $auth->getUserId());

        $_SESSION['success_message'] = "Notice created successfully.";
        header("Location: index.php");
        exit;

    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

require __DIR__ . '/../../../views/admin/notices/create.php';
