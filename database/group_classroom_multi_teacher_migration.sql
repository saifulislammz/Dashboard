-- ============================================================
-- Migration: Group Classroom & Multi-Teacher Management
-- File: database/group_classroom_multi_teacher_migration.sql
-- Compatible with: MySQL 5.7+ / 8.0+ / MariaDB 10.2+
-- Project: LMS/EMS Dashboard System
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Create `classroom_teachers` table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classroom_teachers` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `classroom_id` INT UNSIGNED NOT NULL COMMENT 'FK -> classrooms.id',
    `user_id`      INT UNSIGNED NOT NULL COMMENT 'FK -> users.id (Teacher)',
    `is_primary`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = Primary Teacher (Zoom Host), 0 = Co-teacher',
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_classroom_teacher` (`classroom_id`, `user_id`),
    KEY `idx_ct_user_id` (`user_id`),
    KEY `idx_ct_classroom_id` (`classroom_id`),
    KEY `idx_ct_primary` (`classroom_id`, `is_primary`),
    CONSTRAINT `fk_cteach_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cteach_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Classroom multi-teacher mappings';

-- ------------------------------------------------------------
-- 2. Create `classroom_students` table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classroom_students` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `classroom_id` INT UNSIGNED NOT NULL COMMENT 'FK -> classrooms.id',
    `user_id`      INT UNSIGNED NOT NULL COMMENT 'FK -> users.id (Student)',
    `status`       ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `enrolled_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_classroom_student` (`classroom_id`, `user_id`),
    KEY `idx_cs_user_id` (`user_id`),
    KEY `idx_cs_classroom_id` (`classroom_id`),
    KEY `idx_cs_status` (`status`),
    KEY `idx_cs_user_status` (`user_id`, `status`),
    CONSTRAINT `fk_cstud_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cstud_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Classroom multi-student enrollments';

-- ------------------------------------------------------------
-- 3. Modify `classrooms` columns for Backward Compatibility
-- ------------------------------------------------------------
ALTER TABLE `classrooms`
    MODIFY COLUMN `teacher_id` INT UNSIGNED NULL DEFAULT NULL,
    MODIFY COLUMN `student_id` INT UNSIGNED NULL DEFAULT NULL;

-- ------------------------------------------------------------
-- 4. Backfill existing 1-to-1 classrooms (Zero Data Loss)
-- ------------------------------------------------------------
INSERT IGNORE INTO `classroom_teachers` (`classroom_id`, `user_id`, `is_primary`)
SELECT `id`, `teacher_id`, 1 
FROM `classrooms` 
WHERE `teacher_id` IS NOT NULL AND `teacher_id` > 0;

INSERT IGNORE INTO `classroom_students` (`classroom_id`, `user_id`, `status`)
SELECT `id`, `student_id`, 'active' 
FROM `classrooms` 
WHERE `student_id` IS NOT NULL AND `student_id` > 0;

SET FOREIGN_KEY_CHECKS = 1;
