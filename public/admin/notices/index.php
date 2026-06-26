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

$action = $_GET['action'] ?? 'list';
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
        
        if ($action === 'status') {
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $service->updateStatus($id, $status);
            $_SESSION['success_message'] = "Notice status updated.";
            header("Location: index.php");
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $service->deleteNotice($id);
            $_SESSION['success_message'] = "Notice deleted.";
            header("Location: index.php");
            exit;
            
        } elseif ($action === 'duplicate') {
            $id = (int)($_POST['id'] ?? 0);
            $service->duplicateNotice($id, $auth->getUserId());
            $_SESSION['success_message'] = "Notice duplicated.";
            header("Location: index.php");
            exit;
        }

    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Handle GET for Listing
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;

$paginated = $service->getPaginatedNotices($page, $limit, $search);

$notices = $paginated['data'];
$totalPages = $paginated['pages'];

require __DIR__ . '/../../../views/admin/notices/index.php';
