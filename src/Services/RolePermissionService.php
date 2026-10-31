<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PermissionRepository;
use App\Repositories\RbacAuditRepository;
use App\Repositories\RoleRepository;
use App\Utils\PermissionCache;
use Delight\Auth\Auth;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class RolePermissionService
{
    private PDO $db;
    private RoleRepository $roleRepo;
    private PermissionRepository $permRepo;
    private RbacAuditRepository $auditRepo;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->roleRepo = new RoleRepository($db);
        $this->permRepo = new PermissionRepository($db);
        $this->auditRepo = new RbacAuditRepository($db);
    }

    /**
     * Get all active roles with user counts and permission counts.
     *
     * @return array
     */
    public function getAllRoles(): array
    {
        return $this->roleRepo->getAllRoles();
    }

    /**
     * Get permissions catalog grouped by module for rendering UI checkboxes.
     *
     * @return array
     */
    public function getPermissionMatrix(): array
    {
        return $this->permRepo->getAllPermissionsGroupedByModule();
    }

    /**
     * Get role detail along with assigned permission IDs.
     *
     * @param int $roleId
     * @return array
     */
    public function getRoleWithPermissions(int $roleId): array
    {
        $role = $this->roleRepo->findRoleById($roleId);
        if (!$role) {
            throw new InvalidArgumentException("Role not found with ID: {$roleId}");
        }

        $assignedPermIds = $this->permRepo->getPermissionIdsForRole($roleId);
        $role['assigned_permission_ids'] = $assignedPermIds;
        return $role;
    }

    /**
     * Create a new custom role with assigned permissions.
     *
     * @param string $name
     * @param string $slug
     * @param string|null $description
     * @param int[] $permissionIds
     * @param int $actorId
     * @return int New Role ID
     */
    public function createRole(
        string $name,
        string $slug,
        ?string $description,
        array $permissionIds,
        int $actorId
    ): int {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9_-]/', '_', $slug)));
        if (empty($slug)) {
            throw new InvalidArgumentException("Role slug cannot be empty and must be alphanumeric.");
        }

        if (strlen($name) < 2) {
            throw new InvalidArgumentException("Role name must be at least 2 characters.");
        }

        // Check duplicate slug
        $existing = $this->roleRepo->findRoleBySlug($slug);
        if ($existing) {
            throw new InvalidArgumentException("A role with slug '{$slug}' already exists.");
        }

        // Insert role
        $roleId = $this->roleRepo->createRole($name, $slug, $description, false);

        // Sync permissions
        if (!empty($permissionIds)) {
            $this->permRepo->syncRolePermissions($roleId, $permissionIds);
        }

        // Audit log
        $this->auditRepo->logEvent(
            $actorId,
            'ROLE_CREATED',
            'role',
            $roleId,
            [
                'name' => $name,
                'slug' => $slug,
                'permission_count' => count($permissionIds)
            ]
        );

        return $roleId;
    }

    /**
     * Update an existing role's details and permissions.
     *
     * @param int $roleId
     * @param string $name
     * @param string|null $description
     * @param int[] $permissionIds
     * @param int $actorId
     * @return bool
     */
    public function updateRole(
        int $roleId,
        string $name,
        ?string $description,
        array $permissionIds,
        int $actorId
    ): bool {
        $role = $this->roleRepo->findRoleById($roleId);
        if (!$role) {
            throw new InvalidArgumentException("Role not found with ID: {$roleId}");
        }

        if (strlen($name) < 2) {
            throw new InvalidArgumentException("Role name must be at least 2 characters.");
        }

        // Super Admin protection: Cannot empty super admin's permissions
        if ($role['slug'] === 'super_admin' && empty($permissionIds)) {
            throw new RuntimeException("Super Admin role must retain core permissions.");
        }

        // Update role info
        $this->roleRepo->updateRole($roleId, $name, $description);

        // Sync permissions
        $this->permRepo->syncRolePermissions($roleId, $permissionIds);

        // Invalidate cache for all affected users
        PermissionCache::invalidateRole($roleId, $this->db);

        // Audit log
        $this->auditRepo->logEvent(
            $actorId,
            'ROLE_UPDATED',
            'role',
            $roleId,
            [
                'name' => $name,
                'new_permission_count' => count($permissionIds)
            ]
        );

        return true;
    }

    /**
     * Delete a custom role.
     *
     * @param int $roleId
     * @param int $actorId
     * @return bool
     */
    public function deleteRole(int $roleId, int $actorId): bool
    {
        $role = $this->roleRepo->findRoleById($roleId);
        if (!$role) {
            throw new InvalidArgumentException("Role not found with ID: {$roleId}");
        }

        if ((int)$role['is_system'] === 1) {
            throw new RuntimeException("System-defined roles cannot be deleted.");
        }

        // Check if any users are currently assigned
        $usersCount = $this->roleRepo->countUsersWithRole($roleId);
        if ($usersCount > 0) {
            throw new RuntimeException("Cannot delete this role because {$usersCount} staff member(s) are currently assigned to it. Reassign them first.");
        }

        $this->roleRepo->softDeleteRole($roleId);

        $this->auditRepo->logEvent(
            $actorId,
            'ROLE_DELETED',
            'role',
            $roleId,
            ['slug' => $role['slug'], 'name' => $role['name']]
        );

        return true;
    }

    /**
     * Create a new staff account (Admin/Moderator/Accountant/etc.) and assign role.
     *
     * @param string $email
     * @param string $username
     * @param string $password
     * @param int $roleId
     * @param int $actorId
     * @param bool $actorIsSuperAdmin
     * @param Auth $auth
     * @return int User ID
     */
    public function createStaffAccount(
        string $email,
        string $username,
        string $password,
        int $roleId,
        int $actorId,
        bool $actorIsSuperAdmin,
        Auth $auth
    ): int {
        $email = trim(filter_var($email, FILTER_VALIDATE_EMAIL) ?: '');
        if (empty($email)) {
            throw new InvalidArgumentException("Please provide a valid email address.");
        }

        $username = trim($username);
        if (strlen($username) < 3) {
            throw new InvalidArgumentException("Username must be at least 3 characters.");
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException("Password must be at least 8 characters.");
        }

        $role = $this->roleRepo->findRoleById($roleId);
        if (!$role) {
            throw new InvalidArgumentException("Selected role does not exist.");
        }

        // Privilege Escalation Check: Only super admin can create another super admin
        if ($role['slug'] === 'super_admin' && !$actorIsSuperAdmin) {
            throw new RuntimeException("Only a Super Administrator can assign the Super Admin role.");
        }

        // Register user via Delight Auth with bitmask 1 (ROLE_ADMIN) so they can access the admin portal
        $rolesMask = 1; // ROLE_ADMIN
        if ($role['slug'] === 'super_admin') {
            $rolesMask |= 262144; // SUPER_ADMIN bitmask
        }

        try {
            $userId = (int) $auth->admin()->createUser($email, $password, $username);
            
            // Mark user verified and active
            $stmt = $this->db->prepare("UPDATE users SET verified = 1, status = 0, roles_mask = :mask WHERE id = :id");
            $stmt->bindValue(':mask', $rolesMask, PDO::PARAM_INT);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            throw new InvalidArgumentException("A user with this email address already exists.");
        } catch (\Delight\Auth\DuplicateUsernameException $e) {
            throw new InvalidArgumentException("This username is already taken.");
        } catch (\Throwable $e) {
            throw new RuntimeException("Failed to register staff account: " . $e->getMessage());
        }

        // Assign role in `user_roles`
        $this->roleRepo->assignRoleToUser($userId, $roleId, $actorId);

        // Bust cache
        PermissionCache::invalidateUser($userId);

        // Audit log
        $this->auditRepo->logEvent(
            $actorId,
            'STAFF_CREATED',
            'user',
            $userId,
            [
                'email' => $email,
                'username' => $username,
                'role_id' => $roleId,
                'role_slug' => $role['slug']
            ]
        );

        return $userId;
    }

    /**
     * Change assigned role for an existing staff member.
     *
     * @param int $targetUserId
     * @param int $newRoleId
     * @param int $actorId
     * @param bool $actorIsSuperAdmin
     * @return bool
     */
    public function assignRoleToStaff(
        int $targetUserId,
        int $newRoleId,
        int $actorId,
        bool $actorIsSuperAdmin
    ): bool {
        // Prevent modifying own role to avoid self-lockout
        if ($targetUserId === $actorId) {
            throw new RuntimeException("You cannot change your own role.");
        }

        $newRole = $this->roleRepo->findRoleById($newRoleId);
        if (!$newRole) {
            throw new InvalidArgumentException("Selected role does not exist.");
        }

        // Privilege Escalation Check: Only super admin can assign the Super Admin role
        if ($newRole['slug'] === 'super_admin' && !$actorIsSuperAdmin) {
            throw new RuntimeException("Only a Super Administrator can assign the Super Admin role.");
        }

        // Check existing role of target user
        $currentRole = $this->roleRepo->getUserPrimaryRole($targetUserId);
        if ($currentRole && $currentRole['slug'] === 'super_admin' && !$actorIsSuperAdmin) {
            throw new RuntimeException("You do not have permission to modify a Super Administrator's role.");
        }

        // Update user_roles
        $this->roleRepo->removeUserRoles($targetUserId);
        $this->roleRepo->assignRoleToUser($targetUserId, $newRoleId, $actorId);

        // Synchronize bitmask if Super Admin role assigned or revoked
        if ($newRole['slug'] === 'super_admin') {
            $stmt = $this->db->prepare("UPDATE users SET roles_mask = (roles_mask | 262144 | 1) WHERE id = :id");
        } else {
            // Retain ROLE_ADMIN (1), remove SUPER_ADMIN bit (262144)
            $stmt = $this->db->prepare("UPDATE users SET roles_mask = ((roles_mask | 1) & ~262144) WHERE id = :id");
        }
        $stmt->bindValue(':id', $targetUserId, PDO::PARAM_INT);
        $stmt->execute();

        // Invalidate cache
        PermissionCache::invalidateUser($targetUserId);

        // Audit log
        $this->auditRepo->logEvent(
            $actorId,
            'USER_ROLE_ASSIGNED',
            'user',
            $targetUserId,
            [
                'old_role' => $currentRole['name'] ?? 'None',
                'new_role' => $newRole['name'],
                'new_role_id' => $newRoleId
            ]
        );

        return true;
    }

    /**
     * Fetch staff members list with pagination.
     *
     * @param int $limit
     * @param int $offset
     * @param string $search
     * @return array
     */
    public function getStaffList(int $limit, int $offset, string $search = ''): array
    {
        return $this->roleRepo->getAllStaffWithRoles($limit, $offset, $search);
    }

    /**
     * Count total staff members.
     *
     * @param string $search
     * @return int
     */
    public function countStaff(string $search = ''): int
    {
        return $this->roleRepo->countTotalStaff($search);
    }

    /**
     * Get recent RBAC audit logs.
     *
     * @param int $limit
     * @return array
     */
    public function getAuditLogs(int $limit = 50): array
    {
        return $this->auditRepo->getRecentLogs($limit);
    }
}
