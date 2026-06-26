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

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch Notice
$notice = $service->getNoticeDetails($id);

if (!$notice) {
    $_SESSION['error_message'] = "Notice not found.";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');

        $service->updateNotice($id, $_POST);

        $_SESSION['success_message'] = "Notice updated successfully.";
        header("Location: index.php");
        exit;

    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        // Fallback to post values
        $notice['title'] = $_POST['title'] ?? '';
        $notice['content'] = $_POST['content'] ?? '';
        $notice['status'] = ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
        $notice['target_audience'] = 'student';
        if (!empty($_POST['audience_student']) && !empty($_POST['audience_teacher'])) {
            $notice['target_audience'] = 'both';
        } elseif (!empty($_POST['audience_teacher'])) {
            $notice['target_audience'] = 'teacher';
        }
    }
}

require __DIR__ . '/../../../views/admin/notices/edit.php';
