<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use Delight\Auth\Auth;
use Delight\Auth\Status;
use Delight\Auth\Role;

/**
 * ProfileController — Thin Controller
 * Assembles profile display data via UserService.
 * No SQL in this controller — all DB access via UserService → UserRepository.
 */
class ProfileController
{
    private UserService $userService;
    private Auth        $auth;

    /** Status integer → human-readable label */
    private const STATUS_MAP = [
        Status::NORMAL         => 'Active',
        Status::ARCHIVED       => 'Archived',
        Status::BANNED         => 'Banned',
        Status::LOCKED         => 'Locked',
        Status::PENDING_REVIEW => 'Pending Review',
        Status::SUSPENDED      => 'Suspended',
    ];

    public function __construct(UserService $userService, Auth $auth)
    {
        $this->userService = $userService;
        $this->auth        = $auth;
    }

    public function index(): void
    {
        $email    = $this->auth->getEmail();
        $username = $this->auth->getUsername();

        // Resolve role label
        $role = 'Student';
        if ($this->auth->hasRole(\Delight\Auth\Role::ADMIN) || $this->auth->hasRole(Role::SUPER_ADMIN)) {
            $role = 'Administrator';
        } elseif ($this->auth->hasRole(\Delight\Auth\Role::MANAGER)) {
            $role = 'Teacher';
        }

        // Resolve status label via Service → Repository (no raw SQL here)
        $statusInt  = $this->userService->getUserStatus((int) $this->auth->getUserId());
        $statusText = self::STATUS_MAP[$statusInt] ?? 'Unknown';

        $auth = $this->auth;
        require __DIR__ . '/../../views/profile.php';
    }
}
