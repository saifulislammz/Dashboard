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
        $allowedSortFields = ['title', 'target_audience', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'created_at';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        $orderBy = "n.{$sortField} {$sortOrder}";

        $stmt = $this->db->prepare("
            SELECT n.id, n.title, n.content, n.target_audience, n.status, n.created_at, n.updated_at, u.username as creator_name 
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $stmt = $this->db->prepare("SELECT id, title, content, target_audience, status, created_by, created_at, updated_at FROM notices WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int|false
    {
        $stmt = $this->db->prepare("INSERT INTO notices (title, content, target_audience, status, created_by) VALUES (:title, :content, :target_audience, :status, :created_by)");
        if ($stmt->execute([
            'title' => $data['title'],
            'content' => $data['content'],
            'target_audience' => $data['target_audience'],
            'status' => $data['status'],
            'created_by' => $data['created_by']
        ])) {
            return (int) $this->db->lastInsertId();
        }
        return false;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE notices SET title = :title, content = :content, target_audience = :target_audience, status = :status WHERE id = :id");
        return $stmt->execute([
            'title' => $data['title'],
            'content' => $data['content'],
            'target_audience' => $data['target_audience'],
            'status' => $data['status'],
            'id' => $id
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

    /**
     * Fetch active notices for a given audience (student | teacher | both).
     * Used by Student & Teacher dashboards.
     */
    public function getActiveNoticesByAudience(string $audience, int $limit = 10): array
    {
        $allowedAudiences = ['student', 'teacher', 'both'];
        if (!in_array($audience, $allowedAudiences, true)) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT id, title, content, created_at
             FROM notices
             WHERE status = 'active'
               AND target_audience IN (:audience, 'both')
             ORDER BY created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':audience', $audience);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch attachments for these notices
        foreach ($notices as &$notice) {
            $notice['attachments'] = $this->getAttachmentsByNoticeId((int) $notice['id']);
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

