<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getPaginatedUsersByRole(int $roleMask, int $limit, int $offset, string $search = ''): array
    {
        $whereClause = "roles_mask & :role = :role";
        $params = ['role' => $roleMask];

        if (!empty($search)) {
            $whereClause .= " AND MATCH(username, email) AGAINST(:search IN BOOLEAN MODE)";
            $params['search'] = $search;
        }

        $stmt = $this->db->prepare("
            SELECT id, email, username as name, status, registered 
            FROM users 
            WHERE $whereClause 
            ORDER BY id DESC 
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

    public function countTotalUsersByRole(int $roleMask, string $search = ''): int
    {
        $whereClause = "roles_mask & :role = :role";
        $params = ['role' => $roleMask];

        if (!empty($search)) {
            $whereClause .= " AND MATCH(username, email) AGAINST(:search IN BOOLEAN MODE)";
            $params['search'] = $search;
        }

        $stmt = $this->db->prepare("SELECT COUNT(id) FROM users WHERE $whereClause");
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function updateUsername(int $id, string $username): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET username = :username WHERE id = :id");
        return $stmt->execute(['username' => $username, 'id' => $id]);
    }

    public function updateStatus(int $id, int $status): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
