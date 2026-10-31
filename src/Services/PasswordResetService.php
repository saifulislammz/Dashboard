<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MailerInterface;
use App\Repositories\PasswordResetRepository;
use Delight\Auth\Auth;
use Delight\Auth\AuthException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\ResetDisabledException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Throwable;

/**
 * PasswordResetService
 *
 * Core business logic for password resets:
 * - Anti-enumeration email handling
 * - Cryptographic split-token generation & expiry
 * - Mail template compilation and delivery
 * - Strict password complexity validation
 * - Instant session invalidation across devices
 */
class PasswordResetService
{
    private const RESET_TOKEN_LIFETIME = 1800; // 30 minutes in seconds

    private Auth $auth;
    private PasswordResetRepository $repository;
    private MailerInterface $mailer;
    private string $appUrl;

    public function __construct(
        Auth $auth,
        PasswordResetRepository $repository,
        MailerInterface $mailer,
        ?string $appUrl = null
    ) {
        $this->auth       = $auth;
        $this->repository = $repository;
        $this->mailer     = $mailer;
        $this->appUrl     = $appUrl ?? ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost');
    }

    /**
     * Initiate password reset request for a student or teacher.
     * Enforces Anti-User Enumeration: always returns a positive user message
     * even if the email does not exist in the database.
     *
     * @param string $rawEmail
     * @return array ['success' => bool, 'message' => string]
     */
    public function initiatePasswordReset(string $rawEmail): array
    {
        $genericSuccess = 'If your email is registered in our system, you will receive a password reset link shortly. Please check your inbox and spam folder.';

        // 1. Sanitize email input
        $email = preg_replace('/[\x00-\x1F\x7F]/', '', $rawEmail);
        $email = trim($email);
        $email = mb_strtolower($email, 'UTF-8');
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            return [
                'success' => false,
                'message' => 'Please enter a valid email address.'
            ];
        }

        // 2. Check user existence and eligibility
        $user = $this->repository->findUserByEmail($email);

        if (!$user || (int) $user['status'] !== 0 || (int) $user['resettable'] !== 1) {
            // Mitigate timing attacks with a random microsecond sleep
            usleep(random_int(80000, 200000));
            return [
                'success' => true,
                'message' => $genericSuccess
            ];
        }

        // 3. Delete any stale reset requests for this user to ensure single-link integrity
        $this->repository->deleteExistingResetsForUser((int) $user['id']);

