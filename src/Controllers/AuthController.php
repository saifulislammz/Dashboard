<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;

/**
 * AuthController — Thin Controller
 * Handles change-password flow.
 * Validation and business logic live in AuthService.
 * No direct Delight Auth calls here.
 */
class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // ─── GET/POST /change_password.php ────────────────────────────
    public function changePassword(): void
    {
        global $auth;
        
        $successMessage = '';
        $errorMessage   = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                validateCsrfToken($_POST['csrf_token'] ?? '');

                $this->authService->changePassword(
                    $_POST['current_password'] ?? '',
                    $_POST['new_password']     ?? '',
                    $_POST['confirm_password'] ?? ''
                );

                $successMessage = 'Password updated successfully.';

            } catch (\Delight\Auth\InvalidPasswordException $e) {
                $errorMessage = 'Current password is incorrect.';
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                $errorMessage = 'Too many requests. Please try again later.';
            } catch (\Exception $e) {
                $errorMessage = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        }

        require __DIR__ . '/../../views/change_password.php';
    }
}
