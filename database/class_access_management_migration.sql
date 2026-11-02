-- ============================================================
-- Class Access Management (Observer Access System) Migration
-- File: database/class_access_management_migration.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Create `classroom_observers` table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classroom_observers` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `classroom_id` INT UNSIGNED NOT NULL COMMENT 'FK -> classrooms.id',
    `user_id`      INT UNSIGNED NOT NULL COMMENT 'FK -> users.id (Admin/Moderator)',
    `assigned_by`  INT UNSIGNED NOT NULL COMMENT 'FK -> users.id (Super Admin)',
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_class_observer` (`classroom_id`, `user_id`),
    KEY `idx_co_user_id` (`user_id`),
    KEY `idx_co_classroom_id` (`classroom_id`),
    CONSTRAINT `fk_co_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_co_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_co_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Classroom-level observer access assignments';

-- ------------------------------------------------------------
-- 2. Create `session_observers` table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `session_observers` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id`  INT UNSIGNED NOT NULL COMMENT 'FK -> class_sessions.id',
    `user_id`     INT UNSIGNED NOT NULL COMMENT 'FK -> users.id (Admin/Moderator)',
    `assigned_by` INT UNSIGNED NOT NULL COMMENT 'FK -> users.id (Super Admin)',
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_session_observer` (`session_id`, `user_id`),
    KEY `idx_so_user_id` (`user_id`),
    KEY `idx_so_session_id` (`session_id`),
    CONSTRAINT `fk_so_session` FOREIGN KEY (`session_id`) REFERENCES `class_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_so_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_so_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Session-level observer access assignments';

-- ------------------------------------------------------------
-- 3. Enhance `session_attendance` table
-- Add 'observer' role to ENUM (instant non-blocking operation)
-- ------------------------------------------------------------
ALTER TABLE `session_attendance`
    MODIFY COLUMN `role` ENUM('teacher', 'student', 'observer') NOT NULL DEFAULT 'student';

-- ------------------------------------------------------------
-- 4. Seed Granular Permissions for Observer Access
-- ------------------------------------------------------------
INSERT INTO `permissions` (`module`, `action`, `slug`, `name`, `description`, `sort_order`) VALUES
('classrooms', 'assign_observer', 'classrooms.assign_observer', 'Assign Class Observers', 'Assign Admin or Moderator staff accounts as observers to classrooms and live sessions', 105),
('classrooms', 'view_all', 'classrooms.view_all', 'View All Classrooms', 'Unrestricted view of all classrooms regardless of individual observer assignments', 85)
ON DUPLICATE KEY UPDATE 
    `module` = VALUES(`module`),
    `action` = VALUES(`action`),
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `sort_order` = VALUES(`sort_order`);

-- Assign both permissions to Super Admin
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.slug = 'super_admin' 
  AND p.slug IN ('classrooms.assign_observer', 'classrooms.view_all');

-- Assign view_all to Standard Admin (so admin views all, while moderator is scoped to assigned classes)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.slug = 'admin' 
  AND p.slug = 'classrooms.view_all';

SET FOREIGN_KEY_CHECKS = 1;

