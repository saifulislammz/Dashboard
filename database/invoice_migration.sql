-- =============================================================
-- Invoice System Migration
-- Created: 2026-06-29
-- Tables: invoices, invoice_items, invoice_settings
-- =============================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;

-- -------------------------------------------------------------
-- Table: invoices (master invoice record, soft-deletable)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoices` (
  `id`              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `invoice_number`  VARCHAR(50)       NOT NULL COMMENT 'e.g. INV2506040001',
  `student_name`    VARCHAR(150)      NOT NULL,
  `student_email`   VARCHAR(249)      DEFAULT NULL,
  `student_phone`   VARCHAR(30)       DEFAULT NULL,
  `student_country` VARCHAR(100)      DEFAULT NULL,
  `currency`        VARCHAR(10)       NOT NULL DEFAULT 'BDT',
  `subtotal`        DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
  `discount`        DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
  `vat_percent`     DECIMAL(5,2)      NOT NULL DEFAULT 0.00,
  `vat_amount`      DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
  `grand_total`     DECIMAL(12,2)     NOT NULL DEFAULT 0.00,
  `status`          VARCHAR(20)       NOT NULL DEFAULT 'unpaid' COMMENT 'draft|unpaid|paid',
  `invoice_date`    DATE              NOT NULL,
  `due_date`        DATE              DEFAULT NULL,
  `notes`           TEXT              DEFAULT NULL,
  `created_by`      INT UNSIGNED      NOT NULL COMMENT 'references users.id (admin)',
  `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      DATETIME          DEFAULT NULL COMMENT 'NULL = active, set = soft-deleted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoice_number` (`invoice_number`),
  KEY `idx_status`             (`status`),
  KEY `idx_currency`           (`currency`),
  KEY `idx_created_by`         (`created_by`),
  KEY `idx_invoice_date`       (`invoice_date`),
  KEY `idx_deleted_at`         (`deleted_at`),
  KEY `idx_status_created_at`  (`status`, `created_at`),
  KEY `idx_currency_deleted`   (`currency`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table: invoice_items (line items per invoice, CASCADE delete)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id`          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `invoice_id`  BIGINT UNSIGNED  NOT NULL,
  `item_name`   VARCHAR(255)     NOT NULL,
  `description` TEXT             DEFAULT NULL,
  `quantity`    DECIMAL(10,2)    NOT NULL DEFAULT 1.00,
  `unit_price`  DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  `amount`      DECIMAL(12,2)    NOT NULL DEFAULT 0.00 COMMENT 'quantity × unit_price (pre-computed)',
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_id` (`invoice_id`),
  CONSTRAINT `fk_invoice_items_invoice`
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table: invoice_settings (key-value config store)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoice_settings` (
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT         DEFAULT NULL,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings (INSERT IGNORE = safe to re-run)
INSERT IGNORE INTO `invoice_settings` (`setting_key`, `setting_value`) VALUES
  ('invoice_prefix',        'INV'),
  ('invoice_number_format', '{PREFIX}{YEAR}{MONTH}{SEQ4}'),
  ('institution_name',      'Rahe Nazat Institute'),
  ('institution_tagline',   'Excellence in Education'),
  ('institution_address',   ''),
  ('institution_phone',     ''),
  ('institution_email',     ''),
  ('institution_logo',      ''),
  ('invoice_footer_note',   'Thank you for your payment. Please retain this invoice for your records.');

/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
