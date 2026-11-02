<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\ClassroomRepository;
use App\Repositories\UserRepository;
use App\Services\ClassroomService;

/**
 * AdminClassroomController — Thin Controller
 * Handles admin classroom CRUD: index, create, edit, delete.
 * Enforces Role-Based Access Control and strict observer access scoping.
 */
class AdminClassroomController
{
    private ClassroomService $service;

    public function __construct(ClassroomService $service)
    {
        $this->service = $service;
    }

    private function getUserId(): int
    {
        global $auth;
        return $auth ? (int) $auth->getUserId() : (int) ($GLOBALS['auth']?->getUserId() ?? 0);
    }

    private function canViewAllClassrooms(): bool
    {
        if (function_exists('isSuperAdmin') && isSuperAdmin()) {
            return true;
        }

        if (function_exists('hasPermission') && hasPermission('classrooms.view_all')) {
            return true;
        }

        return false;
    }

    // ─── GET /admin/classrooms/index.php ───────────────────────────
    public function index(): void
    {
        $userId = $this->getUserId();
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $search  = trim($_GET['search'] ?? '');
        $limit   = 20;

        $sort    = $_GET['sort'] ?? 'created_at';
        $order   = strtoupper($_GET['order'] ?? 'DESC');
        if (!in_array($sort, ['id', 'status', 'created_at'])) $sort = 'created_at';
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

        // Check if user has global view or is restricted to assigned observer classes
        $allowedIds = null; // null represents unrestricted
        if (!$this->canViewAllClassrooms()) {
            $allowedIds = (new \App\Repositories\ClassObserverRepository($GLOBALS['db']))->getAssignedClassroomIds($userId);
        }

        $result      = $this->service->getPaginatedClassrooms($page, $limit, $search, $sort, $order, $allowedIds);
        $classrooms  = $result['data'];
        $totalPages  = $result['pages'];
        $currentPage = $result['current_page'];

        $pageTitle  = 'Manage Classrooms';
        $activeMenu = 'classrooms_manage';

        require __DIR__ . '/../../../views/admin/classrooms/index.php';
    }

    // ─── GET  /admin/classrooms/create.php ──────────────────────────
    // ─── POST /admin/classrooms/create.php ──────────────────────────
    public function create(): void
    {
        global $auth;

        if (function_exists('requirePermission')) {
            requirePermission('classrooms.create');
        }

        $pageTitle  = 'Create Classroom';
        $activeMenu = 'classrooms_create';
        $error      = '';
        $success    = '';

        // Fetch teachers & students via service (which uses UserRepository)
        $teachers          = $this->service->getUsersByRole(ROLE_TEACHER);
        $students          = $this->service->getUsersByRole(ROLE_STUDENT);
        $eligibleObservers = $this->service->getEligibleObservers();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');
                $this->service->createClassroom($_POST, (int) $auth->getUserId());
                $success = 'Classroom created successfully.';
            } catch (\Exception $e) {
                $error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }

        require __DIR__ . '/../../../views/admin/classrooms/create.php';
    }

    // ─── GET  /admin/classrooms/edit.php?id=X ───────────────────────
    // ─── POST /admin/classrooms/edit.php ────────────────────────────
    public function edit(): void
    {
        $id        = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
        $classroom = $this->service->getClassroomDetails($id);

        if (!$classroom) {
            http_response_code(404);
            echo '<h1>404 Not Found</h1><p>Classroom not found.</p>';
            return;
        }

        $userId = $this->getUserId();

        // Security / IDOR Check: Ensure user is Super Admin, has view_all, or is assigned observer
        if (!$this->canViewAllClassrooms()) {
            $isObserver = (new \App\Repositories\ClassObserverRepository($GLOBALS['db']))->isUserObserverForClassroom($id, $userId);
            if (!$isObserver) {
                if (function_exists('renderForbiddenResponse')) {
                    renderForbiddenResponse('Access Denied: You are not assigned to observe this classroom.');
                }
                http_response_code(403);
                exit('<h1>403 Forbidden</h1><p>Access Denied: You are not assigned to observe this classroom.</p>');
            }
        }

        $pageTitle           = 'Edit Classroom';
        $activeMenu          = 'classrooms_manage';
        $error               = '';
        $success             = '';
        $eligibleObservers   = $this->service->getEligibleObservers();
        $assignedObserverIds = $this->service->getClassroomObserverIds($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // If user doesn't have edit permission, block POST changes
                if (function_exists('hasPermission') && !hasPermission('classrooms.edit') && !(function_exists('isSuperAdmin') && isSuperAdmin())) {
                    throw new \RuntimeException('You do not have permission to modify classroom settings.');
                }

                validateCsrfToken($_POST['csrf_token'] ?? '');
                $this->service->updateClassroom($id, $_POST, $userId);
                $success             = 'Classroom updated successfully.';
                $classroom           = $this->service->getClassroomDetails($id); // refresh
                $assignedObserverIds = $this->service->getClassroomObserverIds($id);
            } catch (\Exception $e) {
                $error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }

        require __DIR__ . '/../../../views/admin/classrooms/edit.php';
    }

    // ─── POST /admin/classrooms/delete.php?id=X ─────────────────────
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Location: /admin/classrooms/index.php');
            exit;
        }

        if (function_exists('requirePermission')) {
            requirePermission('classrooms.delete');
        }

        $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));

        if ($id <= 0) {
            header('Location: /admin/classrooms/index.php?error=invalid_id');
            exit;
        }

        try {
            validateCsrfToken($_POST['csrf_token'] ?? '');
            $this->service->deleteClassroom($id);
            header('Location: /admin/classrooms/index.php?deleted=1');
        } catch (\Exception $e) {
            error_log('[AdminClassroomController::delete] ' . $e->getMessage());
            header('Location: /admin/classrooms/index.php?error=delete_failed');
        }
        exit;
    }
}
