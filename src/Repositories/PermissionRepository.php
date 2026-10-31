<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class PermissionRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Retrieve all system permissions ordered by module and sort_order.
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        $stmt = $this->db->query("
            SELECT id, module, action, slug, name, description, sort_order 
            FROM permissions 
            ORDER BY module ASC, sort_order ASC, id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve all permissions grouped by their module.
     *
     * @return array [ 'analytics' => [...], 'invoices' => [...] ]
     */
    public function getAllPermissionsGroupedByModule(): array
    {
        $all = $this->getAllPermissions();
        $grouped = [];
        foreach ($all as $perm) {
            $module = $perm['module'] ?? 'general';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $perm;
        }
        return $grouped;
    }

    /**
     * Get permission IDs currently assigned to a role.
     *
     * @param int $roleId
     * @return int[]
     */
    public function getPermissionIdsForRole(int $roleId): array
    {
        $stmt = $this->db->prepare("
            SELECT permission_id 
            FROM role_permissions 
            WHERE role_id = :role_id
        ");
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * Get permission slugs assigned to a role.
     *
     * @param int $roleId
     * @return string[]
     */
    public function getPermissionSlugsForRole(int $roleId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.slug 
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id
        ");
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Single optimized JOIN query: Get all unique permission slugs for a user.
     *
     * @param int $userId
     * @return string[]
     */
    public function getPermissionSlugsForUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.slug
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            INNER JOIN user_roles ur ON rp.role_id = ur.role_id
            INNER JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = :user_id 
              AND r.deleted_at IS NULL
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Atomically sync permissions for a role (DELETE old + INSERT new).
     *
     * @param int $roleId
     * @param int[] $permissionIds
     * @return void
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->db->beginTransaction();
        try {
            // Remove existing permissions
            $delStmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
            $delStmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $delStmt->execute();

            // Insert new permissions if any
            if (!empty($permissionIds)) {
                $insertStmt = $this->db->prepare("
                    INSERT INTO role_permissions (role_id, permission_id) 
                    VALUES (:role_id, :permission_id)
                ");
                foreach ($permissionIds as $permId) {
                    $insertStmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
                    $insertStmt->bindValue(':permission_id', (int) $permId, PDO::PARAM_INT);
                    $insertStmt->execute();
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
