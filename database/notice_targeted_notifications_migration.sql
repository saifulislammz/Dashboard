-- ============================================================
-- Migration: Notice Board Targeted Notification System
-- File: database/notice_targeted_notifications_migration.sql
-- Compatible with: MySQL 5.7+ / 8.0+ / MariaDB 10.2+
-- Project: LMS/EMS Dashboard System
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Add `target_type` column and composite indexes to `notices`
-- ------------------------------------------------------------
SET @dbname = DATABASE();
SET @tablename = "notices";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = "target_type"
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `notices` ADD COLUMN `target_type` ENUM('all', 'targeted') NOT NULL DEFAULT 'targeted' AFTER `target_audience`, ADD INDEX `idx_notices_status_type` (`status`, `target_type`, `created_at`)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Ensure `idx_notices_status_audience` exists for accelerated legacy fallback queries
SET @prepIdxAudience = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND INDEX_NAME = "idx_notices_status_audience"
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `notices` ADD INDEX `idx_notices_status_audience` (`status`, `target_audience`, `created_at`)"
));
PREPARE alterIdxAudience FROM @prepIdxAudience;
EXECUTE alterIdxAudience;
DEALLOCATE PREPARE alterIdxAudience;

-- ------------------------------------------------------------
-- 2. Backfill existing notices
-- ------------------------------------------------------------
UPDATE `notices` SET `target_type` = 'targeted' WHERE `target_type` IS NULL;

-- ------------------------------------------------------------
-- 3. Create `notice_targets` table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notice_targets` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `notice_id`    INT(11) NOT NULL COMMENT 'FK -> notices.id',
    `target_type`  ENUM('role', 'classroom', 'user') NOT NULL COMMENT 'Target category',
    `target_id`    INT UNSIGNED NOT NULL COMMENT 'Role bitmask, classroom_id, or user_id',
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_notice_target` (`notice_id`, `target_type`, `target_id`),
    KEY `idx_target_lookup` (`target_type`, `target_id`, `notice_id`),
    KEY `idx_target_notice_id` (`notice_id`),
    CONSTRAINT `fk_nt_notice` FOREIGN KEY (`notice_id`) REFERENCES `notices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Granular targeted notice mappings';

SET FOREIGN_KEY_CHECKS = 1;

