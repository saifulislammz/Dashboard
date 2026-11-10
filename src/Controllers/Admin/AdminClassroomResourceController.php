<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\ClassroomResourceRepository;
use App\Services\ClassroomResourceService;
use Exception;

/**
 * AdminClassroomResourceController — Thin Controller
 * Handles administrative classroom resource management, bulk distribution to group classrooms,
 * and assignment evaluations.
 */
class AdminClassroomResourceController
{
    private ClassroomResourceService $service;
    private ClassroomResourceRepository $repository;

    public function __construct(ClassroomResourceService $service, ClassroomResourceRepository $repository)
    {
        $this->service = $service;
        $this->repository = $repository;
    }

    private function getUserId(): int
    {
        global $auth;
        return $auth ? (int) $auth->getUserId() : (int) ($GLOBALS['auth']?->getUserId() ?? 0);
    }

    /**
     * GET /admin/resources/index.php
     */
    public function index(): void
    {
        if (function_exists('requirePermission')) {
            requirePermission('classroom_resources.view');
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $classroomId = !empty($_GET['classroom_id']) ? (int) $_GET['classroom_id'] : null;
        $limit = 15;

        $result = $this->repository->getPaginatedResources(
            $page,
            $limit,
            $search ?: null,
            $type ?: null,
            $classroomId
        );

        $resources   = $result['data'];
        $totalPages  = $result['pages'];
        $currentPage = $result['current_page'];
        $totalItems  = $result['total'];

        $classrooms = $this->service->getEligibleClassrooms($this->getUserId(), true);

        $pageTitle  = 'Classroom Resources & Distribution';
        $activeMenu = 'classroom_resources';

        require __DIR__ . '/../../../views/admin/resources/index.php';
    }

    /**
     * GET / POST /admin/resources/create.php
     */
    public function create(): void
    {
        if (function_exists('requirePermission')) {
            requirePermission('classroom_resources.distribute');
        }

        $userId = $this->getUserId();
        $classrooms = $this->service->getEligibleClassrooms($userId, true);
        $quizzes = $this->service->getQuizzesList();

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (function_exists('validateCsrfToken')) {
                    validateCsrfToken($_POST['csrf_token'] ?? '');
                }

                $resourceId = $this->service->createAndDistribute($_POST, $_FILES['attachment'] ?? null, $userId, true);

                $targetCount = count($_POST['classroom_ids'] ?? []);
                $_SESSION['flash_success'] = "Resource created and successfully distributed to {$targetCount} Group Classroom(s)!";
                header('Location: /admin/resources/index.php');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $pageTitle  = 'Distribute Resource to Group Classrooms';
        $activeMenu = 'classroom_resources_create';

        require __DIR__ . '/../../../views/admin/resources/create.php';
    }

    /**
     * GET / POST /admin/resources/edit.php
     */
    public function edit(): void
    {
        if (function_exists('requirePermission')) {
            requirePermission('classroom_resources.edit');
        }

        $id = (int) ($_GET['id'] ?? 0);
        $resource = $this->repository->findById($id);

        if (!$resource) {
            http_response_code(404);
            die('Resource not found.');
        }

        $userId = $this->getUserId();
        $classrooms = $this->service->getEligibleClassrooms($userId, true);
        $quizzes = $this->service->getQuizzesList();
        $selectedClassroomIds = $this->repository->getDistributedClassroomIds($id);

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (function_exists('validateCsrfToken')) {
                    validateCsrfToken($_POST['csrf_token'] ?? '');
                }

                $this->service->updateResource($id, $_POST, $_FILES['attachment'] ?? null, $userId, true);

                $_SESSION['flash_success'] = "Resource updated successfully.";
                header('Location: /admin/resources/view.php?id=' . $id);
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $pageTitle  = 'Edit Classroom Resource';
        $activeMenu = 'classroom_resources';

        require __DIR__ . '/../../../views/admin/resources/edit.php';
    }

    /**
     * GET /admin/resources/view.php
     */
    public function view(): void
    {
        if (function_exists('requirePermission')) {
            requirePermission('classroom_resources.view');
        }

        $id = (int) ($_GET['id'] ?? 0);
        $resource = $this->repository->findById($id);

        if (!$resource) {
            http_response_code(404);
            die('Resource not found.');
        }

        $distributedClassrooms = $this->repository->getDistributedClassrooms($id);
        $submissions = [];
        if ($resource['resource_type'] === 'assignment') {
            $submissions = $this->repository->getSubmissionsByResource($id);
        }

        $pageTitle  = 'Resource Details & Submissions';
        $activeMenu = 'classroom_resources';

        require __DIR__ . '/../../../views/admin/resources/view.php';
    }

    /**
     * POST /admin/resources/delete.php
     */
    public function delete(): void
    {
        if (function_exists('requirePermission')) {
            requirePermission('classroom_resources.delete');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (function_exists('validateCsrfToken')) {
                    validateCsrfToken($_POST['csrf_token'] ?? '');
                }

                $id = (int) ($_POST['id'] ?? 0);
                $this->service->deleteResource($id, $this->getUserId(), true);

                $_SESSION['flash_success'] = "Resource deleted successfully.";
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }

        header('Location: /admin/resources/index.php');
        exit;
    }

    /**
     * POST /admin/resources/grade.php
     */
    public function grade(): void
    {
        if (function_exists('requirePermission')) {
            requirePermission('classroom_resources.grade');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (function_exists('validateCsrfToken')) {
                    validateCsrfToken($_POST['csrf_token'] ?? '');
                }

                $submissionId = (int) ($_POST['submission_id'] ?? 0);
                $resourceId   = (int) ($_POST['resource_id'] ?? 0);
                $points       = (float) ($_POST['points_awarded'] ?? 0);
                $feedback     = trim($_POST['teacher_feedback'] ?? '');

                $this->service->gradeSubmission($submissionId, $points, $feedback, $this->getUserId(), true);
                $_SESSION['flash_success'] = "Student assignment graded successfully.";
                header('Location: /admin/resources/view.php?id=' . $resourceId);
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                header('Location: /admin/resources/view.php?id=' . (int)($_POST['resource_id'] ?? 0));
                exit;
            }
        }
    }
}

