-- ============================================================
-- Role & Permission Management (RBAC) System — Migration
-- File: database/rbac_migration.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Alter / Create `roles` table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_roles_deleted_at` (`deleted_at`),
    INDEX `idx_roles_is_system` (`is_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure missing columns exist in `roles` if table pre-existed
SET @dbname = DATABASE();
SET @tablename = "roles";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = "is_system"
  ) > 0,
  "SELECT 1",
  "ALTER TABLE roles ADD COLUMN is_system TINYINT(1) NOT NULL DEFAULT 0 AFTER description, ADD INDEX idx_roles_is_system (is_system)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = "deleted_at"
  ) > 0,
  "SELECT 1",
  "ALTER TABLE roles ADD COLUMN deleted_at DATETIME NULL AFTER updated_at, ADD INDEX idx_roles_deleted_at (deleted_at)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ------------------------------------------------------------
-- 2. Alter / Create `permissions` table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `module` VARCHAR(50) NOT NULL DEFAULT 'general',
    `action` VARCHAR(50) NOT NULL DEFAULT 'view',
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_permissions_module` (`module`),
    INDEX `idx_permissions_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure missing columns exist in `permissions` if table pre-existed
SET @tablename = "permissions";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = "module"
  ) > 0,
  "SELECT 1",
  "ALTER TABLE permissions ADD COLUMN module VARCHAR(50) NOT NULL DEFAULT 'general' AFTER id, ADD INDEX idx_permissions_module (module)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = "action"
  ) > 0,
  "SELECT 1",
  "ALTER TABLE permissions ADD COLUMN action VARCHAR(50) NOT NULL DEFAULT 'view' AFTER module"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = "sort_order"
  ) > 0,
  "SELECT 1",
  "ALTER TABLE permissions ADD COLUMN sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER description, ADD INDEX idx_permissions_sort (sort_order)"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ------------------------------------------------------------
-- 3. `role_permissions` junction table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`, `permission_id`),
    INDEX `idx_rp_permission` (`permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. `user_roles` junction table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `assigned_by` INT UNSIGNED NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    INDEX `idx_ur_role` (`role_id`),
    INDEX `idx_ur_assigned_by` (`assigned_by`),
    CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure missing columns exist in `user_roles` if table pre-existed
SET @tablename = "user_roles";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = "assigned_by"
  ) > 0,
  "SELECT 1",
  "ALTER TABLE user_roles ADD COLUMN assigned_by INT UNSIGNED NULL AFTER role_id, ADD COLUMN assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER assigned_by"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ------------------------------------------------------------
