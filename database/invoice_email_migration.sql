-- =============================================================
-- Invoice Email System Migration
-- Created: 2026-11-02
-- Compatible: MySQL 5.7+ / MariaDB 10.2+ / MySQL 8.0+
-- Safe to re-run: Idempotent statements with IF NOT EXISTS & INSERT IGNORE
-- =============================================================

SET NAMES utf8mb4;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- Step 1: Create Table `invoice_email_logs`
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoice_email_logs` (
    `id`              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `invoice_id`      BIGINT UNSIGNED   NOT NULL COMMENT 'references invoices.id',
    `invoice_number`  VARCHAR(50)       NOT NULL COMMENT 'denormalized for rapid lookup',
    `recipient_email` VARCHAR(255)      NOT NULL COMMENT 'target email address',
    `trigger_type`    ENUM('manual', 'automatic') NOT NULL DEFAULT 'manual',
    `status`          ENUM('sent', 'failed')      NOT NULL DEFAULT 'sent',
    `sent_by`         INT UNSIGNED      NULL DEFAULT NULL COMMENT 'user who initiated (NULL if automatic)',
    `error_message`   TEXT              NULL DEFAULT NULL COMMENT 'SMTP or runtime error if failed',
    `sent_at`         DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_iel_invoice_id`     (`invoice_id`),
    INDEX `idx_iel_invoice_number` (`invoice_number`),
    INDEX `idx_iel_recipient`      (`recipient_email`),
    INDEX `idx_iel_status`         (`status`),
    INDEX `idx_iel_trigger_type`   (`trigger_type`),
    INDEX `idx_iel_sent_at`        (`sent_at`),
    CONSTRAINT `fk_iel_invoice`
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Step 2: Seed Default Invoice Email Settings into `invoice_settings`
-- -------------------------------------------------------------
INSERT IGNORE INTO `invoice_settings` (`setting_key`, `setting_value`) VALUES
  ('invoice_auto_email_enabled', '0'),
  ('invoice_email_subject',      'Invoice #{invoice_number} from {institution_name}'),
  ('invoice_email_bcc',          ''),
  ('invoice_email_custom_notes', 'Thank you for choosing us. Please find your detailed invoice attached as a PDF document.');

-- -------------------------------------------------------------
-- Step 3: Seed `invoices.send_email` Permission
-- -------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`module`, `action`, `slug`, `name`, `description`, `sort_order`)
SELECT 'invoices', 'send_email', 'invoices.send_email', 'Send Invoice Email', 'Send invoice PDF via email manually to students and clients', 285
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions'
LIMIT 1;

-- -------------------------------------------------------------
-- Step 4: Map Permission to Roles (super_admin, admin, accountant)
-- -------------------------------------------------------------
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.slug IN ('super_admin', 'admin', 'accountant')
  AND p.slug = 'invoices.send_email';

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

