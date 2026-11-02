<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use App\Services\UserProfileService;
use Delight\Auth\Auth;
use Delight\Auth\Status;
use Delight\Auth\Role;
use Throwable;

/**
 * ProfileController — Thin Controller
 * Assembles profile display data and handles updates via UserProfileService and UserService.
 * Strictly adheres to MVC: No raw SQL in this controller.
 */
class ProfileController
{
    private UserService         $userService;
    private UserProfileService  $profileService;
    private Auth                $auth;

    /** Status integer → human-readable label */
    private const STATUS_MAP = [
        Status::NORMAL         => 'Active',
        Status::ARCHIVED       => 'Archived',
        Status::BANNED         => 'Banned',
        Status::LOCKED         => 'Locked',
        Status::PENDING_REVIEW => 'Pending Review',
        Status::SUSPENDED      => 'Suspended',
    ];

    public function __construct(UserService $userService, UserProfileService $profileService, Auth $auth)
    {
        $this->userService    = $userService;
        $this->profileService = $profileService;
        $this->auth           = $auth;
    }

    public function index(): void
    {
        $userId = (int) $this->auth->getUserId();
        $email  = (string) $this->auth->getEmail();

        // Resolve role label
        $role = 'Student';
        if ($this->auth->hasRole(Role::ADMIN) || $this->auth->hasRole(Role::SUPER_ADMIN)) {
            $role = 'Administrator';
        } elseif ($this->auth->hasRole(Role::MANAGER)) {
            $role = 'Teacher';
        }

        // Fetch comprehensive user profile data via UserProfileService
        try {
            $profileData = $this->profileService->getCompleteUserProfile($userId);
            $user        = $profileData['user'];
            $profile     = $profileData['profile'];
            $username    = $user['username'] ?? $this->auth->getUsername();
            $statusText  = $profileData['status_label'];
            $statusInt   = $profileData['status_int'];
            $profilePic  = $user['profile_picture'] ?? null;
        } catch (Throwable $e) {
            $username   = $this->auth->getUsername();
            $statusInt  = $this->userService->getUserStatus($userId) ?? 0;
            $statusText = self::STATUS_MAP[$statusInt] ?? 'Active';
            $profilePic = $this->userService->getProfilePicture($userId);
            $profile    = [
                'phone'         => null,
                'date_of_birth' => null,
                'gender'        => null,
                'country'       => null,
                'city'          => null,
                'address'       => null,
                'age'           => null,
            ];
        }

        $auth = $this->auth;
        require __DIR__ . '/../../views/profile.php';
    }

    /**
     * Handle personal details update (Name, Birthday, Gender, Phone, Country, City, Address)
     * and optional simultaneous profile picture upload.
     */
    public function updateProfile(): void
    {
        try {
            $userId = (int) $this->auth->getUserId();

            // 1. Update personal details
            $this->profileService->updateProfileInfo($userId, $_POST);

            // 2. If a new profile picture is also attached, process upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
                $this->profileService->updateProfilePicture($userId, $_FILES['profile_picture']);
            }

            $_SESSION['profile_success'] = 'Profile details updated successfully.';
        } catch (Throwable $e) {
            $_SESSION['profile_error'] = $e->getMessage();
        }

        header("Location: profile.php");
        exit();
    }

    /**
     * Upload or update profile picture only.
     */
    public function updatePicture(): void
    {
        try {
            if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
                throw new \Exception('Please select an image file to upload.');
            }
            $userId = (int) $this->auth->getUserId();
            $this->profileService->updateProfilePicture($userId, $_FILES['profile_picture']);
            $_SESSION['profile_success'] = 'Profile picture updated successfully.';
        } catch (Throwable $e) {
            $_SESSION['profile_error'] = $e->getMessage();
        }
        header("Location: profile.php");
        exit();
    }

    /**
     * Remove current profile picture.
     */
    public function removePicture(): void
    {
        try {
            $userId = (int) $this->auth->getUserId();
            $this->profileService->deleteProfilePicture($userId);
            $_SESSION['profile_success'] = 'Profile picture removed successfully.';
        } catch (Throwable $e) {
            $_SESSION['profile_error'] = $e->getMessage();
        }
        header("Location: profile.php");
        exit();
    }
}
