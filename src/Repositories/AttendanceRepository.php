<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * AttendanceRepository
 *
 * All DB operations for the session_attendance table.
 * No business logic — pure data access only.
 * Uses named column selects (no SELECT *), prepared statements, and composite indexes.
 */
class AttendanceRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // -------------------------------------------------------
    // WRITE
    // -------------------------------------------------------

    /**
     * Record attendance for a user joining a session.
     * Idempotent: ON DUPLICATE KEY UPDATE keeps first join_time (updates only ip_address).
     */
    public function record(int $sessionId, int $userId, string $ipAddress, string $role = 'student'): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO session_attendance (session_id, user_id, role, join_time, ip_address)
            VALUES (:session_id, :user_id, :role, NOW(), :ip_address)
            ON DUPLICATE KEY UPDATE
                ip_address = VALUES(ip_address)
        ");
        return $stmt->execute([
            'session_id' => $sessionId,
            'user_id'    => $userId,
            'role'       => $role,
            'ip_address' => $ipAddress,
        ]);
    }

    // -------------------------------------------------------
    // READ — Per Session
    // -------------------------------------------------------

    /**
     * Get all attendees for a specific session with user info.
     * Returns: id, session_id, user_id, join_time, ip_address, username, email
     */
    public function getBySession(int $sessionId): array
    {
        $stmt = $this->db->prepare("
            SELECT sa.id, sa.session_id, sa.user_id, sa.join_time, sa.ip_address,
                   u.username, u.email
            FROM session_attendance sa
            JOIN users u ON u.id = sa.user_id
            WHERE sa.session_id = :session_id
            ORDER BY sa.join_time ASC
        ");
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count attendees for a session.
     */
    public function countBySession(int $sessionId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(id) FROM session_attendance WHERE session_id = :session_id"
        );
        $stmt->execute(['session_id' => $sessionId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Check if a specific user has attendance recorded for a session.
     */
    public function hasRecord(int $sessionId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(id) FROM session_attendance
            WHERE session_id = :session_id AND user_id = :user_id
        ");
        $stmt->execute(['session_id' => $sessionId, 'user_id' => $userId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // -------------------------------------------------------
    // READ — Student Summary
    // -------------------------------------------------------

    /**
     * Get attendance summary for a student in a specific classroom.
     * Returns: total_sessions, attended, absent, percentage
     */
    public function getStudentSummary(int $studentId, int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(cs.id) AS total_sessions,
                COUNT(sa.user_id) AS attended
            FROM class_sessions cs
            LEFT JOIN session_attendance sa ON sa.session_id = cs.id AND sa.user_id = :student_id
            WHERE cs.classroom_id = :classroom_id
              AND cs.status NOT IN ('cancelled')
              AND cs.session_date <= CURDATE()
        ");
        $stmt->execute([
            'student_id'   => $studentId,
            'classroom_id' => $classroomId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $total    = (int) ($row['total_sessions'] ?? 0);
        $attended = (int) ($row['attended'] ?? 0);
        $absent   = max(0, $total - $attended);
        $pct      = $total > 0 ? round(($attended / $total) * 100, 1) : 0.0;

        return [
            'total_sessions' => $total,
            'attended'       => $attended,
            'absent'         => $absent,
            'percentage'     => $pct,
        ];
    }

    /**
     * Get session-by-session attendance record for a student in a classroom.
     */
    public function getStudentSessionList(int $studentId, int $classroomId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT cs.id AS session_id, cs.session_date, cs.start_time, cs.end_time,
                   cs.topic, cs.status,
                   IF(sa.user_id IS NOT NULL, 1, 0) AS is_present,
                   sa.join_time
            FROM class_sessions cs
            LEFT JOIN session_attendance sa ON sa.session_id = cs.id AND sa.user_id = :student_id
            WHERE cs.classroom_id = :classroom_id
              AND cs.status NOT IN ('cancelled')
              AND cs.session_date <= CURDATE()
            ORDER BY cs.session_date DESC, cs.start_time DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':student_id',   $studentId,   PDO::PARAM_INT);
        $stmt->bindValue(':classroom_id', $classroomId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',        $limit,       PDO::PARAM_INT);
        $stmt->bindValue(':offset',       $offset,      PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countStudentSessions(int $classroomId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(cs.id)
            FROM class_sessions cs
            WHERE cs.classroom_id = :classroom_id
              AND cs.status NOT IN ('cancelled')
              AND cs.session_date <= CURDATE()
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return (int) $stmt->fetchColumn();
    }

    // -------------------------------------------------------
    // READ — Teacher Report
    // -------------------------------------------------------

    /**
     * Get paginated session-wise attendance summary for a teacher's classroom.
     * Returns each session with present/absent counts.
     */
    public function getTeacherClassroomReport(int $classroomId, int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT cs.id AS session_id, cs.session_date, cs.start_time, cs.end_time,
                   cs.topic, cs.session_number, cs.status,
                   COUNT(sa.id) AS present_count
            FROM class_sessions cs
            LEFT JOIN session_attendance sa ON sa.session_id = cs.id
            WHERE cs.classroom_id = :classroom_id
              AND cs.status NOT IN ('cancelled')
            GROUP BY cs.id, cs.session_date, cs.start_time, cs.end_time,
                     cs.topic, cs.session_number, cs.status
            ORDER BY cs.session_date DESC, cs.start_time DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':classroom_id', $classroomId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',        $limit,       PDO::PARAM_INT);
        $stmt->bindValue(':offset',       $offset,      PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTeacherClassroomSessions(int $classroomId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(id) FROM class_sessions
            WHERE classroom_id = :classroom_id AND status NOT IN ('cancelled')
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Overall attendance summary for a teacher's classroom.
     *
     * Uses a plain LEFT JOIN + COUNT DISTINCT to avoid the subquery full-scan.
     * Returns both past-session stats (for the summary card) and total_all_sessions
     * (for pagination), eliminating the need for a separate countTeacherClassroomSessions() call.
     *
     * Returns:
     *   total_sessions            — past sessions only (for rate calculation)
     *   sessions_with_attendance  — past sessions where at least 1 attendee joined
     *   sessions_without_attendance
     *   percentage
     *   total_all_sessions        — all non-cancelled sessions (for pagination count)
     */
    public function getTeacherSummary(int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(DISTINCT cs.id)                                            AS total_all_sessions,
                SUM(CASE WHEN cs.session_date <= CURDATE() THEN 1 ELSE 0 END)   AS total_sessions,
                COUNT(DISTINCT CASE WHEN cs.session_date <= CURDATE()
                                     AND sa.id IS NOT NULL
                                    THEN cs.id END)                              AS sessions_with_attendance
            FROM class_sessions cs
            LEFT JOIN session_attendance sa ON sa.session_id = cs.id
            WHERE cs.classroom_id = :classroom_id
              AND cs.status NOT IN ('cancelled')
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $totalAll = (int) ($row['total_all_sessions']          ?? 0);
        $total    = (int) ($row['total_sessions']              ?? 0);
        $present  = (int) ($row['sessions_with_attendance']    ?? 0);
        $absent   = max(0, $total - $present);
        $pct      = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;

        return [
            'total_all_sessions'            => $totalAll,
            'total_sessions'                => $total,
            'sessions_with_attendance'      => $present,
            'sessions_without_attendance'   => $absent,
            'percentage'                    => $pct,
        ];
    }

    // -------------------------------------------------------
    // READ — Admin Overview (Classroom-level)
    // -------------------------------------------------------

    /**
     * Paginated admin overview: one row per classroom (teacher+student pair).
     * Returns classroom info + total non-cancelled sessions count.
     */
    public function getAdminClassroomList(int $limit, int $offset): array
    {
        $stmt = $this->db->prepare("
            SELECT
                c.id          AS classroom_id,
                c.class_name,
                c.class_code,
                t.id          AS teacher_id,
                t.username    AS teacher_name,
                s.id          AS student_id,
                s.username    AS student_name,
                COUNT(cs.id)  AS total_sessions
            FROM classrooms c
            JOIN users t ON t.id = c.teacher_id
            JOIN users s ON s.id = c.student_id
            LEFT JOIN class_sessions cs
                ON cs.classroom_id = c.id
               AND cs.status NOT IN ('cancelled')
            GROUP BY
                c.id, c.class_name, c.class_code,
                t.id, t.username,
                s.id, s.username
            ORDER BY c.id DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total classroom count for admin overview pagination.
     */
    public function countAdminClassrooms(): int
    {
        $stmt = $this->db->query("SELECT COUNT(id) FROM classrooms");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Session-by-session detail for a classroom.
     * Single query with two LEFT JOINs — student + teacher attendance,
     * no N+1, no SELECT *.
     *
     * Returns per session:
     *   session_id, session_date, start_time, end_time, topic, status,
     *   student_present (0|1), teacher_present (0|1),
     *   student_join_time, teacher_join_time
     */
    public function getAdminClassroomDetail(
        int $classroomId,
        int $studentId,
        int $teacherId,
        int $limit,
        int $offset
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                cs.id           AS session_id,
                cs.session_date,
                cs.start_time,
                cs.end_time,
                cs.topic,
                cs.status,
                IF(sa_s.user_id IS NOT NULL, 1, 0) AS student_present,
                IF(sa_t.user_id IS NOT NULL, 1, 0) AS teacher_present,
                sa_s.join_time  AS student_join_time,
                sa_t.join_time  AS teacher_join_time
            FROM class_sessions cs
            LEFT JOIN session_attendance sa_s
                ON sa_s.session_id = cs.id AND sa_s.user_id = :student_id
            LEFT JOIN session_attendance sa_t
                ON sa_t.session_id = cs.id AND sa_t.user_id = :teacher_id
            WHERE cs.classroom_id  = :classroom_id
              AND cs.status NOT IN ('cancelled')
              AND cs.session_date  <= CURDATE()
            ORDER BY cs.session_date DESC, cs.start_time DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':classroom_id', $classroomId, PDO::PARAM_INT);
        $stmt->bindValue(':student_id',   $studentId,   PDO::PARAM_INT);
        $stmt->bindValue(':teacher_id',   $teacherId,   PDO::PARAM_INT);
        $stmt->bindValue(':limit',        $limit,       PDO::PARAM_INT);
        $stmt->bindValue(':offset',       $offset,      PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total past sessions for a classroom (for detail pagination).
     */
    public function countAdminClassroomSessions(int $classroomId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(id)
            FROM class_sessions
            WHERE classroom_id = :classroom_id
              AND status NOT IN ('cancelled')
              AND session_date <= CURDATE()
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Aggregate summary for the detail stats boxes.
     * Returns:
     *   total_sessions      — past non-cancelled sessions
     *   student_present     — sessions where student attended
     *   teacher_present     — sessions where teacher attended
     *   (student/teacher absent can be derived as total - present)
     */
    public function getAdminClassroomSummary(
        int $classroomId,
        int $studentId,
        int $teacherId
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(cs.id)                                         AS total_sessions,
                SUM(IF(sa_s.user_id IS NOT NULL, 1, 0))              AS student_present,
                SUM(IF(sa_t.user_id IS NOT NULL, 1, 0))              AS teacher_present
            FROM class_sessions cs
            LEFT JOIN session_attendance sa_s
                ON sa_s.session_id = cs.id AND sa_s.user_id = :student_id
            LEFT JOIN session_attendance sa_t
                ON sa_t.session_id = cs.id AND sa_t.user_id = :teacher_id
            WHERE cs.classroom_id  = :classroom_id
              AND cs.status NOT IN ('cancelled')
              AND cs.session_date  <= CURDATE()
        ");
        $stmt->execute([
            'classroom_id' => $classroomId,
            'student_id'   => $studentId,
            'teacher_id'   => $teacherId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $total          = (int) ($row['total_sessions']  ?? 0);
        $studentPresent = (int) ($row['student_present'] ?? 0);
        $teacherPresent = (int) ($row['teacher_present'] ?? 0);

        return [
            'total_sessions'  => $total,
            'student_present' => $studentPresent,
            'student_absent'  => max(0, $total - $studentPresent),
            'teacher_present' => $teacherPresent,
            'teacher_absent'  => max(0, $total - $teacherPresent),
        ];
    }
}
