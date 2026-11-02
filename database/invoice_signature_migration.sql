-- =============================================================
-- Invoice Signature Management Migration
-- Created: 2026-11-02
-- Compatible: MySQL 5.7+ / MariaDB 10.2+ / MySQL 8.0+
-- Safe to re-run: idempotent checks via information_schema
-- =============================================================

-- Step 1: Ensure `show_signature` column exists in `invoices`
SET @col_sig = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'invoices'
      AND COLUMN_NAME  = 'show_signature'
);

SET @sql_sig = IF(
    @col_sig = 0,
    'ALTER TABLE `invoices` ADD COLUMN `show_signature` TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''1 = show authorized signature on print/view, 0 = hide'' AFTER `notes`',
    'SELECT ''[SKIP] show_signature column already exists'' AS info'
);
PREPARE stmt_sig FROM @sql_sig;
EXECUTE stmt_sig;
DEALLOCATE PREPARE stmt_sig;

-- Step 2: Seed default settings keys if not already present
INSERT IGNORE INTO `invoice_settings` (`setting_key`, `setting_value`) VALUES
  ('invoice_signature', ''),
  ('invoice_signature_enabled', '1');

SELECT 'Invoice signature migration completed successfully.' AS status;

