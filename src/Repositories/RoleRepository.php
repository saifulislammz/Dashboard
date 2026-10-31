<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class RoleRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch all active roles.
     *
     * @param bool $includeDeleted
     * @return array
     */
    public function getAllRoles(bool $includeDeleted = false): array
    {
        $sql = "SELECT r.*, COUNT(ur.user_id) as users_count, COUNT(rp.permission_id) as permissions_count 
                FROM roles r 
                LEFT JOIN user_roles ur ON r.id = ur.role_id 
                LEFT JOIN role_permissions rp ON r.id = rp.role_id";

        if (!$includeDeleted) {
            $sql .= " WHERE r.deleted_at IS NULL";
        }

        $sql .= " GROUP BY r.id ORDER BY r.is_system DESC, r.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a role by its ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findRoleById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find a role by its slug.
     *
     * @param string $slug
     * @return array|null
     */
    public function findRoleBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE slug = :slug AND deleted_at IS NULL LIMIT 1");
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Create a new role.
     *
     * @param string $name
     * @param string $slug
     * @param string|null $description
     * @param bool $isSystem
     * @return int Inserted ID
     */
    public function createRole(string $name, string $slug, ?string $description = null, bool $isSystem = false): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO roles (name, slug, description, is_system, created_at, updated_at) 
            VALUES (:name, :slug, :description, :is_system, NOW(), NOW())
        ");
        $stmt->bindValue(':name', trim($name), PDO::PARAM_STR);
        $stmt->bindValue(':slug', trim($slug), PDO::PARAM_STR);
        $stmt->bindValue(':description', $description ? trim($description) : null, PDO::PARAM_STR);
        $stmt->bindValue(':is_system', $isSystem ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update role details.
     *
     * @param int $id
     * @param string $name
     * @param string|null $description
     * @return bool
     */
    public function updateRole(int $id, string $name, ?string $description = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE roles 
            SET name = :name, description = :description, updated_at = NOW() 
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', trim($name), PDO::PARAM_STR);
        $stmt->bindValue(':description', $description ? trim($description) : null, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Soft delete a custom role (never soft delete system roles).
     *
     * @param int $id
     * @return bool
     */
    public function softDeleteRole(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE roles 
            SET deleted_at = NOW() 
            WHERE id = :id AND is_system = 0 AND deleted_at IS NULL
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Count how many active users are currently assigned to this role.
     *
     * @param int $roleId
     * @return int
     */
    public function countUsersWithRole(int $roleId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_roles WHERE role_id = :role_id");
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Assign a role to a user.
     *
     * @param int $userId
     * @param int $roleId
     * @param int|null $assignedBy
     * @return bool
     */
    public function assignRoleToUser(int $userId, int $roleId, ?int $assignedBy = null): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_roles (user_id, role_id, assigned_by, assigned_at) 
            VALUES (:user_id, :role_id, :assigned_by, NOW())
            ON DUPLICATE KEY UPDATE 
                assigned_by = VALUES(assigned_by),
                assigned_at = VALUES(assigned_at)
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':assigned_by', $assignedBy, $assignedBy ? PDO::PARAM_INT : PDO::PARAM_NULL);
        return $stmt->execute();
    }

    /**
     * Clear existing roles for a user.
     *
     * @param int $userId
     * @return bool
     */
    public function removeUserRoles(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get a user's assigned role(s).
     *
     * @param int $userId
     * @return array|null
     */
    public function getUserPrimaryRole(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, ur.assigned_at, ur.assigned_by 
            FROM roles r 
            JOIN user_roles ur ON r.id = ur.role_id 
            WHERE ur.user_id = :user_id AND r.deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get all role IDs assigned to a user.
     *
     * @param int $userId
     * @return array
     */
    public function getUserRoleIds(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT role_id 
            FROM user_roles 
            WHERE user_id = :user_id
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Fetch staff/admin accounts with their assigned roles.
     *
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @return array
     */
    public function getAllStaffWithRoles(int $limit, int $offset, string $search = ''): array
    {
        $whereSql = "WHERE (u.roles_mask & 1 = 1 OR u.roles_mask & 262144 = 262144 OR ur.role_id IS NOT NULL)";
        $params = [];

        if (!empty($search)) {
            $whereSql .= " AND (u.username LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $sql = "
            SELECT 
                u.id, 
                u.email, 
                u.username, 
                u.status, 
                u.registered, 
                u.last_login,
                u.roles_mask,
                r.id as role_id,
                r.name as role_name,
                r.slug as role_slug,
                r.is_system as role_is_system,
                ur.assigned_at
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id AND r.deleted_at IS NULL
            $whereSql
            ORDER BY u.id ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total staff members.
     *
     * @param string $search
     * @return int
     */
    public function countTotalStaff(string $search = ''): int
    {
        $whereSql = "WHERE (u.roles_mask & 1 = 1 OR u.roles_mask & 262144 = 262144 OR ur.role_id IS NOT NULL)";
        $params = [];

        if (!empty($search)) {
            $whereSql .= " AND (u.username LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $sql = "
            SELECT COUNT(DISTINCT u.id)
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            $whereSql
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}

