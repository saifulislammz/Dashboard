<?php

declare(strict_types=1);

namespace App\Controllers\Session;

use App\Repositories\ClassObserverRepository;
use App\Repositories\ClassSessionRepository;
use App\Repositories\SessionMeetingRepository;
use App\Services\AttendanceService;
use PDO;

/**
 * SessionJoinController
 *
 * Handles the secure join flow for live sessions:
 * - Validates session and meeting status
 * - Enforces role-based access control (Teacher, Student, Observer, Super Admin)
 * - Checks time windows
 * - Records isolated attendance (role: teacher, student, observer)
 * - Guarantees host vs participant URL separation (only teacher gets start_url; observers get join_url)
 */
class SessionJoinController
{
    private PDO                      $db;
    private ClassSessionRepository   $sessionRepo;
    private SessionMeetingRepository $meetingRepo;
    private AttendanceService        $attendanceService;
    private ClassObserverRepository  $observerRepo;

    public function __construct(
        PDO                      $db,
        ClassSessionRepository   $sessionRepo,
        SessionMeetingRepository $meetingRepo,
        AttendanceService        $attendanceService,
        ?ClassObserverRepository $observerRepo = null
    ) {
        $this->db                = $db;
        $this->sessionRepo       = $sessionRepo;
        $this->meetingRepo       = $meetingRepo;
        $this->attendanceService = $attendanceService;
        $this->observerRepo      = $observerRepo ?? new ClassObserverRepository($db);
    }

    public function handleJoin(int $sessionId): void
    {
        global $auth;

        $userId = $auth ? (int) $auth->getUserId() : (int) ($GLOBALS['auth']?->getUserId() ?? 0);
        if (!$userId) {
            header('Location: /login.php');
            exit;
        }

        $session = $this->sessionRepo->findById($sessionId);
        if (!$session) {
            $this->showError("Class session not found.");
            return;
        }

        if ($session['status'] === 'cancelled') {
            $this->showError("This class session has been cancelled.");
            return;
        }

        $meeting = $this->meetingRepo->findBySessionId($sessionId);
        if (!$meeting || $meeting['generation_status'] !== 'success' || empty($meeting['join_url'])) {
            $this->showError("Meeting link is not ready yet. Please try again later.");
            return;
        }

        // -------------------------------------------------------
        // 1. Role & Access Authorization Checks
        // -------------------------------------------------------
        $isSuperAdmin = function_exists('isSuperAdmin') && isSuperAdmin();
        $isTeacher    = ((int) $session['classroom_id'] > 0 && $this->sessionRepo->teacherBelongsToSession($sessionId, $userId));
        $isStudent    = ((int) $session['classroom_id'] > 0 && $this->sessionRepo->studentBelongsToSession($sessionId, $userId));
        $isObserver   = $this->observerRepo->isUserObserverForSession($sessionId, $userId);

        // Security / IDOR Gate: Deny access if user has no legitimate relationship with this session
        if (!$isSuperAdmin && !$isTeacher && !$isStudent && !$isObserver) {
            $this->showError("Access Denied: You are not authorized to join or observe this class session.");
            return;
        }

        // -------------------------------------------------------
        // 2. Time Window Logic
        // -------------------------------------------------------
        // Fetch join window settings
        $stmtSettings = $this->db->query("
            SELECT setting_key, setting_val 
            FROM meeting_settings 
            WHERE setting_key IN ('join_open_minutes_before', 'teacher_join_open_minutes_before')
        ");
        $settings       = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        $minutesBefore  = (int) ($settings['join_open_minutes_before'] ?? 10);
        $teacherMinutes = isset($settings['teacher_join_open_minutes_before'])
            ? (int) $settings['teacher_join_open_minutes_before']
            : $minutesBefore;

        $tz      = new \DateTimeZone($session['timezone'] ?: 'Asia/Dhaka');
        $startDt = new \DateTime("{$session['session_date']} {$session['start_time']}", $tz);
        $endDt   = new \DateTime("{$session['session_date']} {$session['end_time']}", $tz);
        $now     = new \DateTime('now', $tz);

        // Super Admin bypasses time gates
        if (!$isSuperAdmin) {
            // Observers and teachers can join with teacher window; students with student window
            $openMinutes = ($isTeacher || $isObserver) ? $teacherMinutes : $minutesBefore;
            $openDt      = (clone $startDt)->modify("-{$openMinutes} minutes");

            if ($now < $openDt) {
                $roleLabel = $isObserver ? 'Observer' : ($isTeacher ? 'Teacher' : 'Student');
                $this->showError(
                    "Class is not open yet. {$roleLabel}s can join {$openMinutes} minute" . ($openMinutes === 1 ? '' : 's') . " before the start time."
                );
                return;
            }

            if ($now > $endDt) {
                $this->showError("This class session has already ended.");
                return;
            }
        }

        // -------------------------------------------------------
        // 3. Record Attendance & Audit Trail
        // -------------------------------------------------------
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($isTeacher) {
            $attendanceRole = 'teacher';
        } elseif ($isObserver) {
            $attendanceRole = 'observer';
        } elseif ($isStudent) {
            $attendanceRole = 'student';
        } else {
            // Super Admin observing
            $attendanceRole = 'observer';
        }

        // Record attendance (idempotent, isolated role)
        $this->attendanceService->recordAttendance($sessionId, $userId, $ipAddress, $attendanceRole);

        // If observer or super admin, log specific observation event in session_audit_log
        if ($isObserver || $isSuperAdmin) {
            try {
                $stmtAudit = $this->db->prepare("
                    INSERT INTO session_audit_log (session_id, user_id, action, details, ip_address, user_agent, created_at)
                    VALUES (:session_id, :user_id, :action, :details, :ip_address, :user_agent, NOW())
                ");
                $stmtAudit->execute([
                    'session_id' => $sessionId,
                    'user_id'    => $userId,
                    'action'     => $isObserver ? 'observer_joined' : 'super_admin_observed',
                    'details'    => json_encode([
                        'role'         => $attendanceRole,
                        'classroom_id' => $session['classroom_id'] ?? null,
                        'session_date' => $session['session_date'] ?? null,
                    ]),
                    'ip_address' => $ipAddress,
                    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500),
                ]);
            } catch (\Throwable $e) {
                // Non-blocking audit failure
                error_log('[SessionJoinController] Audit log error: ' . $e->getMessage());
            }
        }

        // -------------------------------------------------------
        // 4. Determine Redirection URL (Host vs Participant)
        // -------------------------------------------------------
        // In Multi-Teacher classrooms:
        // ONLY the designated Primary Teacher receives the Zoom host start_url.
        // Co-teachers, observers, students, and super admins receive join_url
        // to prevent host session conflicts and concurrent host takeover errors.
        $isPrimaryTeacher = $isTeacher && $this->sessionRepo->isPrimaryTeacherForSession($sessionId, $userId);

        $redirectUrl = $meeting['join_url'];
        if ($isPrimaryTeacher && !empty($meeting['start_url'])) {
            $redirectUrl = $meeting['start_url'];
        }

        header("Location: {$redirectUrl}");
        exit;
    }

    private function showError(string $message): void
    {
        $pageTitle = "Join Session Error";
        require __DIR__ . '/../../../views/session/join_error.php';
        exit;
    }
}
