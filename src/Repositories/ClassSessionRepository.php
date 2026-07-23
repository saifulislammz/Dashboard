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
        return $row ?: null;
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
     * Upcoming sessions for a teacher (across all their classes)
     */
    public function getUpcomingForTeacher(int $teacherId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT cs.id, cs.session_date, cs.start_time, cs.end_time,
                   cs.timezone, cs.topic, cs.provider, cs.status,
                   sm.join_url, sm.start_url, sm.meet_link, sm.generation_status,
                   c.class_name, c.class_code
            FROM class_sessions cs
            JOIN classrooms c       ON c.id = cs.classroom_id AND c.teacher_id = :teacher_id
            LEFT JOIN session_meetings sm ON sm.session_id = cs.id
            WHERE cs.session_date >= CURDATE()
              AND cs.status NOT IN ('cancelled', 'completed')
            ORDER BY cs.session_date ASC, cs.start_time ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':teacher_id', $teacherId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',      $limit,     PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Upcoming sessions for a student (across their enrolled class)
     */
    public function getUpcomingForStudent(int $studentId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT cs.id, cs.session_date, cs.start_time, cs.end_time,
                   cs.timezone, cs.topic, cs.provider, cs.status,
                   sm.join_url, sm.meet_link, sm.generation_status,
                   c.class_name, c.class_code,
                   t.username AS teacher_name
            FROM class_sessions cs
            JOIN classrooms c       ON c.id = cs.classroom_id AND c.student_id = :student_id
            LEFT JOIN session_meetings sm ON sm.session_id = cs.id
            LEFT JOIN users t        ON t.id = c.teacher_id
            WHERE cs.session_date >= CURDATE()
              AND cs.status NOT IN ('cancelled', 'completed')
              AND sm.generation_status = 'success'
            ORDER BY cs.session_date ASC, cs.start_time ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',      $limit,     PDO::PARAM_INT);
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
            JOIN classrooms c ON c.id = cs.classroom_id AND c.student_id = :student_id
            WHERE cs.id = :session_id
        ");
        $stmt->execute(['session_id' => $sessionId, 'student_id' => $studentId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Check if a teacher owns the classroom of a given session
     */
    public function teacherBelongsToSession(int $sessionId, int $teacherId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(cs.id)
            FROM class_sessions cs
            JOIN classrooms c ON c.id = cs.classroom_id AND c.teacher_id = :teacher_id
            WHERE cs.id = :session_id
        ");
        $stmt->execute(['session_id' => $sessionId, 'teacher_id' => $teacherId]);
        return (int) $stmt->fetchColumn() > 0;
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
            'classroom_id'   => $data['classroom_id'],
            'session_date'   => $data['session_date'],
            'start_time'     => $data['start_time'],
            'end_time'       => $data['end_time'],
            'timezone'       => $data['timezone'] ?? 'Asia/Dhaka',
            'topic'          => $data['topic'] ?? null,
            'agenda'         => $data['agenda'] ?? null,
            'session_number' => $data['session_number'] ?? null,
            'provider'       => $data['provider'],
            'provider_account_id' => $data['provider_account_id'] ?? null,
            'job_id'         => $data['job_id'] ?? null,
            'created_by'     => $data['created_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Bulk insert sessions using a single prepared statement.
     * Returns array of newly created session IDs.
     */
    public function bulkCreate(array $sessions): array
    {
        $ids  = [];
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
                    'classroom_id'   => $s['classroom_id'],
                    'session_date'   => $s['session_date'],
                    'start_time'     => $s['start_time'],
                    'end_time'       => $s['end_time'],
                    'timezone'       => $s['timezone'] ?? 'Asia/Dhaka',
                    'topic'          => $s['topic'] ?? null,
                    'agenda'         => $s['agenda'] ?? null,
                    'session_number' => $s['session_number'] ?? null,
                    'provider'       => $s['provider'],
                    'provider_account_id' => $s['provider_account_id'] ?? null,
                    'job_id'         => $s['job_id'] ?? null,
                    'created_by'     => $s['created_by'],
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $ids[] = (int) $this->db->lastInsertId();
                }
            } catch (\PDOException $e) {
                // If it's a duplicate entry error, just skip it.
                if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    continue;
                }
                throw $e;
            }
        }
        return $ids;
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
