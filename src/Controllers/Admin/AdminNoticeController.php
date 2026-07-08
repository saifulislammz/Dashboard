<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\NoticeService;

/**
 * AdminNoticeController — Thin Controller
 * Handles admin notice CRUD: index, create, edit, status, delete, duplicate.
 * No SQL, no business logic here — delegates entirely to NoticeService.
 */
class AdminNoticeController
{
    private NoticeService $service;

    public function __construct(NoticeService $service)
    {
        $this->service = $service;
    }

    // ─── GET/POST /admin/notices/index.php ────────────────────────
    public function index(): void
    {
        global $auth;

        $action         = $_GET['action'] ?? 'list';
        $successMessage = $_SESSION['success_message'] ?? '';
        $errorMessage   = $_SESSION['error_message']   ?? '';
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');

                if ($action === 'status') {
                    $id     = (int) ($_POST['id'] ?? 0);
                    $status = $_POST['status'] ?? '';
                    $this->service->updateStatus($id, $status);
                    $_SESSION['success_message'] = 'Notice status updated.';
                    header('Location: index.php');
                    exit;
                }

                if ($action === 'delete') {
                    $id = (int) ($_POST['id'] ?? 0);
                    $this->service->deleteNotice($id);
                    $_SESSION['success_message'] = 'Notice deleted.';
                    header('Location: index.php');
                    exit;
                }

                if ($action === 'duplicate') {
                    $id = (int) ($_POST['id'] ?? 0);
                    $this->service->duplicateNotice($id, (int) $auth->getUserId());
                    $_SESSION['success_message'] = 'Notice duplicated.';
                    header('Location: index.php');
                    exit;
                }

            } catch (\Exception $e) {
                $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }

        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $limit      = 10;

        $paginated  = $this->service->getPaginatedNotices($page, $limit, $search);
        $notices    = $paginated['data'];
        $totalPages = $paginated['pages'];

        require __DIR__ . '/../../../views/admin/notices/index.php';
    }

    // ─── GET/POST /admin/notices/create.php ───────────────────────
    public function create(): void
    {
        global $auth;

        $successMessage = $_SESSION['success_message'] ?? '';
        $errorMessage   = $_SESSION['error_message']   ?? '';
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        $oldValues = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');

                $oldValues = [
                    'title'            => $_POST['title']            ?? '',
                    'content'          => $_POST['content']          ?? '',
                    'status'           => $_POST['status']           ?? '',
                    'audience_student' => !empty($_POST['audience_student']),
                    'audience_teacher' => !empty($_POST['audience_teacher']),
                ];

                $this->service->createNotice($_POST, (int) $auth->getUserId());

                $_SESSION['success_message'] = 'Notice created successfully.';
                header('Location: index.php');
                exit;

            } catch (\Exception $e) {
                $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }

        require __DIR__ . '/../../../views/admin/notices/create.php';
    }

    // ─── GET/POST /admin/notices/edit.php?id=X ────────────────────
    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));

        if ($id <= 0) {
            header('Location: index.php');
            exit;
        }

        $notice = $this->service->getNoticeDetails($id);

        if (!$notice) {
            $_SESSION['error_message'] = 'Notice not found.';
            header('Location: index.php');
            exit;
        }

        $successMessage = $_SESSION['success_message'] ?? '';
        $errorMessage   = $_SESSION['error_message']   ?? '';
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');

                $this->service->updateNotice($id, $_POST);

                $_SESSION['success_message'] = 'Notice updated successfully.';
                header('Location: index.php');
                exit;

            } catch (\Exception $e) {
                $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

                // Restore POST values for re-display
                $notice['title']           = $_POST['title']  ?? '';
                $notice['content']         = $_POST['content'] ?? '';
                $notice['status']          = (($_POST['status'] ?? '') === 'inactive') ? 'inactive' : 'active';
                $notice['target_audience'] = 'student';
                if (!empty($_POST['audience_student']) && !empty($_POST['audience_teacher'])) {
                    $notice['target_audience'] = 'both';
                } elseif (!empty($_POST['audience_teacher'])) {
                    $notice['target_audience'] = 'teacher';
                }
            }
        }

        require __DIR__ . '/../../../views/admin/notices/edit.php';
    }
}
