<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Repositories\ClassSessionRepository;
use PDO;

/**
 * AttendanceService
 *
 * All business logic for the Attendance module.
 * Controllers must call this service — never the repository directly.
 */
class AttendanceService
{
    private AttendanceRepository  $attendanceRepo;
    private ClassSessionRepository $sessionRepo;
    private PDO                   $db;

    public function __construct(
        AttendanceRepository  $attendanceRepo,
        ClassSessionRepository $sessionRepo,
        PDO                   $db
    ) {
        $this->attendanceRepo = $attendanceRepo;
        $this->sessionRepo    = $sessionRepo;
        $this->db             = $db;
    }

    // -------------------------------------------------------
    // Record attendance
    // -------------------------------------------------------

    /**
     * Record attendance for any user (student or teacher) joining a session.
     * Safe to call multiple times — idempotent (only first join_time kept).
     * Returns true on success, false if attendance tracking is disabled.
     */
    public function recordAttendance(int $sessionId, int $userId, string $ipAddress, string $role = 'student'): bool
    {
        if (!$this->isAttendanceEnabled()) {
            return false;
        }
        return $this->attendanceRepo->record($sessionId, $userId, $ipAddress, $role);
    }

    // -------------------------------------------------------
    // Session report (Teacher / Admin)
    // -------------------------------------------------------

    /**
     * Full report for a single session: present list + absent list + stats.
     */
    public function getSessionReport(int $sessionId): array
    {
        $session = $this->sessionRepo->findById($sessionId);
        if (!$session) {
            return [];
        }

        $classroomId = (int) $session['classroom_id'];
        $present     = $this->attendanceRepo->getBySession($sessionId);
        $absent      = $this->attendanceRepo->getAbsenteesBySession($sessionId, $classroomId);
        $presentCount = count($present);
        $absentCount  = count($absent);
        $total        = $presentCount + $absentCount;
        $percentage   = $total > 0 ? round(($presentCount / $total) * 100, 1) : 0.0;

        return [
            'session'       => $session,
            'present'       => $present,
            'absent'        => $absent,
            'stats'         => [
                'present_count'  => $presentCount,
                'absent_count'   => $absentCount,
                'total_enrolled' => $total,
                'percentage'     => $percentage,
            ],
        ];
    }

    // -------------------------------------------------------
    // Student summary
    // -------------------------------------------------------

    /**
     * Overall attendance summary for a student in one classroom.
     */
    public function getStudentSummary(int $studentId, int $classroomId): array
    {
        return $this->attendanceRepo->getStudentSummary($studentId, $classroomId);
    }

    /**
     * Session-by-session attendance list for a student.
     */
    public function getStudentSessionList(
        int $studentId,
        int $classroomId,
        int $limit,
        int $offset
    ): array {
        return $this->attendanceRepo->getStudentSessionList($studentId, $classroomId, $limit, $offset);
    }

    public function countStudentSessions(int $studentId, int $classroomId): int
    {
        return $this->attendanceRepo->countStudentSessions($classroomId);
    }

    // -------------------------------------------------------
    // Teacher report
    // -------------------------------------------------------

    /**
     * Paginated session-wise summary for a classroom (for teacher view).
     */
    public function getTeacherClassroomReport(int $classroomId, int $limit, int $offset): array
    {
        return $this->attendanceRepo->getTeacherClassroomReport($classroomId, $limit, $offset);
    }

    public function countTeacherClassroomSessions(int $classroomId): int
    {
        return $this->attendanceRepo->countTeacherClassroomSessions($classroomId);
    }

    // -------------------------------------------------------
    // Admin overview
    // -------------------------------------------------------

    /**
     * Paginated admin overview with optional filters.
     */
    public function getAdminOverview(
        int    $limit,
        int    $offset,
        int    $classroomId = 0,
        string $dateFrom    = '',
        string $dateTo      = ''
    ): array {
        return $this->attendanceRepo->getAdminOverview($limit, $offset, $classroomId, $dateFrom, $dateTo);
    }

    public function countAdminOverview(
        int    $classroomId = 0,
        string $dateFrom    = '',
        string $dateTo      = ''
    ): int {
        return $this->attendanceRepo->countAdminOverview($classroomId, $dateFrom, $dateTo);
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    /**
     * Check if attendance tracking is enabled in meeting_settings.
     * Cached in static variable to avoid repeated DB hits within one request.
     */
    private function isAttendanceEnabled(): bool
    {
        static $enabled = null;
        if ($enabled !== null) {
            return $enabled;
        }

        $stmt = $this->db->prepare(
            "SELECT setting_val FROM meeting_settings WHERE setting_key = 'attendance_sync_enabled'"
        );
        $stmt->execute();
        $val     = $stmt->fetchColumn();
        $enabled = ($val === '1');
        return $enabled;
    }
}
