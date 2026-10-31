<?php

declare(strict_types=1);

namespace App\Utils;

use App\Repositories\PermissionRepository;
use PDO;

/**
 * High-performance, zero-duplicate-query Permission Cache.
 * Combines in-memory static runtime cache with persistent atomic file cache.
 */
class PermissionCache
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour
    private const CACHE_PREFIX = 'rbac_perms_user_';

    /**
     * In-memory cache for the current PHP lifecycle / request.
     * Guarantees 0 disk and 0 DB queries on multiple checks in a single page load.
     *
     * @var array<int, array<string, bool>>
     */
    private static array $runtimeMemory = [];

    /**
     * Get associative map of permission slugs for a user: ['invoices.view' => true, ...]
     *
     * @param int $userId
     * @param PDO $db
     * @return array<string, bool>
     */
    public static function getUserPermissions(int $userId, PDO $db): array
    {
        if ($userId <= 0) {
            return [];
        }

        // 1. Check in-memory request-scoped cache (fastest, O(1))
        if (isset(self::$runtimeMemory[$userId])) {
            return self::$runtimeMemory[$userId];
        }

        $cacheKey = self::CACHE_PREFIX . $userId;

        // 2. Check persistent Cache (Redis or safe JSON File Cache)
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            self::$runtimeMemory[$userId] = $cached;
            return $cached;
        }

        // 3. Cache Miss: Execute single indexed JOIN query
        $permRepo = new PermissionRepository($db);
        $slugs = $permRepo->getPermissionSlugsForUser($userId);

        // Transform into hash map for O(1) isset() lookups
        $permMap = [];
        foreach ($slugs as $slug) {
            $permMap[$slug] = true;
        }

        // Store in both caches
        Cache::set($cacheKey, $permMap, self::CACHE_TTL_SECONDS);
        self::$runtimeMemory[$userId] = $permMap;

        return $permMap;
    }

    /**
     * Check if a user possesses a specific permission.
     *
     * @param int    $userId
     * @param string $slug
     * @param PDO    $db
     * @param bool   $isSuperAdmin
     * @return bool
     */
    public static function hasPermission(int $userId, string $slug, PDO $db, bool $isSuperAdmin = false): bool
    {
        // Super Admin bypass: Always permitted
        if ($isSuperAdmin) {
            return true;
        }

        if ($userId <= 0) {
            return false;
        }

        $perms = self::getUserPermissions($userId, $db);
        return isset($perms[$slug]);
    }

    /**
     * Check if user possesses ANY of the given permission slugs.
     *
     * @param int      $userId
     * @param string[] $slugs
     * @param PDO      $db
     * @param bool     $isSuperAdmin
     * @return bool
     */
    public static function hasAnyPermission(int $userId, array $slugs, PDO $db, bool $isSuperAdmin = false): bool
    {
        if ($isSuperAdmin) {
            return true;
        }

        if ($userId <= 0) {
            return false;
        }

        $perms = self::getUserPermissions($userId, $db);
        foreach ($slugs as $slug) {
            if (isset($perms[$slug])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Bust cache for a single user (e.g., when assigned a new role).
     *
     * @param int $userId
     * @return void
     */
    public static function invalidateUser(int $userId): void
    {
        unset(self::$runtimeMemory[$userId]);
        Cache::forget(self::CACHE_PREFIX . $userId);
    }

    /**
     * Bust cache for all users assigned to a specific role.
     *
     * @param int $roleId
     * @param PDO $db
     * @return void
     */
    public static function invalidateRole(int $roleId, PDO $db): void
    {
        self::$runtimeMemory = [];

        try {
            $stmt = $db->prepare("SELECT user_id FROM user_roles WHERE role_id = :role_id");
            $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $stmt->execute();
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            foreach ($userIds as $uid) {
                Cache::forget(self::CACHE_PREFIX . (int)$uid);
            }
        } catch (\Throwable $e) {
            error_log("[PermissionCache::invalidateRole Error] " . $e->getMessage());
        }
    }
}

