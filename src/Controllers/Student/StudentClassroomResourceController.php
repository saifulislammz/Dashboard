<?php

declare(strict_types=1);

namespace App\Controllers\Student;

use App\Repositories\ClassroomResourceRepository;
use App\Services\ClassroomResourceService;
use Exception;

/**
 * StudentClassroomResourceController
 * Serves the student's central study materials and resource hub, and handles assignment submissions.
 */
class StudentClassroomResourceController
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
     * GET /student/resources.php
     */
    public function index(): void
    {
        $studentId = $this->getUserId();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $search = trim($_GET['search'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $classroomId = !empty($_GET['classroom_id']) ? (int) $_GET['classroom_id'] : null;
        $limit = 15;

        $result = $this->repository->getStudentAllResources(
            $studentId,
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

        // Get enrolled classrooms for filter dropdown
        global $db;
        $stmtC = $db->prepare("
            SELECT c.id, c.class_name, c.class_title, c.class_code 
            FROM classrooms c
            INNER JOIN classroom_students cs ON c.id = cs.classroom_id
            WHERE cs.user_id = :sid AND cs.status = 'active' AND c.status = 'Active'
            ORDER BY c.class_name ASC
        ");
        $stmtC->execute([':sid' => $studentId]);
        $enrolledClassrooms = $stmtC->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $pageTitle  = 'My Study Materials & Resources';
        $activeMenu = 'student_resources';

        require __DIR__ . '/../../../views/student/resources/index.php';
    }

    /**
     * POST /student/submit_assignment.php
     */
    public function submitAssignment(): void
    {
        $studentId = $this->getUserId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resourceId = (int) ($_POST['resource_id'] ?? 0);
            $redirectUrl = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : '/student/resources.php';

            try {
                if (function_exists('validateCsrfToken')) {
                    validateCsrfToken($_POST['csrf_token'] ?? '');
                }

                $this->service->submitAssignment(
                    $resourceId,
                    $studentId,
                    $_POST,
                    $_FILES['submission_file'] ?? null
                );

                $_SESSION['flash_success'] = "Assignment submitted successfully!";
            } catch (Exception $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }

            header('Location: ' . $redirectUrl);
            exit;
        }

        header('Location: /student/resources.php');
        exit;
    }
}

