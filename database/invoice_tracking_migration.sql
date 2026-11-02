-- =============================================================
-- Invoice Tracking & Account Information Migration
-- Created: 2026-11-02
-- Compatible: MySQL 5.7+ / MariaDB 10.2+ / MySQL 8.0+
-- Safe to re-run: idempotent checks via information_schema
-- =============================================================

-- Step 1: Ensure `deleted_by` column exists in `invoices` table
SET @dbname = DATABASE();
SET @col_deleted_by = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME   = 'invoices'
      AND COLUMN_NAME  = 'deleted_by'
);

SET @sql_deleted_by = IF(
    @col_deleted_by = 0,
    'ALTER TABLE `invoices` ADD COLUMN `deleted_by` INT UNSIGNED NULL DEFAULT NULL COMMENT "references users.id" AFTER `deleted_at`, ADD INDEX `idx_invoices_deleted_by` (`deleted_by`)',
    'SELECT "[SKIP] deleted_by column already exists" AS info'
);
PREPARE stmt_del_by FROM @sql_deleted_by;
EXECUTE stmt_del_by;
DEALLOCATE PREPARE stmt_del_by;

-- Step 2: Create `invoice_activities` table for audit trail & history tracking
CREATE TABLE IF NOT EXISTS `invoice_activities` (
    `id`             BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `invoice_id`     BIGINT UNSIGNED   NOT NULL COMMENT 'references invoices.id',
    `invoice_number` VARCHAR(50)       NOT NULL COMMENT 'denormalized for fast track query',
    `actor_id`       INT UNSIGNED      NULL DEFAULT NULL COMMENT 'user who performed the action (references users.id)',
    `action`         VARCHAR(50)       NOT NULL COMMENT 'CREATED | UPDATED | STATUS_CHANGED | PRINTED | SOFT_DELETED | RESTORED',
    `description`    VARCHAR(255)      NOT NULL COMMENT 'Human readable summary of the event',
    `details_json`   LONGTEXT          NULL COMMENT 'Snapshot diff / JSON metadata',
    `ip_address`     VARCHAR(45)       NULL DEFAULT NULL,
    `user_agent`     TEXT              NULL DEFAULT NULL,
    `created_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_act_invoice_id`     (`invoice_id`),
    INDEX `idx_act_invoice_number` (`invoice_number`),
    INDEX `idx_act_actor_id`       (`actor_id`),
    INDEX `idx_act_created_at`     (`created_at`),
    INDEX `idx_act_action`         (`action`),
    CONSTRAINT `fk_invoice_activities_invoice`
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3: Seed `invoices.track` permission if permissions table exists
INSERT IGNORE INTO `permissions` (`module`, `action`, `slug`, `name`, `description`, `sort_order`)
SELECT 'invoices', 'track', 'invoices.track', 'Track Invoice Audit', 'Access Super Admin invoice lifecycle and deletion tracking dashboard', 295
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions'
LIMIT 1;