        // 4. Request password reset via Delight Auth
        try {
            $userName = $user['username'] ?? '';
            $mailSent = false;

            $this->auth->forgotPassword(
                $email,
                function ($selector, $token) use ($email, $userName, &$mailSent) {
                    $resetUrl = rtrim($this->appUrl, '/') . '/reset_password.php?selector=' . urlencode($selector) . '&token=' . urlencode($token);
                    $expiresMinutes = (int) (self::RESET_TOKEN_LIFETIME / 60);

                    // Compile HTML and Plain-text templates
                    $userEmail = $email;

                    ob_start();
                    require __DIR__ . '/../../views/emails/password_reset_html.php';
                    $htmlBody = ob_get_clean();

                    ob_start();
                    require __DIR__ . '/../../views/emails/password_reset_text.php';
                    $textBody = ob_get_clean();

                    // Send email
                    $mailSent = $this->mailer->send(
                        $email,
                        'Password Reset Request - Rahe Nazat Institute',
                        (string) $htmlBody,
                        (string) $textBody,
                        $userName
                    );
                },
                self::RESET_TOKEN_LIFETIME
            );

            // Log successful reset request
            $this->repository->logAudit((int) $user['id'], 'password_reset_requested', null, null, [
                'email'     => $email,
                'mail_sent' => $mailSent
            ]);

            return [
                'success' => true,
                'message' => $genericSuccess
            ];
        } catch (TooManyRequestsException $e) {
            return [
                'success' => false,
                'message' => 'Too many reset requests. Please wait a few minutes before trying again.'
            ];
        } catch (EmailNotVerifiedException | ResetDisabledException | InvalidEmailException $e) {
            // Anti-enumeration: preserve generic message
            return [
                'success' => true,
                'message' => $genericSuccess
            ];
        } catch (Throwable $e) {
            error_log('[PasswordResetService] Reset request error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again later.'
            ];
        }
    }

    /**
     * Check whether a given selector and token pair is currently valid.
     */
    public function validateToken(string $selector, string $token): bool
    {
        $selector = trim($selector);
        $token    = trim($token);

        if (empty($selector) || empty($token)) {
            return false;
        }

        try {
            return $this->auth->canResetPassword($selector, $token);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Complete the password reset with strict validation and session termination.
     *
     * @param string $selector
     * @param string $token
     * @param string $password
     * @param string $confirmPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public function resetPassword(string $selector, string $token, string $password, string $confirmPassword): array
    {
        $selector = trim($selector);
        $token    = trim($token);

        if (empty($selector) || empty($token)) {
            return [
                'success' => false,
                'message' => 'Invalid or missing reset token.'
            ];
        }

        // 1. Password presence & confirmation
        if (empty($password) || empty($confirmPassword)) {
            return [
                'success' => false,
                'message' => 'Please provide both new password and confirmation password.'
            ];
        }

        if ($password !== $confirmPassword) {
            return [
                'success' => false,
                'message' => 'New password and confirm password do not match.'
            ];
        }

        // 2. Strip non-printable / control characters
        $cleanPassword = preg_replace('/[\x00-\x1F\x7F]/', '', $password);
        if ($cleanPassword !== $password) {
            return [
                'success' => false,
                'message' => 'Password contains invalid non-printable characters.'
            ];
        }

        // 3. Password length bounds (NIST standard)
        $len = strlen($password);
        if ($len < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters long.'
            ];
        }
        if ($len > 128) {
            return [
                'success' => false,
                'message' => 'Password must not exceed 128 characters.'
            ];
        }

        // 4. Complexity requirements
        $hasUpper   = (bool) preg_match('/[A-Z]/', $password);
        $hasLower   = (bool) preg_match('/[a-z]/', $password);
        $hasNumber  = (bool) preg_match('/[0-9]/', $password);
        $hasSpecial = (bool) preg_match('/[^A-Za-z0-9]/', $password);

        if (!$hasUpper || !$hasLower || !$hasNumber || !$hasSpecial) {
            return [
                'success' => false,
                'message' => 'Password must contain uppercase, lowercase, numbers, and at least one special character.'
            ];
        }

        // 5. Execute password reset in Delight Auth
        try {
            $preResolvedUserId = $this->repository->getUserIdBySelector($selector);

            $idAndEmail = $this->auth->resetPassword($selector, $token, $password);
            $userId = (int) ($idAndEmail['id'] ?? $preResolvedUserId ?? 0);

            if ($userId > 0) {
                // Invalidate all existing login sessions across all devices
                $this->repository->incrementForceLogout($userId);

                // Audit log
                $this->repository->logAudit($userId, 'password_reset_completed', null, null, [
                    'email' => $idAndEmail['email'] ?? ''
                ]);
            }

            return [
                'success' => true,
                'message' => 'Your password has been reset successfully. Please log in with your new password.'
            ];
        } catch (\Delight\Auth\HeadersAlreadySentError $e) {
            // Delight Auth updates the password in DB first, but throws HeadersAlreadySentError
            // if output started (e.g. CLI or background test scripts) when attempting to clear
            // the remember cookie. We ensure token cleanup, session termination, and report success.
            $userId = $preResolvedUserId ?? 0;
            if ($userId > 0) {
                $this->repository->deleteExistingResetsForUser($userId);
                $this->repository->incrementForceLogout($userId);
                $this->repository->logAudit($userId, 'password_reset_completed', null, null, [
                    'notice' => 'Headers were already sent prior to cookie revocation'
                ]);
            }

            return [
                'success' => true,
                'message' => 'Your password has been reset successfully. Please log in with your new password.'
            ];
        } catch (InvalidSelectorTokenPairException $e) {
            return [
                'success' => false,
                'message' => 'This password reset link is invalid or has already been used. Please request a new one.'
            ];
        } catch (TokenExpiredException $e) {
            return [
                'success' => false,
                'message' => 'This password reset link has expired. Please request a new one.'
            ];
        } catch (ResetDisabledException $e) {
            return [
                'success' => false,
                'message' => 'Password reset is disabled for this account.'
            ];
        } catch (InvalidPasswordException $e) {
            return [
                'success' => false,
                'message' => 'Password does not meet required security standards.'
            ];
        } catch (TooManyRequestsException $e) {
            return [
                'success' => false,
                'message' => 'Too many failed attempts. Please try again in a few minutes.'
            ];
        } catch (AuthException $e) {
            return [
                'success' => false,
                'message' => 'Authentication error occurred. Please try requesting a new reset link.'
            ];
        } catch (Throwable $e) {
            error_log('[PasswordResetService] Reset execution error: ' . get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }
    }
}
