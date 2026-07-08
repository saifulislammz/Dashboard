<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\ClassroomRepository;
use App\Repositories\UserRepository;
use App\Services\ClassroomService;

/**
 * AdminClassroomController — Thin Controller
 * Handles admin classroom CRUD: index, create, edit, delete.
 * No business logic, no SQL here — delegates to ClassroomService.
 */
class AdminClassroomController
{
    private ClassroomService $service;

    public function __construct(ClassroomService $service)
    {
        $this->service = $service;
    }

    // ─── GET /admin/classrooms/index.php ───────────────────────────
    public function index(): void
    {
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $search  = trim($_GET['search'] ?? '');
        $limit   = 20;

        $result      = $this->service->getPaginatedClassrooms($page, $limit, $search);
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

        $pageTitle  = 'Create Classroom';
        $activeMenu = 'classrooms_create';
        $error      = '';
        $success    = '';

        // Fetch teachers & students via service (which uses UserRepository)
        $teachers = $this->service->getUsersByRole(ROLE_TEACHER);
        $students = $this->service->getUsersByRole(ROLE_STUDENT);

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

        $pageTitle  = 'Edit Classroom';
        $activeMenu = 'classrooms_manage';
        $error      = '';
        $success    = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');
                $this->service->updateClassroom($id, $_POST);
                $success   = 'Classroom updated successfully.';
                $classroom = $this->service->getClassroomDetails($id); // refresh
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
