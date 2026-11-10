<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * ClassSessionRepository
 *
 * All DB operations for class_sessions table.
 * Optimised with named column selects (no SELECT *),
 * proper indexes used, N+1 avoided via JOINs.
 */
class ClassSessionRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // -------------------------------------------------------
    // READ
    // -------------------------------------------------------

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT cs.id, cs.classroom_id, cs.session_date, cs.start_time, cs.end_time,
                   cs.timezone, cs.topic, cs.agenda, cs.session_number, cs.provider, cs.provider_account_id,
                   cs.status, cs.job_id, cs.created_by, cs.cancelled_at, cs.cancel_reason,
                   cs.created_at,
                   sm.join_url, sm.start_url, sm.meet_link, sm.passcode,
                   sm.generation_status, sm.error_message, sm.provider_meeting_id,
                   c.class_name, c.class_title, c.class_code,
                   t.username AS teacher_name, t.email AS teacher_email,
                   s.username AS student_name, s.email AS student_email
            FROM class_sessions cs
            LEFT JOIN session_meetings sm  ON sm.session_id = cs.id
            LEFT JOIN classrooms c         ON c.id = cs.classroom_id
            LEFT JOIN users t              ON t.id = c.teacher_id
            LEFT JOIN users s              ON s.id = c.student_id
            WHERE cs.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        // Fallback for teacher_name from classroom_teachers if c.teacher_id was NULL
        if (empty($row['teacher_name']) && !empty($row['classroom_id'])) {
            $stmtT = $this->db->prepare("
                SELECT u.username, u.email 
                FROM classroom_teachers ct 
                JOIN users u ON u.id = ct.user_id 
                WHERE ct.classroom_id = :cid 
                ORDER BY ct.is_primary DESC 
                LIMIT 1
            ");
            $stmtT->execute(['cid' => $row['classroom_id']]);
            $t = $stmtT->fetch(PDO::FETCH_ASSOC);
            if ($t) {
                $row['teacher_name']  = $t['username'];
                $row['teacher_email'] = $t['email'];
            }
        }

        // Fallback for student_name from classroom_students if c.student_id was NULL
        if (empty($row['student_name']) && !empty($row['classroom_id'])) {
            $stmtS = $this->db->prepare("
                SELECT u.username, u.email 
                FROM classroom_students cs 
                JOIN users u ON u.id = cs.user_id 
                WHERE cs.classroom_id = :cid 
                LIMIT 1
            ");
            $stmtS->execute(['cid' => $row['classroom_id']]);
            $s = $stmtS->fetch(PDO::FETCH_ASSOC);
            if ($s) {
                $row['student_name']  = $s['username'];
                $row['student_email'] = $s['email'];
            }
        }

        return $row;
    }

    /**
     * Paginated session list for a classroom (admin view)
     */
    public function getPaginatedByClassroom(
        int    $classroomId,
        int    $limit,
        int    $offset,
        string $status = ''
    ): array {
        $where  = 'cs.classroom_id = :classroom_id';
        $params = ['classroom_id' => $classroomId];

        if (!empty($status)) {
            $where .= ' AND cs.status = :status';
            $params['status'] = $status;
        }

        $stmt = $this->db->prepare("
            SELECT cs.id, cs.session_date, cs.start_time, cs.end_time,
                   cs.timezone, cs.topic, cs.session_number, cs.provider, cs.status,
                   sm.join_url, sm.meet_link, sm.generation_status,
                   sm.error_message
            FROM class_sessions cs
            LEFT JOIN session_meetings sm ON sm.session_id = cs.id
            WHERE {$where}
            ORDER BY cs.session_date ASC, cs.start_time ASC
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

    public function countByClassroom(int $classroomId, string $status = ''): int
    {
        $where  = 'classroom_id = :classroom_id';
        $params = ['classroom_id' => $classroomId];

        if (!empty($status)) {
            $where .= ' AND status = :status';
            $params['status'] = $status;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(id) FROM class_sessions WHERE {$where}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Upcoming sessions for a teacher (across all their assigned classes, single or group)
     */
    public function getUpcomingForTeacher(int $teacherId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT cs.id, cs.session_date, cs.start_time, cs.end_time,
                   cs.timezone, cs.topic, cs.provider, cs.status,
                   sm.join_url, sm.start_url, sm.meet_link, sm.generation_status,
                   c.class_name, c.class_code
            FROM class_sessions cs
            JOIN classrooms c ON c.id = cs.classroom_id
            LEFT JOIN session_meetings sm ON sm.session_id = cs.id
            WHERE cs.session_date >= CURDATE()
              AND cs.status NOT IN ('cancelled', 'completed')
              AND (
                  c.teacher_id = :teacher_id
                  OR EXISTS (
                      SELECT 1 FROM classroom_teachers ct 
                      WHERE ct.classroom_id = c.id AND ct.user_id = :teacher_id2
                  )
              )
            ORDER BY cs.session_date ASC, cs.start_time ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':teacher_id',  $teacherId, PDO::PARAM_INT);
        $stmt->bindValue(':teacher_id2', $teacherId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',       $limit,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Upcoming sessions for a student (across all their enrolled classes, single or group)
     */
    public function getUpcomingForStudent(int $studentId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT cs.id, cs.session_date, cs.start_time, cs.end_time,
                   cs.timezone, cs.topic, cs.provider, cs.status,
                   sm.join_url, sm.meet_link, sm.generation_status,
                   c.class_name, c.class_code,
                   COALESCE(
                       (SELECT ut.username FROM classroom_teachers ct JOIN users ut ON ut.id = ct.user_id WHERE ct.classroom_id = c.id ORDER BY ct.is_primary DESC LIMIT 1),
                       t.username
                   ) AS teacher_name
            FROM class_sessions cs
            JOIN classrooms c ON c.id = cs.classroom_id
            LEFT JOIN session_meetings sm ON sm.session_id = cs.id
            LEFT JOIN users t ON t.id = c.teacher_id
            WHERE cs.session_date >= CURDATE()
              AND cs.status NOT IN ('cancelled', 'completed')
              AND sm.generation_status = 'success'
              AND (
                  c.student_id = :student_id
                  OR EXISTS (
                      SELECT 1 FROM classroom_students cstud 
                      WHERE cstud.classroom_id = c.id AND cstud.user_id = :student_id2
                  )
              )
            ORDER BY cs.session_date ASC, cs.start_time ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':student_id',  $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':student_id2', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',       $limit,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a student belongs to the classroom of a given session
     * Used in the secure join flow
     */
    public function studentBelongsToSession(int $sessionId, int $studentId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(cs.id)
            FROM class_sessions cs
            JOIN classrooms c ON c.id = cs.classroom_id
            WHERE cs.id = :session_id
              AND (
                  c.student_id = :student_id
                  OR EXISTS (
                      SELECT 1 FROM classroom_students cstud 
                      WHERE cstud.classroom_id = c.id AND cstud.user_id = :student_id2
                  )
              )
        ");
        $stmt->execute([
            'session_id'   => $sessionId,
            'student_id'   => $studentId,
            'student_id2'  => $studentId,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Check if a teacher owns/teaches the classroom of a given session
     */
    public function teacherBelongsToSession(int $sessionId, int $teacherId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(cs.id)
            FROM class_sessions cs
            JOIN classrooms c ON c.id = cs.classroom_id
            WHERE cs.id = :session_id
              AND (
                  c.teacher_id = :teacher_id
                  OR EXISTS (
                      SELECT 1 FROM classroom_teachers cteach 
                      WHERE cteach.classroom_id = c.id AND cteach.user_id = :teacher_id2
                  )
              )
        ");
        $stmt->execute([
            'session_id'   => $sessionId,
            'teacher_id'   => $teacherId,
            'teacher_id2'  => $teacherId,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Check if a teacher is the designated Primary Host Teacher for the session
     * (Primary teacher gets Zoom start_url; co-teachers get join_url)
     */
    public function isPrimaryTeacherForSession(int $sessionId, int $teacherId): bool
    {
        $stmt = $this->db->prepare("
            SELECT (
                EXISTS (
                    SELECT 1 FROM class_sessions cs
                    JOIN classroom_teachers ct ON ct.classroom_id = cs.classroom_id
                    WHERE cs.id = :session_id AND ct.user_id = :teacher_id AND ct.is_primary = 1
                )
                OR EXISTS (
                    SELECT 1 FROM class_sessions cs
                    JOIN classrooms c ON c.id = cs.classroom_id
                    WHERE cs.id = :session_id2 AND c.teacher_id = :teacher_id2
                )
            ) AS is_primary
        ");
        $stmt->execute([
            'session_id'   => $sessionId,
            'teacher_id'   => $teacherId,
            'session_id2'  => $sessionId,
            'teacher_id2'  => $teacherId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    // -------------------------------------------------------
    // SCHEDULE CONFLICT CHECKS
    // -------------------------------------------------------

    /**
     * Checks if any teacher of a given classroom already has
     * an overlapping session in any OTHER classroom on the same day.
     *
     * Returns an array with [conflict_class_name, teacher_name] or null.
     */
    public function findTeacherConflict(int $classroomId, string $date, string $startTime, string $endTime, ?int $excludeSessionId = null): ?array
    {
        $excludeClause = $excludeSessionId ? 'AND cs.id != :exclude_session_id' : '';

        $stmt = $this->db->prepare("
            SELECT cs.session_date, cs.start_time, cs.end_time,
                   c2.class_name AS conflict_class_name,
                   u.username    AS teacher_name
            FROM class_sessions cs
            JOIN classrooms c2 ON c2.id = cs.classroom_id
            JOIN users u ON (
                u.id IN (
                    SELECT user_id FROM classroom_teachers WHERE classroom_id = :classroom_id
                    UNION
                    SELECT teacher_id FROM classrooms WHERE id = :classroom_id2 AND teacher_id IS NOT NULL
                )
                AND (
                    u.id = c2.teacher_id
                    OR EXISTS (
                        SELECT 1 FROM classroom_teachers ct2
                        WHERE ct2.classroom_id = c2.id AND ct2.user_id = u.id
                    )
                )
            )
            WHERE cs.classroom_id != :classroom_id3
              AND cs.session_date = :date
              AND cs.status NOT IN ('cancelled', 'failed')
              AND cs.start_time  < :end_time
              AND cs.end_time    > :start_time
              {$excludeClause}
            LIMIT 1
        ");

        $params = [
            'classroom_id'  => $classroomId,
            'classroom_id2' => $classroomId,
            'classroom_id3' => $classroomId,
            'date'          => $date,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
        ];
        if ($excludeSessionId) {
            $params['exclude_session_id'] = $excludeSessionId;
        }

        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Checks if any student of a given classroom already has
     * an overlapping session in any OTHER classroom on the same day.
     *
     * Returns an array with [conflict_class_name, student_name] or null.
     */
    public function findStudentConflict(int $classroomId, string $date, string $startTime, string $endTime, ?int $excludeSessionId = null): ?array
    {
        $excludeClause = $excludeSessionId ? 'AND cs.id != :exclude_session_id' : '';

        $stmt = $this->db->prepare("
            SELECT cs.session_date, cs.start_time, cs.end_time,
                   c2.class_name AS conflict_class_name,
                   u.username    AS student_name
            FROM class_sessions cs
            JOIN classrooms c2 ON c2.id = cs.classroom_id
            JOIN users u ON (
                u.id IN (
                    SELECT user_id FROM classroom_students WHERE classroom_id = :classroom_id
                    UNION
                    SELECT student_id FROM classrooms WHERE id = :classroom_id2 AND student_id IS NOT NULL
                )
                AND (
                    u.id = c2.student_id
                    OR EXISTS (
                        SELECT 1 FROM classroom_students cs2
                        WHERE cs2.classroom_id = c2.id AND cs2.user_id = u.id
                    )
                )
            )
            WHERE cs.classroom_id != :classroom_id3
              AND cs.session_date = :date
              AND cs.status NOT IN ('cancelled', 'failed')
              AND cs.start_time  < :end_time
              AND cs.end_time    > :start_time
              {$excludeClause}
            LIMIT 1
        ");

        $params = [
            'classroom_id'  => $classroomId,
            'classroom_id2' => $classroomId,
            'classroom_id3' => $classroomId,
            'date'          => $date,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
        ];
        if ($excludeSessionId) {
            $params['exclude_session_id'] = $excludeSessionId;
        }

        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // -------------------------------------------------------
    // WRITE
    // -------------------------------------------------------

    /**
     * Insert a single session row, returns new ID
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO class_sessions
                (classroom_id, session_date, start_time, end_time, timezone,
                 topic, agenda, session_number, provider, provider_account_id, status, job_id, created_by)
            VALUES
                (:classroom_id, :session_date, :start_time, :end_time, :timezone,
                 :topic, :agenda, :session_number, :provider, :provider_account_id, 'scheduled', :job_id, :created_by)
        ");
        $stmt->execute([
            'classroom_id'        => $data['classroom_id'],
            'session_date'        => $data['session_date'],
            'start_time'          => $data['start_time'],
            'end_time'            => $data['end_time'],
            'timezone'            => $data['timezone'] ?? 'Asia/Dhaka',
            'topic'               => $data['topic'] ?? null,
            'agenda'              => $data['agenda'] ?? null,
            'session_number'      => $data['session_number'] ?? null,
            'provider'            => $data['provider'],
            'provider_account_id' => $data['provider_account_id'] ?? null,
            'job_id'              => $data['job_id'] ?? null,
            'created_by'          => $data['created_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Bulk insert sessions using a single prepared statement inside an atomic database transaction.
     * Prevents partial insertion and inconsistent state upon failures.
     * Returns array of newly created session IDs.
     */
    public function bulkCreate(array $sessions): array
    {
        if (empty($sessions)) {
            return [];
        }

        $ids = [];
        $isOwnTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $isOwnTx = true;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO class_sessions
                    (classroom_id, session_date, start_time, end_time, timezone,
                     topic, agenda, session_number, provider, provider_account_id, status, job_id, created_by)
                VALUES
                    (:classroom_id, :session_date, :start_time, :end_time, :timezone,
                     :topic, :agenda, :session_number, :provider, :provider_account_id, 'scheduled', :job_id, :created_by)
            ");

            foreach ($sessions as $s) {
                try {
                    $stmt->execute([
                        'classroom_id'        => $s['classroom_id'],
                        'session_date'        => $s['session_date'],
                        'start_time'          => $s['start_time'],
                        'end_time'            => $s['end_time'],
                        'timezone'            => $s['timezone'] ?? 'Asia/Dhaka',
                        'topic'               => $s['topic'] ?? null,
                        'agenda'              => $s['agenda'] ?? null,
                        'session_number'      => $s['session_number'] ?? null,
                        'provider'            => $s['provider'],
                        'provider_account_id' => $s['provider_account_id'] ?? null,
                        'job_id'              => $s['job_id'] ?? null,
                        'created_by'          => $s['created_by'],
                    ]);
                    
                    if ($stmt->rowCount() > 0) {
                        $ids[] = (int) $this->db->lastInsertId();
                    }
                } catch (\PDOException $e) {
                    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        continue;
                    }
                    throw $e;
                }
            }

            if ($isOwnTx) {
                $this->db->commit();
            }
            return $ids;
        } catch (\Throwable $e) {
            if ($isOwnTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE class_sessions SET status = :status WHERE id = :id"
        );
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function cancel(int $id, int $cancelledBy, string $reason = ''): bool
    {
        $stmt = $this->db->prepare("
            UPDATE class_sessions
            SET status       = 'cancelled',
                cancelled_by = :cancelled_by,
                cancelled_at = NOW(),
                cancel_reason= :reason
            WHERE id = :id
        ");
        return $stmt->execute([
            'cancelled_by' => $cancelledBy,
            'reason'       => $reason,
            'id'           => $id,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE class_sessions
            SET session_date  = :session_date,
                start_time    = :start_time,
                end_time      = :end_time,
                timezone      = :timezone,
                topic         = :topic,
                agenda        = :agenda
            WHERE id = :id
        ");
        return $stmt->execute([
            'session_date' => $data['session_date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'timezone'     => $data['timezone'] ?? 'Asia/Dhaka',
            'topic'        => $data['topic'] ?? null,
            'agenda'       => $data['agenda'] ?? null,
            'id'           => $id,
        ]);
    }

    public function updateProviderAccount(int $id, ?int $providerAccountId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE class_sessions SET provider_account_id = :provider_account_id WHERE id = :id"
        );
        return $stmt->execute([
            'provider_account_id' => $providerAccountId,
            'id'                  => $id
        ]);
    }
}
