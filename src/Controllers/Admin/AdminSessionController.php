<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Exceptions\TimeSlotConflictException;
use App\Exceptions\ScheduleConflictException;
use App\Services\Sessions\ClassSessionService;
use App\Repositories\ClassSessionRepository;
use App\Repositories\ClassroomRepository;
use App\Repositories\ClassObserverRepository;

/**
 * AdminSessionController
 *
 * Handles all admin-facing session management actions.
 * Enforces strict observer access control and IDOR protections.
 */
class AdminSessionController
{
    private ClassSessionService    $sessionService;
    private ClassSessionRepository $sessionRepo;
    private ClassroomRepository    $classroomRepo;
    private ?\Delight\Auth\Auth    $auth;
    private ?\App\Repositories\ProviderAccountRepository $providerRepo;
    private ?ClassObserverRepository $observerRepo;

    public function __construct(
        ClassSessionService    $sessionService,
        ClassSessionRepository $sessionRepo,
        ClassroomRepository    $classroomRepo,
        ?\Delight\Auth\Auth    $auth = null,
        ?\App\Repositories\ProviderAccountRepository $providerRepo = null,
        ?ClassObserverRepository $observerRepo = null
    ) {
        $this->sessionService = $sessionService;
        $this->sessionRepo    = $sessionRepo;
        $this->classroomRepo  = $classroomRepo;
        $this->auth           = $auth ?? ($GLOBALS['auth'] ?? null);
        $this->providerRepo   = $providerRepo;
        $this->observerRepo   = $observerRepo ?? (isset($GLOBALS['db']) ? new ClassObserverRepository($GLOBALS['db']) : null);
    }

