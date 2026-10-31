<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\PasswordResetService;
use Exception;

/**
 * ForgotPasswordController
 *
 * Handles HTTP requests for the password reset request stage.
 */
class ForgotPasswordController
{
    private PasswordResetService $service;

    public function __construct(PasswordResetService $service)
    {
        $this->service = $service;
    }

    /**
     * Render the forgot password view with flash messages.
     */
    public function showForm(): void
    {
        $error    = $_SESSION['forgot_error'] ?? '';
        $success  = $_SESSION['forgot_success'] ?? '';
        $oldEmail = $_SESSION['old_email'] ?? '';

        unset($_SESSION['forgot_error'], $_SESSION['forgot_success'], $_SESSION['old_email']);

        $csrfToken = generateCsrfToken();

        require __DIR__ . '/../../../views/auth/forgot_password.php';
    }

    /**
     * Process the POST request for reset link generation.
     */
    public function handleRequest(): void
    {
        try {
            $submittedCsrf = (string) ($_POST['csrf_token'] ?? '');
            validateCsrfToken($submittedCsrf);

            $rawEmail = (string) ($_POST['email'] ?? '');
            $result   = $this->service->initiatePasswordReset($rawEmail);

            if ($result['success']) {
                $_SESSION['forgot_success'] = $result['message'];
            } else {
                $_SESSION['forgot_error'] = $result['message'];
                $_SESSION['old_email']    = htmlspecialchars(trim($rawEmail), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        } catch (Exception $e) {
            $_SESSION['forgot_error'] = $e->getMessage() ?: 'Invalid Security Token. Please refresh and try again.';
        }

        header('Location: forgot_password.php');
        exit();
    }
}

