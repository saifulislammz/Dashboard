<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Exception;
use Delight\Auth\Auth;

class UserService
{
    private UserRepository $repository;
    private Auth $auth;

    public function __construct(UserRepository $repository, Auth $auth)
    {
        $this->repository = $repository;
        $this->auth = $auth;
    }

    public function createUser(array $data, int $roleMask): int
    {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        $status = (int)($data['status'] ?? 0); 

        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception("All fields are required.");
        }
        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match.");
        }

        // Create user
        $userId = $this->auth->admin()->createUser($email, $password, $name);
        
        // Assign role
        $this->auth->admin()->addRoleForUserById($userId, $roleMask);
        
        // Update status if inactive (Delight Auth uses status 0 for active, 2 for locked/banned)
        if ($status === 2) {
            $this->repository->updateStatus($userId, 2);
        }

        return $userId;
    }

    public function updateUser(int $id, array $data): bool
    {
        $name = trim($data['name'] ?? '');
        
        if (empty($name) || !$id) {
            throw new Exception("Invalid input.");
        }
        
        return $this->repository->updateUsername($id, $name);
    }

    public function updateStatus(int $id, int $status): bool
    {
        return $this->repository->updateStatus($id, $status);
    }

    public function deleteUser(int $id): void
    {
        $this->auth->admin()->deleteUserById($id);
    }

    public function getPaginatedUsersByRole(int $roleMask, int $page, int $limit, string $search = '', string $sort = 'id', string $order = 'DESC'): array
    {
        $offset = ($page - 1) * $limit;
        $users = $this->repository->getPaginatedUsersByRole($roleMask, $limit, $offset, $search, $sort, $order);
        $total = $this->repository->countTotalUsersByRole($roleMask, $search);

        return [
            'data' => $users,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }

    /**
     * Get the raw status integer for a user (for display on profile page).
     */
    public function getUserStatus(int $userId): ?int
    {
        return $this->repository->getUserStatus($userId);
    }

    public function getProfilePicture(int $userId): ?string
    {
        return $this->repository->getProfilePicture($userId);
    }

    public function updateProfilePicture(int $userId, array $file): void
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error uploading file.");
        }

        if ($file['size'] > 1048576) {
            throw new Exception("Profile picture must be less than 1MB.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'image/png') {
            throw new Exception("Only PNG images are allowed.");
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'png') {
            throw new Exception("Only PNG images are allowed.");
        }

        // Delete old picture if exists
        $oldPicture = $this->repository->getProfilePicture($userId);
        if ($oldPicture && file_exists(__DIR__ . '/../../public/uploads/avatars/' . $oldPicture)) {
            unlink(__DIR__ . '/../../public/uploads/avatars/' . $oldPicture);
        }

        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = 'admin_' . $userId . '_' . time() . '.png';
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to save profile picture.");
        }

        $this->repository->updateProfilePicture($userId, $filename);
    }

    public function deleteProfilePicture(int $userId): void
    {
        $oldPicture = $this->repository->getProfilePicture($userId);
        if ($oldPicture && file_exists(__DIR__ . '/../../public/uploads/avatars/' . $oldPicture)) {
            unlink(__DIR__ . '/../../public/uploads/avatars/' . $oldPicture);
        }
        $this->repository->updateProfilePicture($userId, null);
    }
}

