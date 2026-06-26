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

    public function getPaginatedNotices(int $limit, int $offset, string $search = ''): array
    {
        $whereClause = "1=1";
        $params = [];

        if (!empty($search)) {
            $whereClause .= " AND MATCH(n.title, n.content) AGAINST(:search IN BOOLEAN MODE)";
            $params['search'] = $search;
        }

        $stmt = $this->db->prepare("
            SELECT n.id, n.title, n.content, n.target_audience, n.status, n.created_at, n.updated_at, u.username as creator_name 
            FROM notices n 
            LEFT JOIN users u ON n.created_by = u.id 
            WHERE $whereClause 
            ORDER BY n.created_at DESC 
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
            $whereClause .= " AND MATCH(n.title, n.content) AGAINST(:search IN BOOLEAN MODE)";
            $params['search'] = $search;
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

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO notices (title, content, target_audience, status, created_by) VALUES (:title, :content, :target_audience, :status, :created_by)");
        return $stmt->execute([
            'title' => $data['title'],
            'content' => $data['content'],
            'target_audience' => $data['target_audience'],
            'status' => $data['status'],
            'created_by' => $data['created_by']
        ]);
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
}
