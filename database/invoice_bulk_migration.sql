-- =============================================================
-- Bulk Invoice Upload & Generate Migration
-- Created: 2026-11-02
-- Compatible: MySQL 5.7+ / MariaDB 10.2+ / MySQL 8.0+
-- Tables: invoice_bulk_batches, invoice_bulk_staging_rows
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. Table: invoice_bulk_batches
-- Tracks bulk upload sessions, file metadata, and overall metrics
-- -------------------------------------------------------------
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

-- -------------------------------------------------------------
-- 2. Table: invoice_bulk_staging_rows
-- Stores individual parsed rows for preview, validation, and generation tracking
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoice_bulk_staging_rows` (
    `id`                BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `batch_id`          BIGINT UNSIGNED   NOT NULL COMMENT 'references invoice_bulk_batches.id',
    `row_number`        INT UNSIGNED      NOT NULL COMMENT 'Row number in original file (excluding header)',
    `raw_data_json`     LONGTEXT          NOT NULL COMMENT 'Original row data in JSON format',
    `parsed_data_json`  LONGTEXT          NULL COMMENT 'Sanitized and typed row data in JSON format',
    `is_valid`          TINYINT(1)        NOT NULL DEFAULT 0 COMMENT '1 = valid, 0 = invalid',
    `validation_error`  TEXT              NULL COMMENT 'Human readable validation error explanation',
    `invoice_id`        BIGINT UNSIGNED   NULL DEFAULT NULL COMMENT 'Created invoice id after generation',
    `invoice_number`    VARCHAR(50)       NULL DEFAULT NULL COMMENT 'Generated invoice number',
    `generation_status` VARCHAR(20)       NOT NULL DEFAULT 'pending' COMMENT 'pending|generated|failed',
    `generation_error`  TEXT              NULL COMMENT 'Database or runtime error during creation',
    `created_at`        DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_batch_row` (`batch_id`, `row_number`),
    INDEX `idx_batch_valid` (`batch_id`, `is_valid`),
    INDEX `idx_batch_gen_status` (`batch_id`, `generation_status`),
    CONSTRAINT `fk_bulk_staging_batch`
        FOREIGN KEY (`batch_id`) REFERENCES `invoice_bulk_batches` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. Seed `invoices.bulk` Permission (if permissions table exists)
-- -------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`module`, `action`, `slug`, `name`, `description`, `sort_order`)
SELECT 'invoices', 'bulk', 'invoices.bulk', 'Bulk Upload & Generate', 'Upload CSV template and generate bulk invoices', 255
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions'
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;

