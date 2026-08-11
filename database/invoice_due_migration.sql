-- =============================================================
-- Invoice DUE Amount Migration
-- Created: 2026-08-11
-- Compatible: MySQL 5.7+ / MariaDB 10.2+
-- Safe to re-run: uses information_schema checks instead of
-- IF NOT EXISTS (which requires MySQL 8.0.3+).
-- =============================================================

-- Step 1: Add `amount_paid` column (skip if already exists)
SET @col_paid = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'invoices'
      AND COLUMN_NAME  = 'amount_paid'
);

SET @sql_paid = IF(
    @col_paid = 0,
    'ALTER TABLE `invoices` ADD COLUMN `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT ''Amount collected from student at time of invoice'' AFTER `grand_total`',
    'SELECT ''[SKIP] amount_paid column already exists'' AS info'
);
PREPARE stmt_paid FROM @sql_paid;
EXECUTE stmt_paid;
DEALLOCATE PREPARE stmt_paid;

-- Step 2: Add `amount_due` column (skip if already exists)
SET @col_due = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'invoices'
      AND COLUMN_NAME  = 'amount_due'
);

SET @sql_due = IF(
    @col_due = 0,
    'ALTER TABLE `invoices` ADD COLUMN `amount_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT ''Remaining balance: grand_total - amount_paid'' AFTER `amount_paid`',
    'SELECT ''[SKIP] amount_due column already exists'' AS info'
);
PREPARE stmt_due FROM @sql_due;
EXECUTE stmt_due;
DEALLOCATE PREPARE stmt_due;

-- Step 3: Backfill existing rows
-- Old invoices had no payment recorded, so amount_due = grand_total
UPDATE `invoices`
SET `amount_due` = `grand_total`
WHERE `amount_paid` = 0.00
  AND `amount_due`  = 0.00
  AND `deleted_at`  IS NULL;

-- Step 4: Add index on amount_due (skip if already exists)
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
    'SELECT ''[SKIP] idx_amount_due index already exists'' AS info'
);
PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;
