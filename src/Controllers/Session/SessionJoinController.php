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

        // Fetch join window setting
        $stmt          = $this->db->query("SELECT setting_val FROM meeting_settings WHERE setting_key = 'join_open_minutes_before'");
        $minutesBefore = (int) ($stmt->fetchColumn() ?: 10);

        // Time logic
        $tz      = new \DateTimeZone($session['timezone']);
        $startDt = new \DateTime("{$session['session_date']} {$session['start_time']}", $tz);
        $endDt   = new \DateTime("{$session['session_date']} {$session['end_time']}", $tz);
        $now     = new \DateTime('now', $tz);
        $openDt  = (clone $startDt)->modify("-{$minutesBefore} minutes");

        $isTeacher = $auth->hasRole(\ROLE_ADMIN) || ((int) $session['classroom_id'] && $this->sessionRepo->teacherBelongsToSession($sessionId, $userId));

        if (!$isTeacher && $now < $openDt) {
            $this->showError("Meeting is not open yet. You can join {$minutesBefore} minutes before the start time.");
            return;
        }

        if (!$isTeacher && $now > $endDt) {
            $this->showError("This session has already ended.");
            return;
        }

        // Record attendance for both student and teacher via AttendanceService
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $role      = $isTeacher ? 'teacher' : 'student';
        $this->attendanceService->recordAttendance($sessionId, $userId, $ipAddress, $role);

        // Choose start_url for teacher/admin in Zoom, otherwise join_url
        $redirectUrl = $meeting['join_url'];
        if ($isTeacher && !empty($meeting['start_url'])) {
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
