<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

/**
 * InvoiceEmailLogRepository
 *
 * Dedicated data-access repository for logging all manual and automatic invoice email dispatches.
 */
class InvoiceEmailLogRepository
{
    private PDO $db;
    private static ?bool $hasLogsTable = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Ensure the invoice_email_logs table exists (self-healing).
     */
    private function ensureTableExists(): bool
    {
        if (self::$hasLogsTable !== null) {
            return self::$hasLogsTable;
        }

        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'invoice_email_logs'");
            if ($stmt->fetch() !== false) {
                self::$hasLogsTable = true;
                return true;
            }

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `invoice_email_logs` (
                    `id`              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
                    `invoice_id`      BIGINT UNSIGNED   NOT NULL COMMENT 'references invoices.id',
                    `invoice_number`  VARCHAR(50)       NOT NULL,
                    `recipient_email` VARCHAR(255)      NOT NULL,
                    `trigger_type`    ENUM('manual', 'automatic') NOT NULL DEFAULT 'manual',
                    `status`          ENUM('sent', 'failed')      NOT NULL DEFAULT 'sent',
                    `sent_by`         INT UNSIGNED      NULL DEFAULT NULL COMMENT 'references users.id',
                    `error_message`   TEXT              NULL DEFAULT NULL,
                    `sent_at`         DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_iel_invoice_id`     (`invoice_id`),
                    INDEX `idx_iel_invoice_number` (`invoice_number`),
                    INDEX `idx_iel_recipient`      (`recipient_email`),
                    INDEX `idx_iel_status`         (`status`),
                    INDEX `idx_iel_trigger_type`   (`trigger_type`),
                    INDEX `idx_iel_sent_at`        (`sent_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            self::$hasLogsTable = true;
            return true;
        } catch (Throwable $e) {
            error_log('[InvoiceEmailLogRepository] Table verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Record an email delivery attempt (success or failure).
     */
    public function logAttempt(
        int $invoiceId,
        string $invoiceNumber,
        string $recipientEmail,
        string $triggerType,
        string $status,
        ?int $sentBy = null,
        ?string $errorMessage = null
    ): bool {
        if (!$this->ensureTableExists()) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO `invoice_email_logs`
                    (`invoice_id`, `invoice_number`, `recipient_email`, `trigger_type`, `status`, `sent_by`, `error_message`, `sent_at`)
                VALUES
                    (:invoice_id, :invoice_number, :recipient_email, :trigger_type, :status, :sent_by, :error_message, NOW())
            ");

            return $stmt->execute([
                ':invoice_id'      => $invoiceId,
                ':invoice_number'  => $invoiceNumber,
                ':recipient_email' => $recipientEmail,
                ':trigger_type'    => in_array($triggerType, ['manual', 'automatic'], true) ? $triggerType : 'manual',
                ':status'          => in_array($status, ['sent', 'failed'], true) ? $status : 'failed',
                ':sent_by'         => $sentBy > 0 ? $sentBy : null,
                ':error_message'   => $errorMessage,
            ]);
        } catch (Throwable $e) {
            error_log('[InvoiceEmailLogRepository] Failed to insert email log: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get email dispatch history for a single invoice.
     */
    public function getLogsByInvoiceId(int $invoiceId): array
    {
        if (!$this->ensureTableExists() || $invoiceId <= 0) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT l.*, u.username AS sent_by_name, u.email AS sent_by_email
                FROM `invoice_email_logs` l
                LEFT JOIN `users` u ON l.sent_by = u.id
                WHERE l.invoice_id = :invoice_id
                ORDER BY l.sent_at DESC
                LIMIT 50
            ");
            $stmt->execute([':invoice_id' => $invoiceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('[InvoiceEmailLogRepository] Failed to fetch email logs: ' . $e->getMessage());
            return [];
        }
    }
}
