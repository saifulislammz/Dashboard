<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MailerInterface;
use App\Repositories\UserRepository;
use Delight\Auth\UserAlreadyExistsException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * UserEmailManagementService — Isolated Business Logic Layer
 *
 * Handles administrative email updates for Students and Teachers.
 * Enforces strict sanitization, RFC email validation, case normalization,
 * privilege escalation prevention, atomic token invalidation, and transactional notification alerts.
 */
class UserEmailManagementService
{
    private UserRepository $userRepository;
    private ?MailerInterface $mailer;
    private string $appUrl;

    public function __construct(
        UserRepository $userRepository,
        ?MailerInterface $mailer = null,
        ?string $appUrl = null
    ) {
        $this->userRepository = $userRepository;
        $this->mailer         = $mailer;
        $this->appUrl         = rtrim($appUrl ?? ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost'), '/');
    }

    /**
     * Change a user's main login email by an administrator.
     *
     * @param int    $adminId          The ID of the administrator making the change.
     * @param int    $targetUserId     The target student or teacher user ID.
     * @param int    $expectedRoleMask The expected role bitmask (ROLE_STUDENT or ROLE_TEACHER).
     * @param string $rawNewEmail      The new email address entered by the admin.
     * @param string $ipAddress        Client IP address for audit records.
     * @param string $userAgent        Client User-Agent for audit records.
     *
     * @return array Summary of the change operation.
     *
     * @throws InvalidArgumentException   On invalid inputs or role mismatches.
     * @throws UserAlreadyExistsException If the new email is already taken.
     * @throws RuntimeException           On system or privilege failure.
     */
    public function changeUserEmail(
        int $adminId,
        int $targetUserId,
        int $expectedRoleMask,
        string $rawNewEmail,
        string $ipAddress = '127.0.0.1',
        string $userAgent = ''
    ): array {
        // ─── 1. Input Sanitization & Normalization ────────────────────
        // Strip null-bytes and control characters
        $cleanEmail = preg_replace('/[\x00-\x1F\x7F]/', '', $rawNewEmail);
        $cleanEmail = trim($cleanEmail ?? '');
        $cleanEmail = mb_strtolower($cleanEmail, 'UTF-8');
        $sanitizedEmail = filter_var($cleanEmail, FILTER_SANITIZE_EMAIL);

        // ─── 2. Input Validation ──────────────────────────────────────
        if (empty($sanitizedEmail)) {
            throw new InvalidArgumentException('New email address is required.');
        }

        if (strlen($sanitizedEmail) > 249) {
            throw new InvalidArgumentException('Email address cannot exceed 249 characters.');
        }

        if (!filter_var($sanitizedEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        if ($targetUserId <= 0) {
            throw new InvalidArgumentException('Invalid target user ID.');
        }

        // ─── 3. Target User Verification & Privilege Protection ──────
        $targetUser = $this->userRepository->findUserBasicById($targetUserId);
        if (!$targetUser) {
            throw new InvalidArgumentException('Target user account not found.');
        }

        $targetRolesMask = (int) ($targetUser['roles_mask'] ?? 0);

        // Prevent Privilege Escalation: Never allow changing admin or super admin email from student/teacher panel
        // Bitmask: ROLE_ADMIN is 1, Delight Auth SUPER_ADMIN is 262144, user ID 1 is primary developer/superadmin
        if ($targetUserId === 1 || ($targetRolesMask & 1) > 0 || ($targetRolesMask & 262144) > 0) {
            throw new RuntimeException('Administrative accounts cannot have their email modified through this interface.');
        }

        // Verify that target user belongs to expected scope (ROLE_STUDENT or ROLE_TEACHER)
        if (($targetRolesMask & $expectedRoleMask) === 0) {
            throw new InvalidArgumentException('Target user does not have the expected role for this section.');
        }

        $oldEmail = mb_strtolower(trim((string) $targetUser['email']), 'UTF-8');
        $userName = (string) ($targetUser['username'] ?? 'User');

        // Check if the new email is identical to current
        if ($oldEmail === $sanitizedEmail) {
            throw new InvalidArgumentException('The new email address must be different from the current email.');
        }

        // ─── 4. Duplicate Check (O(1) Indexed Look-up) ────────────────
        if ($this->userRepository->isEmailTaken($sanitizedEmail, $targetUserId)) {
            throw new UserAlreadyExistsException('This email address is already in use by another account.');
        }

        // ─── 5. Atomic Persistence & Token Revocation ─────────────────
        $this->userRepository->updateEmailAndRevokeTokens(
            $targetUserId,
            $oldEmail,
            $sanitizedEmail,
            $adminId,
            $ipAddress,
            $userAgent
        );

        // ─── 6. Dual Transactional Notification Dispatch ──────────────
        $this->dispatchNotifications($userName, $oldEmail, $sanitizedEmail);

        return [
            'success'   => true,
            'user_id'   => $targetUserId,
            'user_name' => $userName,
            'old_email' => $oldEmail,
            'new_email' => $sanitizedEmail,
        ];
    }

    /**
     * Dispatch notification emails to both new and old email addresses.
     * Wrapped with graceful exception handling so external mail transport issues
     * never roll back the primary database transaction.
     */
    private function dispatchNotifications(string $userName, string $oldEmail, string $newEmail): void
    {
        if ($this->mailer === null) {
            return;
        }

        $loginUrl = $this->appUrl . '/index.php';

        // 1. Send update confirmation to NEW email
        try {
            ob_start();
            require __DIR__ . '/../../views/emails/account_email_changed_html.php';
            $newHtml = (string) ob_get_clean();

            $newText = "Hello {$userName},\n\n"
                     . "An administrator has updated your primary Rahe Nazat Institute login email to: {$newEmail}\n"
                     . "Please sign in using this new email address: {$loginUrl}\n\n"
                     . "Rahe Nazat Institute";

            $this->mailer->send(
                $newEmail,
                'Your Account Email Has Been Updated - Rahe Nazat Institute',
                $newHtml,
                $newText,
                $userName
            );
        } catch (Throwable $e) {
            error_log('[EMAIL_MANAGEMENT_NOTIFICATION_ERROR] Failed to send confirmation to new email: ' . $e->getMessage());
        }

        // 2. Send security alert to OLD email
        try {
            ob_start();
            require __DIR__ . '/../../views/emails/account_email_security_alert_html.php';
            $oldHtml = (string) ob_get_clean();

            $oldText = "Security Alert: Account Email Changed\n\n"
                     . "Hello {$userName},\n\n"
                     . "This notice confirms that an administrator has changed the primary email address for your Rahe Nazat Institute account.\n"
                     . "Previous Email: {$oldEmail}\n"
                     . "New Primary Email: {$newEmail}\n\n"
                     . "If you did not request or expect this change, please contact institute administration immediately.\n\n"
                     . "Rahe Nazat Institute";

            $this->mailer->send(
                $oldEmail,
                'Security Alert: Account Email Changed - Rahe Nazat Institute',
                $oldHtml,
                $oldText,
                $userName
            );
        } catch (Throwable $e) {
            error_log('[EMAIL_MANAGEMENT_NOTIFICATION_ERROR] Failed to send security alert to old email: ' . $e->getMessage());
        }
    }
}

