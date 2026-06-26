<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Services\Sessions\ClassSessionService;
use App\Repositories\ClassSessionRepository;
use App\Repositories\ClassroomRepository;

/**
 * AdminSessionController
 *
 * Handles all admin-facing session management actions.
 * Wires into existing classrooms module cleanly.
 */
class AdminSessionController
{
    private ClassSessionService    $sessionService;
    private ClassSessionRepository $sessionRepo;
    private ClassroomRepository    $classroomRepo;

    public function __construct(
        ClassSessionService    $sessionService,
        ClassSessionRepository $sessionRepo,
        ClassroomRepository    $classroomRepo
    ) {
        $this->sessionService = $sessionService;
        $this->sessionRepo    = $sessionRepo;
        $this->classroomRepo  = $classroomRepo;
    }

    /**
     * GET /admin/sessions/index.php?classroom_id=X
     * List all sessions for a classroom (paginated)
     */
    public function index(): void
    {
        global $auth;

        $classroomId = isset($_GET['classroom_id']) ? (int) $_GET['classroom_id'] : 0;
        $classroom   = $this->classroomRepo->findById($classroomId);

        if (!$classroom) {
            http_response_code(404);
            die('<h1>Classroom not found.</h1>');
        }

        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $status = trim($_GET['status'] ?? '');
        $limit  = 20;

        $result   = $this->sessionService->getPaginatedSessions($classroomId, $page, $limit, $status);
        $sessions = $result['data'];
        $total    = $result['total'];
        $pages    = $result['pages'];

        $pageTitle  = "Live Sessions — {$classroom['class_name']}";
        $activeMenu = 'classrooms_manage';

        require __DIR__ . '/../../../views/admin/sessions/index.php';
    }

    /**
     * GET/POST /admin/sessions/create.php?classroom_id=X
     * Create single or bulk sessions
     */
    public function create(): void
    {
        global $auth;

        $classroomId = isset($_GET['classroom_id']) ? (int) $_GET['classroom_id'] : 0;
        $classroom   = $this->classroomRepo->findById($classroomId);

        if (!$classroom) {
            http_response_code(404);
            die('<h1>Classroom not found.</h1>');
        }

        $error   = '';
        $success = '';
        $result  = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');

                $mode = $_POST['mode'] ?? 'single'; // 'single' or 'bulk'

                if ($mode === 'single') {
                    $data = array_merge($_POST, ['classroom_id' => $classroomId]);
                    $out  = $this->sessionService->createSingleSession($data, $auth->getUserId());

                    $success = $out['meeting_ok']
                        ? 'Session created and meeting link generated successfully!'
                        : 'Session created but meeting generation failed: ' . ($out['error'] ?? '');
                    $result  = $out;
                } else {
                    // Bulk: parse submitted dates array
                    $rawDates = $_POST['dates'] ?? [];
                    $dates    = [];

                    foreach ($rawDates as $i => $date) {
                        if (!empty($date)) {
                            $dates[] = ['session_date' => $date, 'session_number' => $i + 1];
                        }
                    }

                    $data = array_merge($_POST, ['classroom_id' => $classroomId]);
                    $out  = $this->sessionService->createBulkSessions($data, $dates, $auth->getUserId());

                    $success = "Bulk generation complete: {$out['succeeded']} succeeded, {$out['failed']} failed out of {$out['total']} sessions.";
                    $result  = $out;
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        $pageTitle  = "Schedule Sessions — {$classroom['class_name']}";
        $activeMenu = 'classrooms_manage';
        $timezones  = \DateTimeZone::listIdentifiers();

        require __DIR__ . '/../../../views/admin/sessions/create.php';
    }

    /**
     * GET/POST /admin/sessions/edit.php?id=X
     */
    public function edit(): void
    {
        global $auth;

        $sessionId  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $session    = $this->sessionRepo->findById($sessionId);

        if (!$session) {
            http_response_code(404);
            die('<h1>Session not found.</h1>');
        }

        $error   = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');
                $this->sessionService->updateSession($sessionId, $_POST);
                $success = 'Session updated successfully.';
                $session = $this->sessionRepo->findById($sessionId); // refresh
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        $pageTitle  = 'Edit Session';
        $activeMenu = 'classrooms_manage';
        $timezones  = \DateTimeZone::listIdentifiers();

        require __DIR__ . '/../../../views/admin/sessions/edit.php';
    }

    /**
     * POST /admin/sessions/cancel.php
     */
    public function cancel(): void
    {
        global $auth;

        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $reason    = htmlspecialchars(trim($_POST['reason'] ?? ''));

        try {
            validateCsrfToken($_POST['csrf_token'] ?? '');
            $this->sessionService->cancelSession($sessionId, $auth->getUserId(), $reason);
            $classroomId = (int) ($_POST['classroom_id'] ?? 0);
        } catch (\Exception $e) {
            // Log error in production
        }

        $classroomId = $classroomId ?? 0;
        header("Location: /admin/sessions/index.php?classroom_id={$classroomId}&cancelled=1");
        exit;
    }

    /**
     * POST /admin/sessions/retry.php
     * Retry failed meeting generation
     */
    public function retry(): void
    {
        global $auth;

        $sessionId   = (int) ($_POST['session_id'] ?? 0);
        $classroomId = (int) ($_POST['classroom_id'] ?? 0);

        try {
            validateCsrfToken($_POST['csrf_token'] ?? '');
            $ok = $this->sessionService->retryFailedSession($sessionId);
            $msg = $ok ? 'retry_ok' : 'retry_failed';
        } catch (\Exception $e) {
            $msg = 'retry_error';
        }

        header("Location: /admin/sessions/index.php?classroom_id={$classroomId}&{$msg}=1");
        exit;
    }
}
