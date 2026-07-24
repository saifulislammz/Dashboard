<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AttendanceRepository;

/**
 * AttendanceService
 *
 * Business logic for the Attendance module.
 * Controllers must call this service — never the repository directly.
 */
class AttendanceService
{
    private AttendanceRepository $attendanceRepo;

    public function __construct(AttendanceRepository $attendanceRepo)
    {
        $this->attendanceRepo = $attendanceRepo;
    }

    // -------------------------------------------------------
    // Record attendance
    // -------------------------------------------------------

    /**
     * Record attendance for any user (student or teacher) joining a session.
     * Safe to call multiple times — idempotent (only first join_time kept).
     */
    public function recordAttendance(int $sessionId, int $userId, string $ipAddress, string $role = 'student'): bool
    {
        return $this->attendanceRepo->record($sessionId, $userId, $ipAddress, $role);
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

    /**
     * Overall summary for a teacher's classroom (for the summary card).
     */
    public function getTeacherSummary(int $classroomId): array
    {
        return $this->attendanceRepo->getTeacherSummary($classroomId);
    }

    // -------------------------------------------------------
    // Admin overview (classroom-level)
    // -------------------------------------------------------

    /**
     * Paginated classroom list for admin attendance overview.
     */
    public function getAdminClassroomList(int $limit, int $offset): array
    {
        return $this->attendanceRepo->getAdminClassroomList($limit, $offset);
    }

    public function countAdminClassrooms(): int
    {
        return $this->attendanceRepo->countAdminClassrooms();
    }

    /**
     * Session-by-session detail for one classroom (student + teacher columns).
     */
    public function getAdminClassroomDetail(
        int $classroomId,
        int $studentId,
        int $teacherId,
        int $limit,
        int $offset
    ): array {
        return $this->attendanceRepo->getAdminClassroomDetail(
            $classroomId,
            $studentId,
            $teacherId,
            $limit,
            $offset
        );
    }

    public function countAdminClassroomSessions(int $classroomId): int
    {
        return $this->attendanceRepo->countAdminClassroomSessions($classroomId);
    }

    /**
     * Aggregate stats for the detail page summary boxes.
     */
    public function getAdminClassroomSummary(
        int $classroomId,
        int $studentId,
        int $teacherId
    ): array {
        return $this->attendanceRepo->getAdminClassroomSummary(
            $classroomId,
            $studentId,
            $teacherId
        );
    }
}
