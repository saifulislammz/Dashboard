-- =============================================================
-- Student & Teacher Profile Management Migration
-- Compatible: MySQL 5.7+ / MariaDB 10.2+ / MySQL 8.0+
-- Tables: user_profiles
-- Safe to re-run: Idempotent DDL and index additions
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. Table: user_profiles (1:1 with users table)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED NOT NULL,
    `phone`         VARCHAR(30) NULL DEFAULT NULL,
    `date_of_birth` DATE NULL DEFAULT NULL,
    `gender`        ENUM('male', 'female', 'other') NULL DEFAULT NULL,
    `country`       VARCHAR(100) NULL DEFAULT NULL,
    `city`          VARCHAR(100) NULL DEFAULT NULL,
    `address`       TEXT NULL DEFAULT NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_profiles_user_id` (`user_id`),
    INDEX `idx_user_profiles_phone` (`phone`),
    INDEX `idx_user_profiles_country` (`country`),
    INDEX `idx_user_profiles_gender` (`gender`),
    INDEX `idx_user_profiles_dob` (`date_of_birth`),
    CONSTRAINT `fk_user_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Auxiliary table storing extended profile attributes for students and teachers';

-- -------------------------------------------------------------
-- 2. Optimize `invoices` table with index on `student_email`
-- -------------------------------------------------------------
SET @exist_idx = (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'invoices' 
      AND INDEX_NAME = 'idx_invoices_student_email'
);
SET @sql_idx = IF(@exist_idx = 0, 'ALTER TABLE `invoices` ADD INDEX `idx_invoices_student_email` (`student_email`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- -------------------------------------------------------------
-- 3. Seed `users.export_pdf` Permission (if permissions table exists)
-- -------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`module`, `action`, `slug`, `name`, `description`, `sort_order`)
SELECT 'users', 'export_pdf', 'users.export_pdf', 'Export Profile PDF', 'Generate and download user profile PDF reports', 75
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'permissions'
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;

