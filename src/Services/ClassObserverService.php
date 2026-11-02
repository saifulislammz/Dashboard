<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClassObserverRepository;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * ClassObserverService
 *
 * Encapsulates authorization and business logic for Class Access Management (Observer Access).
 */
class ClassObserverService
{
    private ClassObserverRepository $repository;
    private PDO $db;

    public function __construct(PDO $db, ?ClassObserverRepository $repository = null)
    {
        $this->db         = $db;
        $this->repository = $repository ?? new ClassObserverRepository($db);
    }

    /**
     * Check if a specific user is authorized to view and access a classroom.
     * Super Admins or staff with 'classrooms.view_all' have unrestricted access.
     * Other staff must be assigned observers for the classroom.
     *
     * @param int $userId
     * @param int $classroomId
     * @return bool
     */
    public function canUserAccessClassroom(int $userId, int $classroomId): bool
    {
        if ($this->hasUnrestrictedAccess($userId)) {
            return true;
        }

        return $this->repository->isUserObserverForClassroom($classroomId, $userId);
    }

    /**
     * Check if a specific user is authorized to view and access a class session.
     *
     * @param int $userId
     * @param int $sessionId
     * @return bool
     */
    public function canUserAccessSession(int $userId, int $sessionId): bool
    {
        if ($this->hasUnrestrictedAccess($userId)) {
            return true;
        }

        return $this->repository->isUserObserverForSession($sessionId, $userId);
    }

    /**
     * Determine if a user has unrestricted global access to all classrooms.
     * If $userId is omitted, evaluates the currently authenticated session.
     *
     * @param int|null $userId
     * @return bool
     */
    public function hasUnrestrictedAccess(?int $userId = null): bool
    {
        if ($userId !== null) {
            if ($userId === 1) {
                return true;
            }
            try {
                $roleRepo = new \App\Repositories\RoleRepository($this->db);
                $role = $roleRepo->getUserPrimaryRole($userId);
                if ($role && ($role['slug'] ?? '') === 'super_admin') {
                    return true;
                }
                $permRepo = new \App\Repositories\PermissionRepository($this->db);
                $slugs = $permRepo->getPermissionSlugsForUser($userId);
                if (in_array('classrooms.view_all', $slugs, true)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Fall through to false
            }
            return false;
        }

        if (function_exists('isSuperAdmin') && isSuperAdmin()) {
            return true;
        }

        if (function_exists('hasPermission') && hasPermission('classrooms.view_all')) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user has permission to assign observers.
     * If $userId is omitted, evaluates the currently authenticated session.
     *
     * @param int|null $userId
     * @return bool
     */
    public function canAssignObservers(?int $userId = null): bool
    {
        if ($userId !== null) {
            if ($userId === 1) {
                return true;
            }
            try {
                $roleRepo = new \App\Repositories\RoleRepository($this->db);
                $role = $roleRepo->getUserPrimaryRole($userId);
                if ($role && ($role['slug'] ?? '') === 'super_admin') {
                    return true;
                }
                $permRepo = new \App\Repositories\PermissionRepository($this->db);
                $slugs = $permRepo->getPermissionSlugsForUser($userId);
                if (in_array('classrooms.assign_observer', $slugs, true)) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Fall through to false
            }
            return false;
        }

        if (function_exists('isSuperAdmin') && isSuperAdmin()) {
            return true;
        }

        if (function_exists('hasPermission') && hasPermission('classrooms.assign_observer')) {
            return true;
        }

        return false;
    }

    /**
     * Assign or synchronize observer accounts for a classroom.
     *
     * @param int $classroomId
     * @param array<int> $observerUserIds
     * @param int $actorId
     * @return bool
     */
    public function syncClassroomObservers(int $classroomId, array $observerUserIds, int $actorId): bool
    {
        if (!$this->canAssignObservers($actorId)) {
            throw new RuntimeException("Unauthorized: Only Super Administrators or authorized staff can assign class observers.");
        }

        if ($classroomId <= 0) {
            throw new InvalidArgumentException("Invalid classroom ID provided.");
        }

        return $this->repository->assignClassroomObservers($classroomId, $observerUserIds, $actorId);
    }

    /**
     * Assign or synchronize observer accounts for a specific live session.
     *
     * @param int $sessionId
     * @param array<int> $observerUserIds
    /**
     * Assign or synchronize observer accounts for a specific live session.
     *
     * @param int $sessionId
     * @param array<int> $observerUserIds
     * @param int $actorId
     * @return bool
     */
    public function syncSessionObservers(int $sessionId, array $observerUserIds, int $actorId): bool
    {
        if (!$this->canAssignObservers($actorId)) {
            throw new RuntimeException("Unauthorized: Only Super Administrators or authorized staff can assign session observers.");
        }

        if ($sessionId <= 0) {
            throw new InvalidArgumentException("Invalid session ID provided.");
        }

        return $this->repository->assignSessionObservers($sessionId, $observerUserIds, $actorId);
    }

    /**
     * Get list of all staff accounts eligible to be selected as class observers.
     *
     * @return array<array<string, mixed>>
     */
    public function getEligibleObservers(): array
    {
        return $this->repository->getEligibleObservers();
    }

    /**
     * Get IDs of observers assigned to a classroom.
     *
     * @param int $classroomId
     * @return array<int>
     */
    public function getClassroomObserverIds(int $classroomId): array
    {
        return $this->repository->getClassroomObserverIds($classroomId);
    }

    /**
     * Get assigned classroom IDs for the specified user.
     *
     * @param int $userId
     * @return array<int>
     */
    public function getAssignedClassroomIds(int $userId): array
    {
        return $this->repository->getAssignedClassroomIds($userId);
    }

    /**
     * Compile observer dashboard payload for assigned staff.
     *
     * @param int $userId
     * @return array<string, mixed>
     */
    public function getObserverDashboardData(int $userId): array
    {
        $assignedIds = $this->repository->getAssignedClassroomIds($userId);
        $upcoming    = $this->repository->getUpcomingObservedSessions($userId, 6);
        $past        = $this->repository->getPastObservedSessions($userId, 5);

        return [
            'is_observer'               => !empty($assignedIds),
            'assigned_classrooms_count' => count($assignedIds),
            'upcoming_sessions'         => $upcoming,
            'past_sessions'             => $past,
        ];
    }
}