    private function getUserId(): int
    {
        return $this->auth ? (int) $this->auth->getUserId() : (int) ($GLOBALS['auth']?->getUserId() ?? 0);
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

    private function getObserverRepo(): ClassObserverRepository
    {
        if ($this->observerRepo === null) {
            $db = $GLOBALS['db'] ?? null;
            $this->observerRepo = new ClassObserverRepository($db);
        }
        return $this->observerRepo;
    }

    private function getProviderRepo(): \App\Repositories\ProviderAccountRepository
    {
        if ($this->providerRepo === null) {
            $db = $GLOBALS['db'] ?? null;
            $this->providerRepo = new \App\Repositories\ProviderAccountRepository($db);
        }
        return $this->providerRepo;
    }

    /**
     * GET /admin/sessions/index.php?classroom_id=X
     * List all sessions for a classroom (paginated)
     */
    public function index(): void
    {
        $classroomId = isset($_GET['classroom_id']) ? (int) $_GET['classroom_id'] : 0;
        $classroom   = $this->classroomRepo->findById($classroomId);

        if (!$classroom) {
            http_response_code(404);
            die('<h1>Classroom not found.</h1>');
        }

        $userId = $this->getUserId();

        // IDOR / Security check: verify user has access to view this classroom's sessions
        if (!$this->canViewAllClassrooms()) {
            $isObserver = $this->getObserverRepo()->isUserObserverForClassroom($classroomId, $userId);
            if (!$isObserver) {
                if (function_exists('renderForbiddenResponse')) {
                    renderForbiddenResponse('Access Denied: You are not assigned to observe this classroom.');
                }
                http_response_code(403);
                die('<h1>403 Forbidden</h1><p>Access Denied: You are not assigned to observe this classroom.</p>');
            }
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

        // Check observer state for action rendering in view
        $isObserver = $this->getObserverRepo()->isUserObserverForClassroom($classroomId, $userId);

        require __DIR__ . '/../../../views/admin/sessions/index.php';
    }

    /**
     * GET/POST /admin/sessions/create.php?classroom_id=X
     * Create single or bulk sessions
     */
    public function create(): void
    {
        $classroomId = isset($_GET['classroom_id']) ? (int) $_GET['classroom_id'] : 0;
        $classroom   = $this->classroomRepo->findById($classroomId);

        if (!$classroom) {
            http_response_code(404);
            die('<h1>Classroom not found.</h1>');
        }

        // Permission check: only staff with sessions.create or Super Admin can schedule sessions
        if (function_exists('requirePermission')) {
            requirePermission('sessions.create');
        }

        $error                = '';
        $success              = '';
        $result               = null;
        $timeConflictError    = null;
        $scheduleConflictError = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');

                $mode = $_POST['mode'] ?? 'single'; // 'single' or 'bulk'

                if ($mode === 'single') {
                    $data = array_merge($_POST, ['classroom_id' => $classroomId]);
                    $out  = $this->sessionService->createSingleSession($data, $this->getUserId());

                    $success = $out['meeting_ok']
                        ? 'Session created and meeting link generated successfully!'
                        : 'Session created but meeting generation failed: ' . ($out['error'] ?? '');
                    $result  = $out;
                } else {
                    $rawDates = $_POST['dates'] ?? [];
                    $dates    = [];

                    foreach ($rawDates as $i => $date) {
                        if (!empty($date)) {
                            $dates[] = ['session_date' => $date, 'session_number' => $i + 1];
                        }
                    }

                    $data = array_merge($_POST, ['classroom_id' => $classroomId]);
                    $out  = $this->sessionService->createBulkSessions($data, $dates, $this->getUserId());

                    $conflictSkipsCount = count($out['conflict_skips'] ?? []);
                    $skipNote = $conflictSkipsCount > 0
                        ? " ({$conflictSkipsCount} skipped due to schedule conflicts)"
                        : '';

                    $success = "Bulk generation complete: {$out['succeeded']} succeeded, {$out['failed']} failed out of {$out['total']} sessions{$skipNote}.";
                    $result  = $out;

                    if (!empty($out['conflict_skips'])) {
                        $scheduleConflictError = [
                            'type'    => 'bulk_skips',
                            'skips'   => $out['conflict_skips'],
                        ];
                    }
                }
            } catch (ScheduleConflictException $e) {
                $scheduleConflictError = [
                    'type'         => $e->getConflictType(),
                    'person_name'  => $e->getPersonName(),
                    'date'         => $e->getSessionDate(),
                    'start_time'   => $e->getStartTime(),
                    'end_time'     => $e->getEndTime(),
                    'class_name'   => $e->getConflictClassName(),
                ];
            } catch (TimeSlotConflictException $e) {
                $timeConflictError = [
                    'provider'   => $e->getProvider(),
                    'date'       => $e->getSessionDate(),
                    'start_time' => $e->getStartTime(),
                    'end_time'   => $e->getEndTime(),
                ];
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        $pageTitle  = "Schedule Sessions — {$classroom['class_name']}";
        $activeMenu = 'classrooms_manage';
        $timezones  = \DateTimeZone::listIdentifiers();

        $providerRepo = $this->getProviderRepo();
        $settingsDb = $providerRepo->getMeetingSettings(['default_provider', 'default_timezone']);
        $defaultProvider = $settingsDb['default_provider'] ?? 'zoom';
        $defaultTimezone = $settingsDb['default_timezone'] ?? 'Asia/Dhaka';

        $googleAccounts = $providerRepo->findAllByProvider('google_meet');
        $zoomAccounts = $providerRepo->findAllByProvider('zoom');

        // Fetch assigned classroom observers for display
        $classroomObservers = $this->getObserverRepo()->getClassroomObservers($classroomId);

        require __DIR__ . '/../../../views/admin/sessions/create.php';
    }

    /**
     * GET/POST /admin/sessions/edit.php?id=X
     */
    public function edit(): void
    {
        $sessionId  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $session    = $this->sessionRepo->findById($sessionId);

        if (!$session) {
            http_response_code(404);
            die('<h1>Session not found.</h1>');
        }

        // Permission check
        if (function_exists('requirePermission')) {
            requirePermission('sessions.edit');
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
        if (function_exists('requirePermission')) {
            requirePermission('sessions.cancel');
        }

        $sessionId   = (int) ($_POST['session_id'] ?? 0);
        $classroomId = (int) ($_POST['classroom_id'] ?? 0);
        $reason      = htmlspecialchars(trim($_POST['reason'] ?? ''));

        try {
            validateCsrfToken($_POST['csrf_token'] ?? '');
            $this->sessionService->cancelSession($sessionId, $this->getUserId(), $reason);
        } catch (\Exception $e) {
            error_log('[AdminSessionController::cancel] Error: ' . $e->getMessage());
        }

        header("Location: /admin/sessions/index.php?classroom_id={$classroomId}&cancelled=1");
        exit;
    }

    /**
     * POST /admin/sessions/retry.php
     * Retry failed meeting generation
     */
    public function retry(): void
    {
        if (function_exists('requirePermission')) {
            requirePermission('sessions.retry');
        }

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
