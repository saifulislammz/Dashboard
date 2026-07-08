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
     * Teacher and student both recorded here.
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
     * Get all students who were ABSENT from a specific session.
     * Looks at classroom student + teacher and subtracts those who attended.
     */
    public function getAbsenteesBySession(int $sessionId, int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id AS user_id, u.username, u.email, 'student' AS role
            FROM classrooms c
            JOIN users u ON u.id = c.student_id
            WHERE c.id = :classroom_id_1
              AND u.id NOT IN (
                  SELECT sa.user_id FROM session_attendance sa WHERE sa.session_id = :session_id_1
              )
            UNION ALL
            SELECT u.id AS user_id, u.username, u.email, 'teacher' AS role
            FROM classrooms c
            JOIN users u ON u.id = c.teacher_id
            WHERE c.id = :classroom_id_2
              AND u.id NOT IN (
                  SELECT sa.user_id FROM session_attendance sa WHERE sa.session_id = :session_id_2
              )
        ");
        $stmt->execute([
            'classroom_id_1' => $classroomId,
            'session_id_1'   => $sessionId,
            'classroom_id_2' => $classroomId,
            'session_id_2'   => $sessionId,
        ]);
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

    // -------------------------------------------------------
    // READ — Admin Overview
    // -------------------------------------------------------

    /**
     * Paginated admin overview: all sessions with present/absent stats.
     * Filters: classroom_id (optional), date_from, date_to (optional).
     */
    public function getAdminOverview(
        int    $limit,
        int    $offset,
        int    $classroomId = 0,
        string $dateFrom    = '',
        string $dateTo      = ''
    ): array {
        $where  = "cs.status NOT IN ('cancelled')";
        $params = [];

        if ($classroomId > 0) {
            $where .= " AND cs.classroom_id = :classroom_id";
            $params['classroom_id'] = $classroomId;
        }
        if (!empty($dateFrom)) {
            $where .= " AND cs.session_date >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where .= " AND cs.session_date <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $stmt = $this->db->prepare("
            SELECT cs.id AS session_id, cs.session_date, cs.start_time, cs.end_time,
                   cs.topic, cs.session_number, cs.status,
                   c.id AS classroom_id, c.class_name, c.class_code,
                   t.username AS teacher_name,
                   s.username AS student_name,
                   COUNT(sa.id) AS present_count,
                   2 AS total_enrolled
            FROM class_sessions cs
            JOIN classrooms c        ON c.id = cs.classroom_id
            JOIN users t             ON t.id = c.teacher_id
            JOIN users s             ON s.id = c.student_id
            LEFT JOIN session_attendance sa ON sa.session_id = cs.id
            WHERE {$where}
            GROUP BY cs.id, cs.session_date, cs.start_time, cs.end_time,
                     cs.topic, cs.session_number, cs.status,
                     c.id, c.class_name, c.class_code,
                     t.username, s.username
            ORDER BY cs.session_date DESC, cs.start_time DESC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $k => $v) {
            $stmt->bindValue(":$k", $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAdminOverview(
        int    $classroomId = 0,
        string $dateFrom    = '',
        string $dateTo      = ''
    ): int {
        $where  = "status NOT IN ('cancelled')";
        $params = [];

        if ($classroomId > 0) {
            $where .= " AND classroom_id = :classroom_id";
            $params['classroom_id'] = $classroomId;
        }
        if (!empty($dateFrom)) {
            $where .= " AND session_date >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where .= " AND session_date <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(id) FROM class_sessions WHERE {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
