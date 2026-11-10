<?php

declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Repositories\ClassroomResourceRepository;
use App\Services\ClassroomResourceService;
use Exception;

/**
 * TeacherClassroomResourceController
 * Allows teachers to upload and bulk distribute resources to their assigned group classrooms,
 * and review/grade student assignment submissions.
 */
class TeacherClassroomResourceController
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
        return (int) $auth->getUserId();
    }

    /**
     * GET /teacher/resources/index.php
     */
    public function index(): void
    {
        $teacherId = $this->getUserId();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $classroomId = !empty($_GET['classroom_id']) ? (int) $_GET['classroom_id'] : null;
        $limit = 15;

        // Teacher only sees resources in their assigned classrooms or created by them
        $result = $this->repository->getPaginatedResources(
            $page,
            $limit,
            $search ?: null,
            $type ?: null,
            $classroomId,
            $teacherId
        );

        $resources   = $result['data'];
        $totalPages  = $result['pages'];
        $currentPage = $result['current_page'];
        $totalItems  = $result['total'];

        $classrooms = $this->service->getEligibleClassrooms($teacherId, false);

        $pageTitle  = 'My Classroom Resources';
        $activeMenu = 'teacher_resources';

        require __DIR__ . '/../../../views/teacher/resources/index.php';
    }

    /**
     * GET / POST /teacher/resources/create.php
     */
    public function create(): void
    {
        $teacherId = $this->getUserId();
        $classrooms = $this->service->getEligibleClassrooms($teacherId, false);
        $quizzes = $this->service->getQuizzesList();

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (function_exists('validateCsrfToken')) {
                    validateCsrfToken($_POST['csrf_token'] ?? '');
                }

                $this->service->createAndDistribute($_POST, $_FILES['attachment'] ?? null, $teacherId, false);

                $_SESSION['flash_success'] = "Resource distributed to your group classroom(s) successfully!";
                header('Location: /teacher/resources/index.php');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        $pageTitle  = 'Distribute Resource to Class';
        $activeMenu = 'teacher_resources_create';

        require __DIR__ . '/../../../views/teacher/resources/create.php';
    }

    /**
     * GET /teacher/resources/submissions.php?id={resource_id}
     * POST /teacher/resources/submissions.php (for grading)
     */
    public function submissions(): void
    {
        $teacherId = $this->getUserId();
        $resourceId = (int) ($_GET['id'] ?? ($_POST['resource_id'] ?? 0));
        $resource = $this->repository->findById($resourceId);

        if (!$resource || $resource['resource_type'] !== 'assignment') {
            http_response_code(404);
            die('Assignment resource not found.');
        }

        // Verify teacher belongs to at least one classroom where this resource is distributed
        if (!$this->repository->canUserAccessResource($resourceId, $teacherId, 2)) {
            http_response_code(403);
            die('Unauthorized access to this assignment.');
        }

        // Handle grading POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (function_exists('validateCsrfToken')) {
                    validateCsrfToken($_POST['csrf_token'] ?? '');
                }

                $submissionId = (int) ($_POST['submission_id'] ?? 0);
                $points       = (float) ($_POST['points_awarded'] ?? 0);
                $feedback     = trim($_POST['teacher_feedback'] ?? '');

                $this->service->gradeSubmission($submissionId, $points, $feedback, $teacherId, false);
                $_SESSION['flash_success'] = "Student assignment graded successfully.";
                header('Location: /teacher/resources/submissions.php?id=' . $resourceId);
                exit;
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                header('Location: /teacher/resources/submissions.php?id=' . $resourceId);
                exit;
            }
        }

        $submissions = $this->repository->getSubmissionsByResource($resourceId);
        $distributedClassrooms = $this->repository->getDistributedClassrooms($resourceId);

        $pageTitle  = 'Review Assignment Submissions';
        $activeMenu = 'teacher_resources';

        require __DIR__ . '/../../../views/teacher/resources/submissions.php';
    }
}

