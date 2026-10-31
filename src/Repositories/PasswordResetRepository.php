<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

/**
 * PasswordResetRepository
 *
 * Dedicated repository for password reset database interactions,
 * session revocation, token cleanup, and audit logging.
 */
class PasswordResetRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find active user by email with necessary authentication flags.
     * Only projects explicit columns (no SELECT *).
     */
    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, email, username, status, verified, resettable, roles_mask, force_logout
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    /**
     * Get user ID associated with a reset selector.
     */
    public function getUserIdBySelector(string $selector): ?int
    {
        $stmt = $this->db->prepare('SELECT user FROM users_resets WHERE selector = :selector LIMIT 1');
        $stmt->execute(['selector' => $selector]);
        $val = $stmt->fetchColumn();

        return $val !== false ? (int) $val : null;
    }

    /**
     * Invalidate all previous reset requests for a user so only the newest request remains active.
     */
    public function deleteExistingResetsForUser(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users_resets WHERE user = :user_id');
        return $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Increment force_logout counter to terminate all active sessions across devices
     * upon successful password reset.
     */
    public function incrementForceLogout(int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users 
             SET force_logout = (force_logout + 1) % 16777215 
             WHERE id = :id'
        );
        return $stmt->execute(['id' => $userId]);
    }

    /**
     * Delete expired reset tokens across the system to maintain optimal table performance.
     */
    public function deleteExpiredTokens(): int
    {
        $stmt = $this->db->prepare('DELETE FROM users_resets WHERE expires < :now');
        $stmt->execute(['now' => time()]);
        return $stmt->rowCount();
    }

    /**
     * Log password reset events in users_audit_log for enterprise forensic compliance.
     */
    public function logAudit(
        ?int $userId,
        string $eventType,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $details = []
    ): bool {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO users_audit_log (user_id, event_at, event_type, ip_address, user_agent, details_json)
                 VALUES (:user_id, :event_at, :event_type, :ip_address, :user_agent, :details_json)'
            );

            return $stmt->execute([
                'user_id'      => $userId,
                'event_at'     => time(),
                'event_type'   => substr($eventType, 0, 128),
                'ip_address'   => substr($ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 49),
                'user_agent'   => $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
                'details_json' => !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (Throwable $e) {
            error_log('[PasswordResetRepository] Audit log failed: ' . $e->getMessage());
            return false;
        }
    }
}
