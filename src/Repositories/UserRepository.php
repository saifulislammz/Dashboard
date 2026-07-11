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
        $whereClause = "roles_mask & :role1 = :role2";
        $params = ['role1' => $roleMask, 'role2' => $roleMask];

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
        $whereClause = "roles_mask & :role1 = :role2";
        $params = ['role1' => $roleMask, 'role2' => $roleMask];

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

    /**
     * Get all active users by role bitmask (for dropdowns, selections).
     * Status 0 = Normal/Active in Delight Auth.
     */
    public function getUsersByRole(int $roleMask): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email
             FROM users
             WHERE (roles_mask & :role) > 0
               AND status = 0
             ORDER BY username ASC"
        );
        $stmt->bindValue(':role', $roleMask, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user status integer from DB (for profile display etc.).
     */
    public function getUserStatus(int $userId): ?int
    {
        $stmt = $this->db->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }
    public function updateProfilePicture(int $userId, ?string $filename): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET profile_picture = :picture WHERE id = :id");
        return $stmt->execute(['picture' => $filename, 'id' => $userId]);
    }

    public function getProfilePicture(int $userId): ?string
    {
        $stmt = $this->db->prepare("SELECT profile_picture FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $val = $stmt->fetchColumn();
        return $val ? (string) $val : null;
    }
}
