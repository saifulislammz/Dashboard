<?php

declare(strict_types=1);

namespace App\Controllers\Session;

use App\Repositories\ClassSessionRepository;
use App\Repositories\SessionMeetingRepository;
use PDO;

class SessionJoinController
{
    private PDO $db;
    private ClassSessionRepository $sessionRepo;
    private SessionMeetingRepository $meetingRepo;

    public function __construct(
        PDO $db,
        ClassSessionRepository $sessionRepo,
        SessionMeetingRepository $meetingRepo
    ) {
        $this->db          = $db;
        $this->sessionRepo = $sessionRepo;
        $this->meetingRepo = $meetingRepo;
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

        // Fetch global settings
        $stmt = $this->db->query("SELECT setting_key, setting_val FROM meeting_settings WHERE setting_key = 'join_open_minutes_before'");
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);
        $minutesBefore = (int) ($setting['setting_val'] ?? 10);

        // Time logic
        $tz = new \DateTimeZone($session['timezone']);
        $startDt = new \DateTime("{$session['session_date']} {$session['start_time']}", $tz);
        $endDt   = new \DateTime("{$session['session_date']} {$session['end_time']}", $tz);
        
        $now = new \DateTime('now', $tz);

        // Determine if meeting is open
        $openDt = clone $startDt;
        $openDt->modify("-{$minutesBefore} minutes");

        $isTeacher = $auth->hasRole(\ROLE_ADMIN) || ($session['teacher_id'] === $userId);
        
        if (!$isTeacher && $now < $openDt) {
            $this->showError("Meeting is not open yet. You can join {$minutesBefore} minutes before the start time.");
            return;
        }

        if (!$isTeacher && $now > $endDt) {
            $this->showError("This session has already ended.");
            return;
        }

        // --- Log attendance if enabled ---
        $this->logAttendance($sessionId, $userId, 'join');

        // Choose start_url (for teacher/admin in Zoom) or join_url
        $redirectUrl = $meeting['join_url'];
        
        if ($isTeacher && !empty($meeting['start_url'])) {
            $redirectUrl = $meeting['start_url'];
        }

        // Optional: show a transitional loading page, or just redirect
        header("Location: {$redirectUrl}");
        exit;
    }

    private function logAttendance(int $sessionId, int $userId, string $action): void
    {
        $stmt = $this->db->prepare("SELECT setting_val FROM meeting_settings WHERE setting_key = 'attendance_sync_enabled'");
        $stmt->execute();
        $enabled = $stmt->fetchColumn();

        if ($enabled === '1') {
            $logStmt = $this->db->prepare("
                INSERT INTO session_attendance (session_id, user_id, join_time) 
                VALUES (:sid, :uid, NOW())
                ON DUPLICATE KEY UPDATE join_time = IF(join_time IS NULL, NOW(), join_time)
            ");
            $logStmt->execute(['sid' => $sessionId, 'uid' => $userId]);
        }
    }

    private function showError(string $message): void
    {
        $pageTitle = "Join Session Error";
        require __DIR__ . '/../../../views/session/join_error.php';
        exit;
    }
}
