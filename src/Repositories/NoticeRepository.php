<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class NoticeRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getPaginatedNotices(int $limit, int $offset, string $search = '', string $sortField = 'created_at', string $sortOrder = 'DESC'): array
    {
        $whereClause = "1=1";
        $params = [];

        if (!empty($search)) {
            $whereClause .= " AND (n.title LIKE :search1 OR n.content LIKE :search2)";
            $params['search1'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
        }

        // Whitelist allowed sort fields to prevent SQL injection
        $allowedSortFields = ['title', 'target_audience', 'target_type', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        $orderBy = "n.{$sortField} {$sortOrder}";

        $stmt = $this->db->prepare("
            SELECT n.id, n.title, n.content, n.target_audience, n.target_type, n.status, n.created_at, n.updated_at, u.username as creator_name 
            FROM notices n 
            LEFT JOIN users u ON n.created_by = u.id 
            WHERE $whereClause 
            ORDER BY $orderBy 
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }

        $stmt->execute();
        $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($notices)) {
            $noticeIds = array_column($notices, 'id');
            $targetsMap = $this->getTargetsForNoticeIds($noticeIds);
            foreach ($notices as &$notice) {
                $notice['targets'] = $targetsMap[$notice['id']] ?? [];
            }
        }

        return $notices;
    }

    public function countTotalNotices(string $search = ''): int
    {
        $whereClause = "1=1";
        $params = [];

        if (!empty($search)) {
            $whereClause .= " AND (n.title LIKE :search1 OR n.content LIKE :search2)";
            $params['search1'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare("SELECT COUNT(id) FROM notices n WHERE $whereClause");
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, title, content, target_audience, target_type, status, created_by, created_at, updated_at FROM notices WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int|false
    {
        $targetType = ($data['target_type'] ?? '') === 'all' ? 'all' : 'targeted';
        $stmt = $this->db->prepare("INSERT INTO notices (title, content, target_audience, target_type, status, created_by) VALUES (:title, :content, :target_audience, :target_type, :status, :created_by)");
        if ($stmt->execute([
            'title'           => $data['title'],
            'content'         => $data['content'],
            'target_audience' => $data['target_audience'] ?? 'both',
            'target_type'     => $targetType,
            'status'          => $data['status'] ?? 'active',
            'created_by'      => $data['created_by']
        ])) {
            return (int) $this->db->lastInsertId();
        }
        return false;
    }

    public function update(int $id, array $data): bool
    {
        $targetType = ($data['target_type'] ?? '') === 'all' ? 'all' : 'targeted';
        $stmt = $this->db->prepare("UPDATE notices SET title = :title, content = :content, target_audience = :target_audience, target_type = :target_type, status = :status WHERE id = :id");
        return $stmt->execute([
            'title'           => $data['title'],
            'content'         => $data['content'],
            'target_audience' => $data['target_audience'] ?? 'both',
            'target_type'     => $targetType,
            'status'          => $data['status'] ?? 'active',
            'id'              => $id
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE notices SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM notices WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // ─────────────────────────────────────────────────────────────
    // TARGET MANAGEMENT & RELATIONAL MAPPING
    // ─────────────────────────────────────────────────────────────

    /**
     * Atomically sync targets for a notice.
     * $targets is an array of ['type' => 'role'|'classroom'|'user', 'id' => int]
     */
    public function syncTargets(int $noticeId, array $targets): bool
    {
        $delStmt = $this->db->prepare("DELETE FROM notice_targets WHERE notice_id = :id");
        $delStmt->execute(['id' => $noticeId]);

        if (empty($targets)) {
            return true;
        }

        $values = [];
        $params = [];
        $idx = 0;

        foreach ($targets as $target) {
            $type = $target['type'] ?? '';
            $targetId = (int) ($target['id'] ?? 0);

            if ($targetId <= 0 || !in_array($type, ['role', 'classroom', 'user'], true)) {
                continue;
            }

            $values[] = "(:nid{$idx}, :type{$idx}, :tid{$idx})";
            $params["nid{$idx}"]  = $noticeId;
            $params["type{$idx}"] = $type;
            $params["tid{$idx}"]  = $targetId;
            $idx++;
        }

        if (empty($values)) {
            return true;
        }

        $sql = "INSERT IGNORE INTO notice_targets (notice_id, target_type, target_id) VALUES " . implode(', ', $values);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Get all target mappings for a single notice.
     */
    public function getTargetsByNoticeId(int $noticeId): array
    {
        $stmt = $this->db->prepare("
            SELECT nt.id, nt.notice_id, nt.target_type, nt.target_id,
                   c.class_name, c.class_code,
                   u.username, u.email
            FROM notice_targets nt
            LEFT JOIN classrooms c ON nt.target_type = 'classroom' AND nt.target_id = c.id
            LEFT JOIN users u ON nt.target_type = 'user' AND nt.target_id = u.id
            WHERE nt.notice_id = :id
            ORDER BY nt.target_type ASC, nt.target_id ASC
        ");
        $stmt->execute(['id' => $noticeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Eager load targets for multiple notices in a single batch query (No N+1).
     */
    public function getTargetsForNoticeIds(array $noticeIds): array
    {
        if (empty($noticeIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($noticeIds), '?'));
        $stmt = $this->db->prepare("
            SELECT nt.notice_id, nt.target_type, nt.target_id,
                   c.class_name, c.class_code,
                   u.username, u.email
            FROM notice_targets nt
            LEFT JOIN classrooms c ON nt.target_type = 'classroom' AND nt.target_id = c.id
            LEFT JOIN users u ON nt.target_type = 'user' AND nt.target_id = u.id
            WHERE nt.notice_id IN ($placeholders)
            ORDER BY nt.target_type ASC, nt.target_id ASC
        ");
        $stmt->execute(array_values($noticeIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['notice_id']][] = $row;
        }

        return $grouped;
    }

    /**
     * Duplicate targets from one notice to another.
     */
    public function duplicateTargets(int $sourceNoticeId, int $newNoticeId): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO notice_targets (notice_id, target_type, target_id)
            SELECT :new_id, target_type, target_id
            FROM notice_targets
            WHERE notice_id = :source_id
        ");
        return $stmt->execute([
            'new_id'    => $newNoticeId,
            'source_id' => $sourceNoticeId
        ]);
    }

    /**
     * Get active enrolled/assigned classroom IDs for a user.
     */
    public function getUserClassroomIds(int $userId, string $role): array
    {
        if ($userId <= 0) {
            return [];
        }

        if ($role === 'student') {
            $stmt = $this->db->prepare("
                SELECT DISTINCT c.id 
                FROM classrooms c 
                WHERE (c.student_id = :uid OR EXISTS (SELECT 1 FROM classroom_students cs WHERE cs.classroom_id = c.id AND cs.user_id = :uid2 AND cs.status = 'active'))
                  AND c.status = 'Active'
            ");
            $stmt->execute(['uid' => $userId, 'uid2' => $userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        if ($role === 'teacher') {
            $stmt = $this->db->prepare("
                SELECT DISTINCT c.id 
                FROM classrooms c 
                WHERE (c.teacher_id = :uid OR EXISTS (SELECT 1 FROM classroom_teachers ct WHERE ct.classroom_id = c.id AND ct.user_id = :uid2))
                  AND c.status = 'Active'
            ");
            $stmt->execute(['uid' => $userId, 'uid2' => $userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        return [];
    }

    /**
     * Master Targeted Notice Fetcher for User Dashboards.
     * Evaluates public broadcast, legacy role audience, and indexed notice_targets.
     * Eagerly loads attachments in a single batch query to prevent N+1 queries.
     */
    public function getNoticesForUser(int $userId, string $role, array $classroomIds = [], int $limit = 10): array
    {
        $roleMask = match ($role) {
            'student' => 4, // \App\Config\Roles::STUDENT
            'teacher' => 2, // \App\Config\Roles::TEACHER
            'admin'   => 1, // \App\Config\Roles::ADMIN
            default   => 0
        };

        $classroomCondition = "";
        $classroomParams = [];
        if (!empty($classroomIds)) {
            $placeholders = [];
            foreach ($classroomIds as $idx => $cId) {
                $ph = ":cid_{$idx}";
                $placeholders[] = $ph;
                $classroomParams[$ph] = (int) $cId;
            }
            $classroomCondition = " OR (nt.target_type = 'classroom' AND nt.target_id IN (" . implode(',', $placeholders) . "))";
        }

        $sql = "
            SELECT n.id, n.title, n.content, n.target_audience, n.target_type, n.created_at
            FROM notices n
            WHERE n.status = 'active'
              AND (
                  -- 1. Broadcast to All Users
                  n.target_type = 'all'

                  -- 2. Legacy notices without notice_targets rows (Backward Compatibility)
                  OR (
                      n.target_type = 'targeted'
                      AND NOT EXISTS (SELECT 1 FROM notice_targets nt_chk WHERE nt_chk.notice_id = n.id)
                      AND n.target_audience IN (:role, 'both')
                  )

                  -- 3. Granular Targeting via notice_targets
                  OR EXISTS (
                      SELECT 1 FROM notice_targets nt
                      WHERE nt.notice_id = n.id
                        AND (
                            (nt.target_type = 'user' AND nt.target_id = :user_id)
                            OR (nt.target_type = 'role' AND nt.target_id = :role_mask)
                            {$classroomCondition}
                        )
                  )
              )
            ORDER BY n.created_at DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':role',      $role,      PDO::PARAM_STR);
        $stmt->bindValue(':user_id',   $userId,    PDO::PARAM_INT);
        $stmt->bindValue(':role_mask', $roleMask,  PDO::PARAM_INT);
        $stmt->bindValue(':limit',     $limit,     PDO::PARAM_INT);

        foreach ($classroomParams as $ph => $val) {
            $stmt->bindValue($ph, $val, PDO::PARAM_INT);
        }

        $stmt->execute();
        $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Eager load all attachments in a single query
        if (!empty($notices)) {
            $noticeIds = array_column($notices, 'id');
            $placeholders = implode(',', array_fill(0, count($noticeIds), '?'));
            $attStmt = $this->db->prepare(
                "SELECT id, notice_id, file_name, file_path, file_type, file_size, created_at 
                 FROM notice_attachments 
                 WHERE notice_id IN ($placeholders) 
                 ORDER BY created_at ASC"
            );
            $attStmt->execute($noticeIds);
            $allAttachments = $attStmt->fetchAll(PDO::FETCH_ASSOC);

            $grouped = [];
            foreach ($allAttachments as $att) {
                $grouped[$att['notice_id']][] = $att;
            }

            foreach ($notices as &$notice) {
                $notice['attachments'] = $grouped[$notice['id']] ?? [];
            }
        }

        return $notices;
    }

    /**
     * Check if a user is authorized to view/download a notice (IDOR Guard).
     */
    public function canUserAccessNotice(int $userId, int $noticeId, string $role, array $classroomIds = []): bool
    {
        $notice = $this->findById($noticeId);
        if (!$notice || $notice['status'] !== 'active') {
            return false;
        }

        // Public notice is accessible by all authenticated users
        if ($notice['target_type'] === 'all') {
            return true;
        }

        $roleMask = match ($role) {
            'student' => 4,
            'teacher' => 2,
            'admin'   => 1,
            default   => 0
        };

        // Check if targets exist in notice_targets
        $targets = $this->getTargetsByNoticeId($noticeId);

        // Legacy notice fallback (no rows in notice_targets)
        if (empty($targets)) {
            if ($notice['target_audience'] === 'both') {
                return true;
            }
            return $notice['target_audience'] === $role;
        }

        // Check granular targets
        foreach ($targets as $target) {
            $type = $target['target_type'];
            $tid  = (int) $target['target_id'];

            if ($type === 'user' && $tid === $userId) {
                return true;
            }
            if ($type === 'role' && $tid === $roleMask) {
                return true;
            }
            if ($type === 'classroom' && in_array($tid, $classroomIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetch active notices for a given audience (student | teacher | both).
     * Kept 100% untouched for backward compatibility with legacy procedural code.
     */
    public function getActiveNoticesByAudience(string $audience, int $limit = 10): array
    {
        $allowedAudiences = ['student', 'teacher', 'both'];
        if (!in_array($audience, $allowedAudiences, true)) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT id, title, content, target_type, target_audience, created_at
             FROM notices
             WHERE status = 'active'
               AND (target_type = 'all' OR target_audience IN (:audience, 'both'))
             ORDER BY created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':audience', $audience);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch attachments for these notices
        if (!empty($notices)) {
            $noticeIds = array_column($notices, 'id');
            $placeholders = implode(',', array_fill(0, count($noticeIds), '?'));
            $attStmt = $this->db->prepare(
                "SELECT id, notice_id, file_name, file_path, file_type, file_size, created_at 
                 FROM notice_attachments 
                 WHERE notice_id IN ($placeholders) 
                 ORDER BY created_at ASC"
            );
            $attStmt->execute($noticeIds);
            $allAttachments = $attStmt->fetchAll(PDO::FETCH_ASSOC);

            $grouped = [];
            foreach ($allAttachments as $att) {
                $grouped[$att['notice_id']][] = $att;
            }

            foreach ($notices as &$notice) {
                $notice['attachments'] = $grouped[$notice['id']] ?? [];
            }
        }

        return $notices;
    }

    public function createAttachment(int $noticeId, array $fileData): bool
    {
        $stmt = $this->db->prepare("INSERT INTO notice_attachments (notice_id, file_name, file_path, file_type, file_size) VALUES (:notice_id, :file_name, :file_path, :file_type, :file_size)");
        return $stmt->execute([
            'notice_id' => $noticeId,
            'file_name' => $fileData['file_name'],
            'file_path' => $fileData['file_path'],
            'file_type' => $fileData['file_type'],
            'file_size' => $fileData['file_size']
        ]);
    }

    public function getAttachmentsByNoticeId(int $noticeId): array
    {
        $stmt = $this->db->prepare("SELECT id, file_name, file_path, file_type, file_size, created_at FROM notice_attachments WHERE notice_id = :notice_id ORDER BY created_at ASC");
        $stmt->execute(['notice_id' => $noticeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttachmentById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, notice_id, file_name, file_path, file_type, file_size FROM notice_attachments WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
