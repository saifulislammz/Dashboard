<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\NoticeService;
use PDO;

/**
 * AdminNoticeController — Thin Controller
 * Handles admin notice CRUD: index, create, edit, status, delete, duplicate.
 * Delegates business logic to NoticeService.
 */
class AdminNoticeController
{
    private NoticeService $service;
    private ?PDO $db;

    public function __construct(NoticeService $service, ?PDO $db = null)
    {
        $this->service = $service;
        $this->db      = $db;
    }

    private function getDb(): PDO
    {
        if ($this->db !== null) {
            return $this->db;
        }
        global $db;
        return $db;
    }

    private function getClassroomsList(): array
    {
        $db = $this->getDb();
        $stmt = $db->query("
            SELECT c.id, c.class_name, c.class_code,
                   (SELECT COUNT(*) FROM classroom_students cs WHERE cs.classroom_id = c.id AND cs.status = 'active') as student_count,
                   (SELECT COUNT(*) FROM classroom_teachers ct WHERE ct.classroom_id = c.id) as teacher_count
            FROM classrooms c
            WHERE c.status = 'Active'
            ORDER BY c.class_name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getInitialUsers(array $selectedIds = []): array
    {
        $db = $this->getDb();
        require_once __DIR__ . '/../../Repositories/UserRepository.php';
        $userRepo = new \App\Repositories\UserRepository($db);
        $selectedUsers = !empty($selectedIds) ? $userRepo->getUsersByIds($selectedIds) : [];
        $recentUsers   = $userRepo->searchActiveUsers('', null, 15);

        $merged = [];
        foreach (array_merge($selectedUsers, $recentUsers) as $u) {
            $merged[$u['id']] = $u;
        }
        return array_values($merged);
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
                    $currentStatus = $_POST['status'] ?? '';
                    $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
                    $this->service->updateStatus($id, $newStatus);
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
                    $_SESSION['success_message'] = 'Notice duplicated successfully.';
                    header('Location: index.php');
                    exit;
                }

            } catch (\Exception $e) {
                $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }

        $search     = trim($_GET['search'] ?? '');
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $sortField  = in_array($_GET['sort'] ?? '', ['title', 'target_audience', 'target_type', 'status', 'created_at'], true) ? $_GET['sort'] : 'created_at';
        $sortOrder  = (strtoupper($_GET['order'] ?? '') === 'ASC') ? 'ASC' : 'DESC';
        $limit      = 10;

        $paginated  = $this->service->getPaginatedNotices($page, $limit, $search, $sortField, $sortOrder);
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

        $classrooms = $this->getClassroomsList();
        $users      = $this->getInitialUsers();

        $oldValues = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');

                $oldValues = [
                    'title'                => $_POST['title']                ?? '',
                    'content'              => $_POST['content']              ?? '',
                    'status'               => $_POST['status']               ?? 'active',
                    'target_type'          => $_POST['target_type']          ?? 'targeted',
                    'audience_student'     => !empty($_POST['audience_student']),
                    'audience_teacher'     => !empty($_POST['audience_teacher']),
                    'target_classroom_ids' => array_map('intval', (array) ($_POST['target_classroom_ids'] ?? [])),
                    'target_user_ids'      => array_map('intval', (array) ($_POST['target_user_ids'] ?? [])),
                ];

                $data = $_POST;
                $data['attachments'] = $_FILES['attachments'] ?? [];
                $this->service->createNotice($data, (int) $auth->getUserId());

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

        // Extract selected user IDs early to only load initial preselected users
        $selectedUserIds = [];
        if (!empty($notice['targets'])) {
            foreach ($notice['targets'] as $t) {
                if ($t['target_type'] === 'user') {
                    $selectedUserIds[] = (int) $t['target_id'];
                }
            }
        }

        $classrooms = $this->getClassroomsList();
        $users      = $this->getInitialUsers($selectedUserIds);

        $successMessage = $_SESSION['success_message'] ?? '';
        $errorMessage   = $_SESSION['error_message']   ?? '';
        unset($_SESSION['success_message'], $_SESSION['error_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');

                $data = $_POST;
                $data['attachments'] = $_FILES['attachments'] ?? [];
                $this->service->updateNotice($id, $data);

                $_SESSION['success_message'] = 'Notice updated successfully.';
                header('Location: index.php');
                exit;

            } catch (\Exception $e) {
                $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

                // Restore POST values for re-display
                $notice['title']                = $_POST['title']                ?? '';
                $notice['content']              = $_POST['content']              ?? '';
                $notice['status']               = (($_POST['status'] ?? '') === 'inactive') ? 'inactive' : 'active';
                $notice['target_type']          = (($_POST['target_type'] ?? '') === 'all') ? 'all' : 'targeted';
                $notice['audience_student']     = !empty($_POST['audience_student']);
                $notice['audience_teacher']     = !empty($_POST['audience_teacher']);
                $notice['selected_classrooms']  = array_map('intval', (array) ($_POST['target_classroom_ids'] ?? []));
                $notice['selected_users']       = array_map('intval', (array) ($_POST['target_user_ids'] ?? []));
            }
        } else {
            // Extract selected classrooms and users from existing targets
            $selectedClassroomIds = [];
            $selectedUserIds = [];
            $audienceStudent = ($notice['target_audience'] === 'student' || $notice['target_audience'] === 'both');
            $audienceTeacher = ($notice['target_audience'] === 'teacher' || $notice['target_audience'] === 'both');

            if (!empty($notice['targets'])) {
                $hasRoleTarget = false;
                foreach ($notice['targets'] as $t) {
                    if ($t['target_type'] === 'classroom') {
                        $selectedClassroomIds[] = (int) $t['target_id'];
                    } elseif ($t['target_type'] === 'user') {
                        $selectedUserIds[] = (int) $t['target_id'];
                    } elseif ($t['target_type'] === 'role') {
                        $hasRoleTarget = true;
                    }
                }
                if ($hasRoleTarget) {
                    $hasStudent = false;
                    $hasTeacher = false;
                    foreach ($notice['targets'] as $t) {
                        if ($t['target_type'] === 'role' && (int) $t['target_id'] === 4) $hasStudent = true;
                        if ($t['target_type'] === 'role' && (int) $t['target_id'] === 2) $hasTeacher = true;
                    }
                    $audienceStudent = $hasStudent;
                    $audienceTeacher = $hasTeacher;
                }
            }

            $notice['audience_student']    = $audienceStudent;
            $notice['audience_teacher']    = $audienceTeacher;
            $notice['selected_classrooms'] = $selectedClassroomIds;
            $notice['selected_users']      = $selectedUserIds;
        }

        require __DIR__ . '/../../../views/admin/notices/edit.php';
    }
}
