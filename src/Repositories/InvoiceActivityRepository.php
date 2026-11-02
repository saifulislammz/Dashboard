<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

/**
 * InvoiceActivityRepository
 *
 * Dedicated data-access layer for invoice activity logs and audit trails.
 * Handles event recording and chronological history retrieval with automatic
 * schema self-healing to prevent runtime table errors.
 */
class InvoiceActivityRepository
{
    private PDO $db;
    private static ?bool $hasActivitiesTable = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Check if the invoice_activities table exists.
     * Automatically attempts to create the table if missing.
     *
     * @return bool
     */
    private function ensureTableExists(): bool
    {
        if (self::$hasActivitiesTable !== null) {
            return self::$hasActivitiesTable;
        }

        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'invoice_activities'");
            if ($stmt->fetch() !== false) {
                self::$hasActivitiesTable = true;
                return true;
            }

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `invoice_activities` (
                    `id`             BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
                    `invoice_id`     BIGINT UNSIGNED   NOT NULL COMMENT 'references invoices.id',
                    `invoice_number` VARCHAR(50)       NOT NULL COMMENT 'denormalized for fast track query',
                    `actor_id`       INT UNSIGNED      NULL DEFAULT NULL COMMENT 'user who performed the action',
                    `action`         VARCHAR(50)       NOT NULL COMMENT 'CREATED | UPDATED | STATUS_CHANGED | PRINTED | SOFT_DELETED | RESTORED',
                    `description`    VARCHAR(255)      NOT NULL COMMENT 'Human readable summary',
                    `details_json`   LONGTEXT          NULL COMMENT 'Snapshot diff / JSON metadata',
                    `ip_address`     VARCHAR(45)       NULL DEFAULT NULL,
                    `user_agent`     TEXT              NULL DEFAULT NULL,
                    `created_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_act_invoice_id`     (`invoice_id`),
                    INDEX `idx_act_invoice_number` (`invoice_number`),
                    INDEX `idx_act_actor_id`       (`actor_id`),
                    INDEX `idx_act_created_at`     (`created_at`),
                    INDEX `idx_act_action`         (`action`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            self::$hasActivitiesTable = true;
            return true;
        } catch (Throwable $e) {
            error_log('[InvoiceActivityRepository] Failed to ensure invoice_activities table: ' . $e->getMessage());
            self::$hasActivitiesTable = false;
            return false;
        }
    }

    /**
     * Record an invoice lifecycle event into invoice_activities.
     * Failsafe design: catches exceptions and logs to error_log to ensure
     * core business operations (creation, update, deletion) are never disrupted.
     *
     * @param int         $invoiceId     ID of the invoice
     * @param string      $invoiceNumber Unique invoice number string
     * @param int|null    $actorId       User ID of the actor (admin/staff)
     * @param string      $action        Action code (CREATED, UPDATED, STATUS_CHANGED, PRINTED, SOFT_DELETED, RESTORED)
     * @param string      $description   Human-readable summary of the action
     * @param array|null  $details       Optional metadata or diff array
     * @return void
     */
    public function logActivity(
        int $invoiceId,
        string $invoiceNumber,
        ?int $actorId,
        string $action,
        string $description,
        ?array $details = null
    ): void {
        try {
            if (!$this->ensureTableExists()) {
                return;
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $detailsJson = ($details !== null) ? json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

            $stmt = $this->db->prepare("
                INSERT INTO invoice_activities
                    (invoice_id, invoice_number, actor_id, action, description, details_json, ip_address, user_agent, created_at)
                VALUES
                    (:invoice_id, :invoice_number, :actor_id, :action, :description, :details_json, :ip_address, :user_agent, NOW())
            ");

            $stmt->bindValue(':invoice_id', $invoiceId, PDO::PARAM_INT);
            $stmt->bindValue(':invoice_number', $invoiceNumber, PDO::PARAM_STR);
            $stmt->bindValue(':actor_id', $actorId, $actorId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':action', $action, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->bindValue(':details_json', $detailsJson, $detailsJson !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':ip_address', substr($ip, 0, 45), PDO::PARAM_STR);
            $stmt->bindValue(':user_agent', $userAgent, $userAgent !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);

            $stmt->execute();
        } catch (Throwable $e) {
            error_log('[InvoiceActivityRepository] Logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve chronological activity timeline for a given invoice number.
     *
     * @param string $invoiceNumber
     * @return array
     */
    public function getActivitiesByInvoiceNumber(string $invoiceNumber): array
    {
        try {
            if (!$this->ensureTableExists()) {
                return [];
            }

            $stmt = $this->db->prepare("
                SELECT 
                    a.id,
                    a.invoice_id,
                    a.invoice_number,
                    a.actor_id,
                    a.action,
                    a.description,
                    a.details_json,
                    a.ip_address,
                    a.user_agent,
                    a.created_at,
                    u.username AS actor_username,
                    u.email AS actor_email
                FROM invoice_activities a
                LEFT JOIN users u ON a.actor_id = u.id
                WHERE a.invoice_number = :invoice_number
                ORDER BY a.id ASC
            ");
            $stmt->bindValue(':invoice_number', $invoiceNumber, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[InvoiceActivityRepository] getActivitiesByInvoiceNumber failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieve recent invoice activities across the entire system.
     *
     * @param int $limit
     * @return array
     */
    public function getRecentActivities(int $limit = 20): array
    {
        try {
            if (!$this->ensureTableExists()) {
                return [];
            }

            $stmt = $this->db->prepare("
                SELECT 
                    a.id,
                    a.invoice_id,
                    a.invoice_number,
                    a.actor_id,
                    a.action,
                    a.description,
                    a.details_json,
                    a.ip_address,
                    a.created_at,
                    u.username AS actor_username,
                    u.email AS actor_email
                FROM invoice_activities a
                LEFT JOIN users u ON a.actor_id = u.id
                ORDER BY a.id DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[InvoiceActivityRepository] getRecentActivities failed: ' . $e->getMessage());
            return [];
        }
    }
}
