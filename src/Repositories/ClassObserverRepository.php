<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * ClassObserverRepository
 *
 * Handles database access for classroom and session observer assignments.
 * Optimized with prepared statements, explicit column projections, and index-backed queries.
 */
class ClassObserverRepository
{
    private PDO $db;

    /**
     * Request-scoped cache for assigned classroom IDs per user to prevent redundant queries.
     *
     * @var array<int, array<int>>
     */
    private static array $assignedClassroomIdsCache = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Assign observers to a classroom (replaces existing assignments atomically).
     *
     * @param int $classroomId
     * @param array<int> $observerUserIds
     * @param int $assignedBy
     * @return bool
     */
    public function assignClassroomObservers(int $classroomId, array $observerUserIds, int $assignedBy): bool
    {
        $this->db->beginTransaction();

        try {
            // Delete existing observers for this classroom
            $deleteStmt = $this->db->prepare("DELETE FROM classroom_observers WHERE classroom_id = :classroom_id");
            $deleteStmt->execute(['classroom_id' => $classroomId]);

            // Insert unique observer IDs
            $uniqueUserIds = array_unique(array_filter(array_map('intval', $observerUserIds)));

            if (!empty($uniqueUserIds)) {
                $insertStmt = $this->db->prepare("
                    INSERT INTO classroom_observers (classroom_id, user_id, assigned_by, created_at)
                    VALUES (:classroom_id, :user_id, :assigned_by, NOW())
                ");

                foreach ($uniqueUserIds as $userId) {
                    if ($userId > 0) {
                        $insertStmt->execute([
                            'classroom_id' => $classroomId,
                            'user_id'      => $userId,
                            'assigned_by'  => $assignedBy,
                        ]);
                    }
                }
            }

            $this->db->commit();
            self::$assignedClassroomIdsCache = []; // Invalidate runtime cache
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('[ClassObserverRepository::assignClassroomObservers] Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get list of observer user IDs assigned to a specific classroom.
     *
     * @param int $classroomId
     * @return array<int>
     */
    public function getClassroomObserverIds(int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT user_id 
            FROM classroom_observers 
            WHERE classroom_id = :classroom_id
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * Get detailed observer records for a classroom.
     *
     * @param int $classroomId
     * @return array<array<string, mixed>>
     */
    public function getClassroomObservers(int $classroomId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                co.id,
                co.classroom_id,
                co.user_id,
                co.assigned_by,
                co.created_at,
                u.username,
                u.email,
                u.profile_picture,
                r.name AS role_name,
                r.slug AS role_slug,
                assigner.username AS assigned_by_name
            FROM classroom_observers co
            JOIN users u ON u.id = co.user_id
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN roles r ON r.id = ur.role_id AND r.deleted_at IS NULL
            LEFT JOIN users assigner ON assigner.id = co.assigned_by
            WHERE co.classroom_id = :classroom_id
            ORDER BY u.username ASC
        ");
        $stmt->execute(['classroom_id' => $classroomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Assign observers to a specific class session.
     *
     * @param int $sessionId
     * @param array<int> $observerUserIds
     * @param int $assignedBy
     * @return bool
     */
    public function assignSessionObservers(int $sessionId, array $observerUserIds, int $assignedBy): bool
    {
        $this->db->beginTransaction();

        try {
            $deleteStmt = $this->db->prepare("DELETE FROM session_observers WHERE session_id = :session_id");
            $deleteStmt->execute(['session_id' => $sessionId]);

            $uniqueUserIds = array_unique(array_filter(array_map('intval', $observerUserIds)));

            if (!empty($uniqueUserIds)) {
                $insertStmt = $this->db->prepare("
                    INSERT INTO session_observers (session_id, user_id, assigned_by, created_at)
                    VALUES (:session_id, :user_id, :assigned_by, NOW())
                ");

                foreach ($uniqueUserIds as $userId) {
                    if ($userId > 0) {
                        $insertStmt->execute([
                            'session_id'  => $sessionId,
                            'user_id'     => $userId,
                            'assigned_by' => $assignedBy,
                        ]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('[ClassObserverRepository::assignSessionObservers] Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get list of observer user IDs assigned to a specific session.
     *
     * @param int $sessionId
     * @return array<int>
     */
    public function getSessionObserverIds(int $sessionId): array
    {
        $stmt = $this->db->prepare("
            SELECT user_id 
            FROM session_observers 
            WHERE session_id = :session_id
        ");
        $stmt->execute(['session_id' => $sessionId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * Check if a user is an observer for a specific classroom.
     *
     * @param int $classroomId
     * @param int $userId
     * @return bool
     */
    public function isUserObserverForClassroom(int $classroomId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 
            FROM classroom_observers 
            WHERE classroom_id = :classroom_id AND user_id = :user_id 
            LIMIT 1
        ");
        $stmt->execute([
            'classroom_id' => $classroomId,
            'user_id'      => $userId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Check if a user is an authorized observer for a live session.
     * Returns true if assigned at the session level OR at the parent classroom level.
     *
     * @param int $sessionId
     * @param int $userId
     * @return bool
     */
    public function isUserObserverForSession(int $sessionId, int $userId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM session_observers so
            WHERE so.session_id = :session_id AND so.user_id = :user_id
            UNION
            SELECT 1
            FROM classroom_observers co
            JOIN class_sessions cs ON cs.classroom_id = co.classroom_id
            WHERE cs.id = :session_id_2 AND co.user_id = :user_id_2
            LIMIT 1
        ");
        $stmt->execute([
            'session_id'   => $sessionId,
            'user_id'      => $userId,
            'session_id_2' => $sessionId,
            'user_id_2'    => $userId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get all classroom IDs assigned to a specific observer user.
     *
     * @param int $userId
     * @return array<int>
     */
    public function getAssignedClassroomIds(int $userId): array
    {
        if (isset(self::$assignedClassroomIdsCache[$userId])) {
            return self::$assignedClassroomIdsCache[$userId];
        }

        $stmt = $this->db->prepare("
            SELECT DISTINCT classroom_id 
            FROM classroom_observers 
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);

        self::$assignedClassroomIdsCache[$userId] = $ids;
        return $ids;
    }

    /**
     * Get all eligible staff accounts (Admins, Moderators, Staff) who can be assigned as observers.
     *
     * @return array<array<string, mixed>>
     */
    public function getEligibleObservers(): array
    {
        $stmt = $this->db->query("
            SELECT 
                u.id, 
                u.username, 
                u.email, 
                u.profile_picture,
                r.name AS role_name,
                r.slug AS role_slug
            FROM users u
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN roles r ON r.id = ur.role_id AND r.deleted_at IS NULL
            WHERE (u.roles_mask & 1 = 1 OR u.roles_mask & 262144 = 262144 OR ur.role_id IS NOT NULL)
              AND u.status = 0
            ORDER BY u.username ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get upcoming sessions for classes assigned to an observer user.
     *
     * @param int $userId
     * @param int $limit
     * @return array<array<string, mixed>>
     */
    public function getUpcomingObservedSessions(int $userId, int $limit = 6): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                cs.id AS session_id,
                cs.classroom_id,
                cs.session_date,
                cs.start_time,
                cs.end_time,
                cs.timezone,
                cs.topic,
                cs.agenda,
                cs.session_number,
                cs.provider,
                cs.status,
                c.class_name,
                c.class_title,
                c.class_code,
                t.username AS teacher_name,
                s.username AS student_name,
                sm.join_url,
                sm.generation_status,
                sm.meet_link
            FROM class_sessions cs
            JOIN classrooms c ON c.id = cs.classroom_id
            JOIN users t ON t.id = c.teacher_id
            JOIN users s ON s.id = c.student_id
            LEFT JOIN session_meetings sm ON sm.session_id = cs.id
            WHERE cs.status NOT IN ('cancelled', 'completed', 'failed')
              AND cs.session_date >= CURDATE()
              AND (
                  c.id IN (SELECT classroom_id FROM classroom_observers WHERE user_id = :user_id)
                  OR cs.id IN (SELECT session_id FROM session_observers WHERE user_id = :user_id_2)
              )
            ORDER BY cs.session_date ASC, cs.start_time ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id',   $userId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id_2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',     $limit,  PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get recent past completed/held sessions for classes assigned to an observer user.
     *
     * @param int $userId
     * @param int $limit
     * @return array<array<string, mixed>>
     */
    public function getPastObservedSessions(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                cs.id AS session_id,
                cs.classroom_id,
                cs.session_date,
                cs.start_time,
                cs.end_time,
                cs.timezone,
                cs.topic,
                cs.session_number,
                cs.provider,
                cs.status,
                c.class_name,
                c.class_code,
                t.username AS teacher_name,
                s.username AS student_name
            FROM class_sessions cs
            JOIN classrooms c ON c.id = cs.classroom_id
            JOIN users t ON t.id = c.teacher_id
            JOIN users s ON s.id = c.student_id
            WHERE (cs.session_date < CURDATE() OR cs.status = 'completed')
              AND cs.status != 'cancelled'
              AND (
                  c.id IN (SELECT classroom_id FROM classroom_observers WHERE user_id = :user_id)
                  OR cs.id IN (SELECT session_id FROM session_observers WHERE user_id = :user_id_2)
              )
            ORDER BY cs.session_date DESC, cs.start_time DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id',   $userId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id_2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',     $limit,  PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

