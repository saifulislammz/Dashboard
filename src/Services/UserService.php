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

    public function getPaginatedUsersByRole(int $roleMask, int $page, int $limit, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;
        $users = $this->repository->getPaginatedUsersByRole($roleMask, $limit, $offset, $search);
        $total = $this->repository->countTotalUsersByRole($roleMask, $search);

        return [
            'data' => $users,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
}
