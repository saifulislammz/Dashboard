<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\PasswordResetService;
use Exception;

/**
 * ResetPasswordController
 *
 * Handles HTTP requests for token validation and new password submission.
 */
class ResetPasswordController
{
    private PasswordResetService $service;

    public function __construct(PasswordResetService $service)
    {
        $this->service = $service;
    }

    /**
     * Render the reset password form after validating selector and token pair.
     */
    public function showForm(): void
    {
        $selector = (string) ($_GET['selector'] ?? '');
        $token    = (string) ($_GET['token'] ?? '');

        $isTokenValid = false;
        $tokenError   = '';

        if (empty($selector) || empty($token)) {
            $tokenError = 'The password reset link is incomplete or missing required parameters.';
        } elseif (!$this->service->validateToken($selector, $token)) {
            $tokenError = 'This password reset link is invalid, expired, or has already been used. Please request a new link.';
        } else {
            $isTokenValid = true;
        }

        $error = $_SESSION['reset_error'] ?? '';
        unset($_SESSION['reset_error']);

        $csrfToken = generateCsrfToken();

        require __DIR__ . '/../../../views/auth/reset_password.php';
    }

    /**
     * Process new password submission.
     */
    public function handleReset(): void
    {
        $selector        = (string) ($_POST['selector'] ?? '');
        $token           = (string) ($_POST['token'] ?? '');
        $password        = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['password_confirmation'] ?? '');

        try {
            $submittedCsrf = (string) ($_POST['csrf_token'] ?? '');
            validateCsrfToken($submittedCsrf);

            $result = $this->service->resetPassword($selector, $token, $password, $confirmPassword);

            if ($result['success']) {
                // Success: Flash message on login screen and redirect
                $_SESSION['login_success'] = $result['message'];
                header('Location: index.php');
                exit();
            }

            $_SESSION['reset_error'] = $result['message'];
        } catch (Exception $e) {
            $_SESSION['reset_error'] = $e->getMessage() ?: 'Invalid Security Token. Please refresh and try again.';
        }

        // Redirect back to reset form on error
        $redirectUrl = 'reset_password.php?selector=' . urlencode($selector) . '&token=' . urlencode($token);
        header('Location: ' . $redirectUrl);
        exit();
    }
}

