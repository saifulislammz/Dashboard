<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;
use RuntimeException;

/**
 * InvoiceBulkRepository
 *
 * Dedicated data access layer for bulk invoice batch operations and staging.
 * Follows strict separation of concerns, explicit column selection, and PDO prepared statements.
 */
class InvoiceBulkRepository
{
    private PDO $db;
    private static bool $schemaEnsured = false;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureSchema();
    }

    /**
     * Self-healing schema migration: ensure tables exist.
     */
    private function ensureSchema(): void
    {
        if (self::$schemaEnsured) {
            return;
        }

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `invoice_bulk_batches` (
                    `id`              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
                    `batch_uuid`      VARCHAR(64)       NOT NULL COMMENT 'Cryptographic unique batch token',
                    `admin_id`        INT UNSIGNED      NOT NULL COMMENT 'references users.id (uploader)',
                    `original_name`   VARCHAR(255)      NOT NULL COMMENT 'Original uploaded file name',
                    `total_rows`      INT UNSIGNED      NOT NULL DEFAULT 0,
                    `valid_rows`      INT UNSIGNED      NOT NULL DEFAULT 0,
                    `invalid_rows`    INT UNSIGNED      NOT NULL DEFAULT 0,
                    `processed_rows`  INT UNSIGNED      NOT NULL DEFAULT 0,
                    `success_rows`    INT UNSIGNED      NOT NULL DEFAULT 0,
                    `failed_rows`     INT UNSIGNED      NOT NULL DEFAULT 0,
                    `status`          VARCHAR(20)       NOT NULL DEFAULT 'staged' COMMENT 'staged|generating|completed|failed',
                    `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_batch_uuid` (`batch_uuid`),
                    INDEX `idx_admin_created` (`admin_id`, `created_at`),
                    INDEX `idx_batch_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `invoice_bulk_staging_rows` (
                    `id`                BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
                    `batch_id`          BIGINT UNSIGNED   NOT NULL COMMENT 'references invoice_bulk_batches.id',
                    `row_number`        INT UNSIGNED      NOT NULL COMMENT 'Row number in original file',
                    `raw_data_json`     LONGTEXT          NOT NULL COMMENT 'Original row data in JSON',
                    `parsed_data_json`  LONGTEXT          NULL COMMENT 'Sanitized and typed row data',
                    `is_valid`          TINYINT(1)        NOT NULL DEFAULT 0 COMMENT '1=valid, 0=invalid',
                    `validation_error`  TEXT              NULL COMMENT 'Validation error explanation',
                    `invoice_id`        BIGINT UNSIGNED   NULL DEFAULT NULL COMMENT 'Created invoice id',
                    `invoice_number`    VARCHAR(50)       NULL DEFAULT NULL COMMENT 'Generated invoice number',
                    `generation_status` VARCHAR(20)       NOT NULL DEFAULT 'pending' COMMENT 'pending|generated|failed',
                    `generation_error`  TEXT              NULL COMMENT 'Database or runtime error',
                    `created_at`        DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_batch_row` (`batch_id`, `row_number`),
                    INDEX `idx_batch_valid` (`batch_id`, `is_valid`),
                    INDEX `idx_batch_gen_status` (`batch_id`, `generation_status`),
                    CONSTRAINT `fk_bulk_staging_batch`
                        FOREIGN KEY (`batch_id`) REFERENCES `invoice_bulk_batches` (`id`)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            self::$schemaEnsured = true;
        } catch (\Throwable $e) {
            // Log but don't crash instantiation if already configured
            error_log('[InvoiceBulkRepository::ensureSchema] Notice: ' . $e->getMessage());
        }
    }

    /**
     * Persist a new bulk upload batch and all its staged rows atomically.
     *
     * @param array $batchData Metadata: admin_id, original_name, total_rows, valid_rows, invalid_rows
     * @param array $stagedRows List of rows: [row_number, raw_data_json, parsed_data_json, is_valid, validation_error]
     * @return string Generated batch_uuid
     * @throws RuntimeException On DB failure
     */
    public function createBatch(array $batchData, array $stagedRows): string
    {
        $batchUuid = bin2hex(random_bytes(32));

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO invoice_bulk_batches
                    (batch_uuid, admin_id, original_name, total_rows, valid_rows, invalid_rows, status)
                VALUES
                    (:batch_uuid, :admin_id, :original_name, :total_rows, :valid_rows, :invalid_rows, 'staged')
            ");

            $stmt->execute([
                'batch_uuid'    => $batchUuid,
                'admin_id'      => $batchData['admin_id'],
                'original_name' => substr($batchData['original_name'], 0, 255),
                'total_rows'    => (int) ($batchData['total_rows'] ?? 0),
                'valid_rows'    => (int) ($batchData['valid_rows'] ?? 0),
                'invalid_rows'  => (int) ($batchData['invalid_rows'] ?? 0),
            ]);

            $batchId = (int) $this->db->lastInsertId();

            if (!empty($stagedRows)) {
                $this->insertStagedRowsChunked($batchId, $stagedRows, 100);
            }

            $this->db->commit();
            return $batchUuid;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[InvoiceBulkRepository::createBatch] Error: ' . $e->getMessage());
            throw new RuntimeException('Failed to store bulk upload batch: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch a batch by its UUID.
     *
     * @param string $uuid
     * @return array|null
     */
    public function findBatchByUuid(string $uuid): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                b.id, b.batch_uuid, b.admin_id, b.original_name,
                b.total_rows, b.valid_rows, b.invalid_rows,
                b.processed_rows, b.success_rows, b.failed_rows,
                b.status, b.created_at, b.updated_at,
                u.username AS admin_name, u.email AS admin_email
            FROM invoice_bulk_batches b
            LEFT JOIN users u ON b.admin_id = u.id
            WHERE b.batch_uuid = :uuid
            LIMIT 1
        ");
        $stmt->execute(['uuid' => $uuid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Fetch staged rows for preview with optional validity filter and pagination.
     *
     * @param int       $batchId
     * @param bool|null $onlyValid (null = all, true = valid only, false = invalid only)
     * @param int       $limit
     * @param int       $offset
     * @return array
     */
    public function getStagedRows(int $batchId, ?bool $onlyValid = null, int $limit = 200, int $offset = 0): array
    {
        $sql = "
            SELECT
                id, batch_id, row_number, raw_data_json, parsed_data_json,
                is_valid, validation_error, invoice_id, invoice_number,
                generation_status, generation_error, created_at
            FROM invoice_bulk_staging_rows
            WHERE batch_id = :batch_id
        ";

        $params = ['batch_id' => $batchId];

        if ($onlyValid !== null) {
            $sql .= " AND is_valid = :is_valid";
            $params['is_valid'] = $onlyValid ? 1 : 0;
        }

        $sql .= " ORDER BY row_number ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count staged rows for a batch with optional validity filter.
     *
     * @param int       $batchId
     * @param bool|null $onlyValid
     * @return int
     */
    public function countStagedRows(int $batchId, ?bool $onlyValid = null): int
    {
        $sql = "SELECT COUNT(id) FROM invoice_bulk_staging_rows WHERE batch_id = :batch_id";
        $params = ['batch_id' => $batchId];

        if ($onlyValid !== null) {
            $sql .= " AND is_valid = :is_valid";
            $params['is_valid'] = $onlyValid ? 1 : 0;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Get all valid rows of a batch ready for invoice generation.
     *
     * @param int $batchId
     * @return array
     */
    public function getValidRowsForGeneration(int $batchId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id, row_number, parsed_data_json, raw_data_json
            FROM invoice_bulk_staging_rows
            WHERE batch_id = :batch_id 
              AND is_valid = 1 
              AND generation_status = 'pending'
            ORDER BY row_number ASC
        ");
        $stmt->execute(['batch_id' => $batchId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update generation outcome for an individual row.
     *
     * @param int         $rowId
     * @param string      $status  'generated' | 'failed'
     * @param int|null    $invoiceId
     * @param string|null $invoiceNumber
     * @param string|null $error
     * @return bool
     */
    public function updateRowGenerationResult(
        int $rowId,
        string $status,
        ?int $invoiceId = null,
        ?string $invoiceNumber = null,
        ?string $error = null
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE invoice_bulk_staging_rows
            SET
                generation_status = :status,
                invoice_id        = :invoice_id,
                invoice_number    = :invoice_number,
                generation_error  = :generation_error
            WHERE id = :row_id
        ");

        return $stmt->execute([
            'status'           => $status,
            'invoice_id'       => $invoiceId,
            'invoice_number'   => $invoiceNumber,
            'generation_error' => $error,
            'row_id'           => $rowId,
        ]);
    }

    /**
     * Update batch status (e.g. staged -> generating).
     *
     * @param int    $batchId
     * @param string $status
     * @return bool
     */
    public function updateBatchStatus(int $batchId, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE invoice_bulk_batches
            SET status = :status
            WHERE id = :batch_id
        ");

        return $stmt->execute([
            'status'   => $status,
            'batch_id' => $batchId,
        ]);
    }

    /**
     * Mark batch as completed with aggregate counts.
     *
     * @param int $batchId
     * @param int $successCount
     * @param int $failedCount
     * @return bool
     */
    public function completeBatch(int $batchId, int $successCount, int $failedCount): bool
    {
        $totalProcessed = $successCount + $failedCount;
        $status = ($failedCount === 0) ? 'completed' : 'completed_with_errors';

        $stmt = $this->db->prepare("
            UPDATE invoice_bulk_batches
            SET
                status         = :status,
                processed_rows = :processed,
                success_rows   = :success,
                failed_rows    = :failed
            WHERE id = :batch_id
        ");

        return $stmt->execute([
            'status'    => $status,
            'processed' => $totalProcessed,
            'success'   => $successCount,
            'failed'    => $failedCount,
            'batch_id'  => $batchId,
        ]);
    }

    /**
     * Get all failed rows (validation failures + generation failures) for error CSV export.
     *
     * @param int $batchId
     * @return array
     */
    public function getErrorRows(int $batchId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                row_number, raw_data_json, validation_error, generation_error
            FROM invoice_bulk_staging_rows
            WHERE batch_id = :batch_id
              AND (is_valid = 0 OR generation_status = 'failed')
            ORDER BY row_number ASC
        ");
        $stmt->execute(['batch_id' => $batchId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all successfully generated invoices from this batch.
     *
     * @param int $batchId
     * @return array
     */
    public function getGeneratedInvoices(int $batchId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                sr.row_number, sr.invoice_id, sr.invoice_number,
                i.student_name, i.currency, i.grand_total, i.status, i.invoice_date, i.due_date
            FROM invoice_bulk_staging_rows sr
            INNER JOIN invoices i ON sr.invoice_id = i.id
            WHERE sr.batch_id = :batch_id
              AND sr.generation_status = 'generated'
            ORDER BY sr.row_number ASC
        ");
        $stmt->execute(['batch_id' => $batchId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===========================================================
    // PRIVATE HELPERS
    // ===========================================================

    /**
     * Chunked bulk insert into invoice_bulk_staging_rows.
     *
     * @param int   $batchId
     * @param array $rows
     * @param int   $chunkSize
     */
    private function insertStagedRowsChunked(int $batchId, array $rows, int $chunkSize = 100): void
    {
        $chunks = array_chunk($rows, $chunkSize);

        foreach ($chunks as $chunk) {
            $placeholders = [];
            $values = [];

            foreach ($chunk as $row) {
                $placeholders[] = "(?, ?, ?, ?, ?, ?)";
                $values[] = $batchId;
                $values[] = (int) $row['row_number'];
                $values[] = json_encode($row['raw_data'], JSON_UNESCAPED_UNICODE);
                $values[] = isset($row['parsed_data']) ? json_encode($row['parsed_data'], JSON_UNESCAPED_UNICODE) : null;
                $values[] = !empty($row['is_valid']) ? 1 : 0;
                $values[] = $row['validation_error'] ?? null;
            }

            $sql = "
                INSERT INTO invoice_bulk_staging_rows
                    (batch_id, row_number, raw_data_json, parsed_data_json, is_valid, validation_error)
                VALUES " . implode(', ', $placeholders);

            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
        }
    }
}