-- 5. `rbac_audit_logs` table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rbac_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_id` INT UNSIGNED NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `target_type` VARCHAR(50) NOT NULL,
    `target_id` INT UNSIGNED NULL,
    `details_json` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_audit_actor` (`actor_id`),
    INDEX `idx_audit_event_time` (`event_type`, `created_at`),
    INDEX `idx_audit_target` (`target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. Seed System Permissions (Insert or Ignore / Update)
-- ------------------------------------------------------------
INSERT INTO `permissions` (`module`, `action`, `slug`, `name`, `description`, `sort_order`) VALUES
-- 1. Dashboard Analytics
('analytics', 'view', 'analytics.view', 'View Dashboard Analytics', 'Access to general dashboard statistics and counters', 10),
('analytics', 'financial', 'analytics.financial', 'View Financial Analytics', 'Access to revenue, earnings and financial summaries', 20),

-- 2. User Management (Teachers & Students)
('users', 'view', 'users.view', 'View Users', 'View teachers and students lists and profiles', 30),
('users', 'create', 'users.create', 'Create Users', 'Register new teachers or students', 40),
('users', 'edit', 'users.edit', 'Edit Users', 'Update teacher or student profiles and credentials', 50),
('users', 'delete', 'users.delete', 'Delete Users', 'Permanently remove teacher or student accounts', 60),
('users', 'ban', 'users.ban', 'Ban/Suspend Users', 'Change status or suspend user accounts', 70),

-- 3. Classroom Management
('classrooms', 'view', 'classrooms.view', 'View Classrooms', 'Browse and view classroom information', 80),
('classrooms', 'create', 'classrooms.create', 'Create Classroom', 'Create new classrooms and assign teachers/students', 90),
('classrooms', 'edit', 'classrooms.edit', 'Edit Classroom', 'Update classroom details and assignments', 100),
('classrooms', 'delete', 'classrooms.delete', 'Delete Classroom', 'Remove existing classrooms', 110),

-- 4. Notice Board
('notices', 'view', 'notices.view', 'View Notices', 'Read announcements and notice board updates', 120),
('notices', 'create', 'notices.create', 'Create Notice', 'Publish new notices to students or teachers', 130),
('notices', 'edit', 'notices.edit', 'Edit Notice', 'Modify published notice content and target audience', 140),
('notices', 'delete', 'notices.delete', 'Delete Notice', 'Delete announcements from notice board', 150),

-- 5. Sessions & Meetings
('sessions', 'view', 'sessions.view', 'View Sessions', 'View live and scheduled online class meetings', 160),
('sessions', 'create', 'sessions.create', 'Create Session', 'Schedule class meetings and generate Meet/Zoom links', 170),
('sessions', 'edit', 'sessions.edit', 'Edit Session', 'Reschedule or change meeting configurations', 180),
('sessions', 'cancel', 'sessions.cancel', 'Cancel Session', 'Cancel scheduled meetings and notify users', 190),
('sessions', 'retry', 'sessions.retry', 'Retry Meeting Jobs', 'Retry failed video provider API synchronization', 200),

-- 6. Attendance System
('attendance', 'view', 'attendance.view', 'View Attendance', 'View session attendance reports and student presence', 210),
('attendance', 'export', 'attendance.export', 'Export Attendance', 'Download attendance reports in CSV/Excel format', 220),
('attendance', 'manage', 'attendance.manage', 'Manage Attendance', 'Manually override or record student attendance', 230),

-- 7. Invoice Management
('invoices', 'view', 'invoices.view', 'View Invoices', 'Access invoice dashboard, history and billing list', 240),
('invoices', 'create', 'invoices.create', 'Create Invoice', 'Generate new client/student billing invoices', 250),
('invoices', 'edit', 'invoices.edit', 'Edit Invoice', 'Update invoice items, discounts and payment status', 260),
('invoices', 'delete', 'invoices.delete', 'Delete Invoice', 'Soft delete invoices from billing registry', 270),
('invoices', 'print', 'invoices.print', 'Print Invoices', 'Print invoice documents or save as PDF', 280),
('invoices', 'settings', 'invoices.settings', 'Invoice Settings', 'Configure currency, VAT, payment details and branding', 290),

-- 8. Quiz & Exam System
('quiz', 'view', 'quiz.view', 'View Quizzes', 'View published quizzes, question banks and attempts', 300),
('quiz', 'create', 'quiz.create', 'Create Quiz', 'Create quizzes with multiple question formats', 310),
('quiz', 'edit', 'quiz.edit', 'Edit Quiz', 'Edit quiz settings, questions and scoring parameters', 320),
('quiz', 'delete', 'quiz.delete', 'Delete Quiz', 'Delete quizzes and associated attempts', 330),
('quiz', 'review', 'quiz.review', 'Review Quiz Submissions', 'Evaluate student audio/voice and written answers', 340),

-- 9. Settings & API Integrations
('settings', 'meetings', 'settings.meetings', 'Meeting Integrations', 'Connect Google Meet OAuth and Zoom API credentials', 350),
('settings', 'general', 'settings.general', 'General Settings', 'Configure portal information and global parameters', 360),

-- 10. Role & Permission Management
('roles', 'view', 'roles.view', 'View Roles & Staff', 'View administrator and staff list, roles and permissions', 370),
('roles', 'create', 'roles.create', 'Create Role', 'Create custom staff roles with granular permissions', 380),
('roles', 'edit', 'roles.edit', 'Edit Role', 'Modify role names and toggle specific permissions', 390),
('roles', 'delete', 'roles.delete', 'Delete Role', 'Remove custom roles that are not assigned to users', 400),
('roles', 'assign', 'roles.assign', 'Assign Staff Roles', 'Create admin/moderator accounts and assign roles', 410)

ON DUPLICATE KEY UPDATE 
    `module` = VALUES(`module`),
    `action` = VALUES(`action`),
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `sort_order` = VALUES(`sort_order`);

-- ------------------------------------------------------------
-- 7. Seed Default Roles
-- ------------------------------------------------------------
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
('Super Admin', 'super_admin', 'Supreme administrator with unrestricted access to all modules and configurations', 1),
('Admin', 'admin', 'Standard administrator with full academic and operational management', 1),
('Moderator', 'moderator', 'Staff member managing users, classrooms, notices and attendance', 0),
('Accountant', 'accountant', 'Financial staff managing invoices, billing configurations and currency', 0)
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `is_system` = VALUES(`is_system`);

-- ------------------------------------------------------------
-- 8. Map Initial Permissions to Default Roles
-- ------------------------------------------------------------
-- Give Super Admin ALL permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.slug = 'super_admin';

-- Give Admin all operational permissions (except role creation/deletion unless super admin)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.slug = 'admin'
  AND p.slug NOT IN ('roles.delete');

-- Give Moderator operational permissions for Users, Classrooms, Notices, Sessions, Attendance
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.slug = 'moderator'
  AND p.module IN ('users', 'classrooms', 'notices', 'sessions', 'attendance', 'analytics')
  AND p.action IN ('view', 'create', 'edit', 'export');

-- Give Accountant invoice and financial permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.slug = 'accountant'
  AND (p.module = 'invoices' OR p.slug IN ('analytics.view', 'analytics.financial'));

-- ------------------------------------------------------------
-- 9. Assign Primary Admin (User 1) to Super Admin Role
-- ------------------------------------------------------------
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`, `assigned_by`)
SELECT u.id, r.id, u.id
FROM `users` u
JOIN `roles` r ON r.slug = 'super_admin'
WHERE u.id = 1;

-- Also update user 1 to have Delight Auth SUPER_ADMIN bitmask (262144 | 1 = 262145)
UPDATE `users` SET `roles_mask` = (`roles_mask` | 262144 | 1) WHERE `id` = 1;

SET FOREIGN_KEY_CHECKS = 1;

