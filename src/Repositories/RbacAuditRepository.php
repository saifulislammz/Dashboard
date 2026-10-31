<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class RbacAuditRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Record an RBAC administrative action in the audit log.
     *
     * @param int|null    $actorId
     * @param string      $eventType
     * @param string      $targetType
     * @param int|null    $targetId
     * @param array|null  $details
     * @param string|null $ipAddress
     * @return void
     */
    public function logEvent(
        ?int $actorId,
        string $eventType,
        string $targetType,
        ?int $targetId,
        ?array $details = null,
        ?string $ipAddress = null
    ): void {
        try {
            $ip = $ipAddress ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

            $stmt = $this->db->prepare("
                INSERT INTO rbac_audit_logs (actor_id, event_type, target_type, target_id, details_json, ip_address, created_at)
                VALUES (:actor_id, :event_type, :target_type, :target_id, :details_json, :ip_address, NOW())
            ");
            $stmt->bindValue(':actor_id', $actorId, $actorId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':event_type', $eventType, PDO::PARAM_STR);
            $stmt->bindValue(':target_type', $targetType, PDO::PARAM_STR);
            $stmt->bindValue(':target_id', $targetId, $targetId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':details_json', $detailsJson, $detailsJson ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':ip_address', substr($ip, 0, 45), PDO::PARAM_STR);
            $stmt->execute();
        } catch (\Throwable $e) {
            // Failsafe: Never let logging failure break core user actions, but log to error_log
            error_log("[RBAC Audit Error] " . $e->getMessage());
        }
    }

    /**
     * Fetch recent audit logs with actor information.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getRecentLogs(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.username as actor_name, u.email as actor_email
            FROM rbac_audit_logs a
            LEFT JOIN users u ON a.actor_id = u.id
            ORDER BY a.id DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

