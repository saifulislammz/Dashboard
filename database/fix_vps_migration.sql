-- =============================================================
-- VPS FIX MIGRATION — Run this on your VPS MySQL to fix all errors
-- Fixes:
--   1. "Failed to save invoice" → adds missing amount_paid, amount_due columns
--   2. HTTP 500 on view.php / print.php → creates invoice_currencies table
--   3. show_signature column (if missing)
-- Safe to re-run: all steps use IF NOT EXISTS / information_schema checks
-- Compatible: MySQL 5.7+ / MariaDB 10.2+
-- =============================================================

-- Select the correct database first
USE `portal`;

-- STEP 1: Add amount_paid column to invoices (if missing)
SET @col_paid = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'invoices'
      AND COLUMN_NAME  = 'amount_paid'
);
SET @sql_paid = IF(
    @col_paid = 0,
    'ALTER TABLE `invoices` ADD COLUMN `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `grand_total`',
    'SELECT 1'
);
PREPARE stmt_paid FROM @sql_paid;
EXECUTE stmt_paid;
DEALLOCATE PREPARE stmt_paid;

-- STEP 2: Add amount_due column to invoices (if missing)
SET @col_due = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'invoices'
      AND COLUMN_NAME  = 'amount_due'
);
SET @sql_due = IF(
    @col_due = 0,
    'ALTER TABLE `invoices` ADD COLUMN `amount_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `amount_paid`',
    'SELECT 1'
);
PREPARE stmt_due FROM @sql_due;
EXECUTE stmt_due;
DEALLOCATE PREPARE stmt_due;

-- STEP 3: Add show_signature column to invoices (if missing)
SET @col_sig = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'invoices'
      AND COLUMN_NAME  = 'show_signature'
);
SET @sql_sig = IF(
    @col_sig = 0,
    'ALTER TABLE `invoices` ADD COLUMN `show_signature` TINYINT(1) NOT NULL DEFAULT 1 AFTER `notes`',
    'SELECT 1'
);
PREPARE stmt_sig FROM @sql_sig;
EXECUTE stmt_sig;
DEALLOCATE PREPARE stmt_sig;

-- STEP 4: Backfill existing invoices (amount_due = grand_total for unpaid rows)
UPDATE `invoices`
SET `amount_due` = `grand_total`
WHERE `amount_paid` = 0.00
  AND `amount_due`  = 0.00
  AND `deleted_at`  IS NULL;

-- STEP 5: Add index on amount_due (if missing)
SET @idx_due = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'invoices'
      AND INDEX_NAME   = 'idx_amount_due'
);
SET @sql_idx = IF(
    @idx_due = 0,
    'ALTER TABLE `invoices` ADD INDEX `idx_amount_due` (`amount_due`)',
    'SELECT 1'
);
PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- STEP 6: Create invoice_currencies table (fixes HTTP 500 on view & print)
CREATE TABLE IF NOT EXISTS `invoice_currencies` (
  `code`   VARCHAR(10)  NOT NULL,
  `name`   VARCHAR(100) NOT NULL,
  `symbol` VARCHAR(10)  NOT NULL,
  `flag`   VARCHAR(10)  DEFAULT '',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- STEP 7: Insert default currencies (safe to re-run)
INSERT IGNORE INTO `invoice_currencies` (`code`, `name`, `symbol`, `flag`) VALUES
('BDT', 'Bangladeshi Taka',   '৳',    '🇧🇩'),
('USD', 'US Dollar',          '$',    '🇺🇸'),
('EUR', 'Euro',               '€',    '🇪🇺'),
('GBP', 'British Pound',      '£',    '🇬🇧'),
('AUD', 'Australian Dollar',  'A$',   '🇦🇺'),
('CAD', 'Canadian Dollar',    'C$',   '🇨🇦'),
('SGD', 'Singapore Dollar',   'S$',   '🇸🇬'),
('SAR', 'Saudi Riyal',        '﷼',   '🇸🇦'),
('AED', 'UAE Dirham',         'د.إ', '🇦🇪'),
('MYR', 'Malaysian Ringgit',  'RM',   '🇲🇾'),
('INR', 'Indian Rupee',       '₹',   '🇮🇳'),
('JPY', 'Japanese Yen',       '¥',    '🇯🇵'),
('CNY', 'Chinese Yuan',       '¥',    '🇨🇳'),
('KWD', 'Kuwaiti Dinar',      'KD',   '🇰🇼'),
('QAR', 'Qatari Riyal',       'QR',   '🇶🇦'),
('TRY', 'Turkish Lira',       '₺',   '🇹🇷');

-- VERIFICATION: show final column list for invoices table
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'invoices'
ORDER BY ORDINAL_POSITION;

SELECT 'VPS Fix Migration Complete!' AS status;
