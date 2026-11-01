<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getPaginatedUsersByRole(int $roleMask, int $limit, int $offset, string $search = '', string $sort = 'id', string $order = 'DESC'): array
    {
        $whereClause = "roles_mask & :role1 = :role2";
        $params = ['role1' => $roleMask, 'role2' => $roleMask];

        if (!empty($search)) {
            $whereClause .= " AND (username LIKE :search1 OR email LIKE :search2)";
            $params['search1'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
        }

        $sortColumn = 'id';
        if ($sort === 'name') $sortColumn = 'username';
        elseif ($sort === 'email') $sortColumn = 'email';
        elseif ($sort === 'status') $sortColumn = 'status';

        $orderDirection = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $stmt = $this->db->prepare("
            SELECT id, email, username as name, status, registered 
            FROM users 
            WHERE $whereClause 
            ORDER BY $sortColumn $orderDirection 
            LIMIT :limit OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countTotalUsersByRole(int $roleMask, string $search = ''): int
    {
        $whereClause = "roles_mask & :role1 = :role2";
        $params = ['role1' => $roleMask, 'role2' => $roleMask];

        if (!empty($search)) {
            $whereClause .= " AND (username LIKE :search1 OR email LIKE :search2)";
            $params['search1'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare("SELECT COUNT(id) FROM users WHERE $whereClause");
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function updateUsername(int $id, string $username): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET username = :username WHERE id = :id");
        return $stmt->execute(['username' => $username, 'id' => $id]);
    }

    public function updateStatus(int $id, int $status): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Get all active users by role bitmask (for dropdowns, selections).
     * Status 0 = Normal/Active in Delight Auth.
     */
    public function getUsersByRole(int $roleMask): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email
             FROM users
             WHERE (roles_mask & :role) > 0
               AND status = 0
             ORDER BY username ASC"
        );
        $stmt->bindValue(':role', $roleMask, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user status integer from DB (for profile display etc.).
     */
    public function getUserStatus(int $userId): ?int
    {
        $stmt = $this->db->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }
    public function updateProfilePicture(int $userId, ?string $filename): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET profile_picture = :picture WHERE id = :id");
        return $stmt->execute(['picture' => $filename, 'id' => $userId]);
    }

    public function getProfilePicture(int $userId): ?string
    {
        $stmt = $this->db->prepare("SELECT profile_picture FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $val = $stmt->fetchColumn();
        return $val ? (string) $val : null;
    }

    /**
     * Retrieve basic user information for authentication and role verification.
     */
    public function findUserBasicById(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, email, username, status, roles_mask, verified, force_logout 
             FROM users 
             WHERE id = :id 
             LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Check if an email address is already taken by another user.
     * Uses the UNIQUE KEY index on email for O(1) performance.
     */
    public function isEmailTaken(string $email, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT id FROM users WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeUserId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Atomically update a user's primary login email, verify the account,
     * invalidate stale security tokens across devices, and write an immutable audit log.
     *
     * @throws \Throwable If any step fails, rolling back all changes atomically.
     */
    public function updateEmailAndRevokeTokens(
        int $userId,
        string $oldEmail,
        string $newEmail,
        int $adminId,
        string $ip,
        string $ua
    ): bool {
        $this->db->beginTransaction();
        try {
            // 1. Update user email, ensure verified = 1, increment force_logout counter
            $stmtUser = $this->db->prepare(
                "UPDATE users 
                 SET email = :new_email, 
                     verified = 1, 
                     force_logout = (force_logout + 1) % 16777215 
                 WHERE id = :id"
            );
            $stmtUser->execute([
                'new_email' => $newEmail,
                'id'        => $userId
            ]);

            // 2. Revoke pending password reset tokens
            $stmtResets = $this->db->prepare("DELETE FROM users_resets WHERE user = :user_id");
            $stmtResets->execute(['user_id' => $userId]);

            // 3. Revoke pending email confirmation requests
            $stmtConfirms = $this->db->prepare("DELETE FROM users_confirmations WHERE user_id = :user_id");
            $stmtConfirms->execute(['user_id' => $userId]);

            // 4. Revoke active remember-me sessions
            $stmtRemember = $this->db->prepare("DELETE FROM users_remembered WHERE user = :user_id");
            $stmtRemember->execute(['user_id' => $userId]);

            // 5. Insert audit log record in users_audit_log
            $detailsJson = json_encode([
                'event'               => 'USER_EMAIL_CHANGED',
                'old_email'           => $oldEmail,
                'new_email'           => $newEmail,
                'changed_by_admin_id' => $adminId,
                'timestamp'           => time()
            ], JSON_UNESCAPED_SLASHES);

            $stmtAudit = $this->db->prepare(
                "INSERT INTO users_audit_log 
                 (user_id, event_at, event_type, admin_id, ip_address, user_agent, details_json) 
                 VALUES (:user_id, :event_at, :event_type, :admin_id, :ip_address, :user_agent, :details_json)"
            );
            $stmtAudit->execute([
                'user_id'      => $userId,
                'event_at'     => time(),
                'event_type'   => 'USER_EMAIL_CHANGED',
                'admin_id'     => $adminId,
                'ip_address'   => substr($ip, 0, 49),
                'user_agent'   => substr($ua, 0, 500),
                'details_json' => $detailsJson
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
