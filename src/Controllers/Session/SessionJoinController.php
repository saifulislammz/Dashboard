<?php

declare(strict_types=1);

namespace App\Controllers\Session;

use App\Repositories\ClassSessionRepository;
use App\Repositories\SessionMeetingRepository;
use App\Services\AttendanceService;
use PDO;

/**
 * SessionJoinController
 *
 * Handles the join flow: validates session, checks time window,
 * records attendance (for both students and teachers), then redirects.
 */
class SessionJoinController
{
    private PDO                    $db;
    private ClassSessionRepository $sessionRepo;
    private SessionMeetingRepository $meetingRepo;
    private AttendanceService      $attendanceService;

    public function __construct(
        PDO                     $db,
        ClassSessionRepository  $sessionRepo,
        SessionMeetingRepository $meetingRepo,
        AttendanceService       $attendanceService
    ) {
        $this->db                = $db;
        $this->sessionRepo       = $sessionRepo;
        $this->meetingRepo       = $meetingRepo;
        $this->attendanceService = $attendanceService;
    }

    public function handleJoin(int $sessionId): void
    {
        global $auth;

        $userId = $auth->getUserId();
        if (!$userId) {
            header('Location: /login.php');
            exit;
        }

        $session = $this->sessionRepo->findById($sessionId);
        if (!$session) {
            $this->showError("Session not found.");
            return;
        }

        if ($session['status'] === 'cancelled') {
            $this->showError("This session has been cancelled.");
            return;
        }

        $meeting = $this->meetingRepo->findBySessionId($sessionId);
        if (!$meeting || $meeting['generation_status'] !== 'success' || empty($meeting['join_url'])) {
            $this->showError("Meeting link is not ready yet. Please try again later.");
            return;
        }

        // Fetch join window settings
        $stmtSettings  = $this->db->query("
            SELECT setting_key, setting_val 
            FROM meeting_settings 
            WHERE setting_key IN ('join_open_minutes_before', 'teacher_join_open_minutes_before')
        ");
        $settings        = $stmtSettings->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
        $minutesBefore   = (int) ($settings['join_open_minutes_before'] ?? 10);
        // Teachers may have a separate (or same) window; fallback to student window
        $teacherMinutes  = isset($settings['teacher_join_open_minutes_before'])
            ? (int) $settings['teacher_join_open_minutes_before']
            : $minutesBefore;

        // Time logic (session timezone)
        $tz      = new \DateTimeZone($session['timezone']);
        $startDt = new \DateTime("{$session['session_date']} {$session['start_time']}", $tz);
        $endDt   = new \DateTime("{$session['session_date']} {$session['end_time']}", $tz);
        $now     = new \DateTime('now', $tz);

        // Determine role — ROLE_ADMIN bypasses time gates entirely
        $isAdmin   = $auth->hasRole(\ROLE_ADMIN);
        $isTeacher = !$isAdmin && ((int) $session['classroom_id'] && $this->sessionRepo->teacherBelongsToSession($sessionId, $userId));

        if (!$isAdmin) {
            // Determine the open window for this user's role
            $openMinutes = $isTeacher ? $teacherMinutes : $minutesBefore;
            $openDt      = (clone $startDt)->modify("-{$openMinutes} minutes");

            if ($now < $openDt) {
                $role_label = $isTeacher ? 'Teacher' : 'Student';
                $this->showError(
                    "Session is not open yet. {$role_label}s can join {$openMinutes} minute" . ($openMinutes === 1 ? '' : 's') . " before the start time."
                );
                return;
            }

            if ($now > $endDt) {
                $this->showError("This session has already ended.");
                return;
            }
        }

        // Record attendance for student / teacher via AttendanceService
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $role      = ($isTeacher || $isAdmin) ? 'teacher' : 'student';
        $this->attendanceService->recordAttendance($sessionId, $userId, $ipAddress, $role);

        // Teachers / admins get the host start_url (Zoom); students get join_url
        $redirectUrl = $meeting['join_url'];
        if (($isTeacher || $isAdmin) && !empty($meeting['start_url'])) {
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
