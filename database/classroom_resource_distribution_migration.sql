-- ============================================================
-- Migration: Group Classroom Resource Distribution Engine
-- File: database/classroom_resource_distribution_migration.sql
-- Compatible with: MySQL 5.7+ / 8.0+ / MariaDB 10.2+
-- Project: LMS/EMS Dashboard System
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Create `classroom_resources` Table (Master Table)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classroom_resources` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`           VARCHAR(255) NOT NULL COMMENT 'Resource title or assignment name',
    `description`     TEXT NULL COMMENT 'Detailed instructions, notes, or overview',
    `resource_type`   ENUM('document', 'video', 'assignment', 'quiz', 'link') NOT NULL DEFAULT 'document',
    `file_path`       VARCHAR(255) NULL COMMENT 'Stored filename inside storage/uploads/classroom_resources/',
    `original_name`   VARCHAR(255) NULL COMMENT 'Original uploaded file name',
    `file_size`       BIGINT UNSIGNED NULL DEFAULT 0 COMMENT 'File size in bytes',
    `mime_type`       VARCHAR(100) NULL COMMENT 'MIME type verified by finfo',
    `external_url`    TEXT NULL COMMENT 'External video, Google Drive, or web reference URL',
    `quiz_id`         BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'FK -> quizzes.id (for quiz-type resources)',
    `due_date`        DATETIME NULL DEFAULT NULL COMMENT 'Submission deadline for assignments',
    `max_points`      DECIMAL(6,2) NULL DEFAULT NULL COMMENT 'Maximum grade/marks for assignments',
    `target_audience` ENUM('all', 'students', 'teachers') NOT NULL DEFAULT 'all',
    `status`          ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
    `created_by`      INT UNSIGNED NOT NULL COMMENT 'FK -> users.id (Teacher or Admin)',
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      DATETIME NULL DEFAULT NULL COMMENT 'Soft-deletion timestamp',
    PRIMARY KEY (`id`),
    INDEX `idx_cr_type_status` (`resource_type`, `status`),
    INDEX `idx_cr_creator` (`created_by`),
    INDEX `idx_cr_quiz_id` (`quiz_id`),
    INDEX `idx_cr_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_cr_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_cr_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master classroom resource and assignment registry';

-- ------------------------------------------------------------
-- 2. Create `classroom_resource_distributions` Pivot Table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classroom_resource_distributions` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resource_id`    INT UNSIGNED NOT NULL COMMENT 'FK -> classroom_resources.id',
    `classroom_id`   INT UNSIGNED NOT NULL COMMENT 'FK -> classrooms.id',
    `distributed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_resource_classroom` (`resource_id`, `classroom_id`),
    INDEX `idx_crd_classroom_id` (`classroom_id`),
    INDEX `idx_crd_resource_id` (`resource_id`),
    CONSTRAINT `fk_crd_resource` FOREIGN KEY (`resource_id`) REFERENCES `classroom_resources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_crd_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pivot table linking distributed resources to group classrooms';

-- ------------------------------------------------------------
-- 3. Create `classroom_assignment_submissions` Table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `classroom_assignment_submissions` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `resource_id`      INT UNSIGNED NOT NULL COMMENT 'FK -> classroom_resources.id (resource_type=assignment)',
    `student_id`       INT UNSIGNED NOT NULL COMMENT 'FK -> users.id (Student)',
    `notes`            TEXT NULL COMMENT 'Student comments or textual submission',
    `file_path`        VARCHAR(255) NULL COMMENT 'Stored filename inside storage/uploads/assignment_submissions/',
    `original_name`    VARCHAR(255) NULL COMMENT 'Original submitted file name',
    `submitted_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`           ENUM('submitted', 'graded', 'returned') NOT NULL DEFAULT 'submitted',
    `points_awarded`   DECIMAL(6,2) NULL DEFAULT NULL COMMENT 'Marks awarded by teacher/admin',
    `teacher_feedback` TEXT NULL COMMENT 'Evaluation comments or feedback',
    `graded_by`        INT UNSIGNED NULL DEFAULT NULL COMMENT 'FK -> users.id (Teacher/Admin evaluator)',
    `graded_at`        DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_student_assignment` (`resource_id`, `student_id`),
    INDEX `idx_cas_student` (`student_id`),
    INDEX `idx_cas_status` (`status`),
    INDEX `idx_cas_resource` (`resource_id`),
    CONSTRAINT `fk_cas_resource` FOREIGN KEY (`resource_id`) REFERENCES `classroom_resources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cas_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cas_grader` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Student assignment submissions and evaluations';

-- ------------------------------------------------------------
-- 4. Seed Granular RBAC Permissions for Classroom Resources
-- ------------------------------------------------------------
INSERT INTO `permissions` (`module`, `action`, `slug`, `name`, `description`, `sort_order`) VALUES
('classroom_resources', 'view', 'classroom_resources.view', 'View Classroom Resources', 'View study materials, documents, and assignments in classrooms', 125),
('classroom_resources', 'distribute', 'classroom_resources.distribute', 'Distribute Classroom Resources', 'Upload and bulk distribute resources to group classrooms', 126),
('classroom_resources', 'edit', 'classroom_resources.edit', 'Edit Classroom Resources', 'Modify distributed resources and assignment deadlines', 127),
('classroom_resources', 'delete', 'classroom_resources.delete', 'Delete Classroom Resources', 'Delete or archive classroom resources', 128),
('classroom_resources', 'grade', 'classroom_resources.grade', 'Grade Assignments', 'Evaluate and grade student assignment submissions', 129)
ON DUPLICATE KEY UPDATE 
    `name`        = VALUES(`name`),
    `description` = VALUES(`description`),
    `sort_order`  = VALUES(`sort_order`);

-- ------------------------------------------------------------
-- 5. Map Permissions to Administrative Roles
-- ------------------------------------------------------------
-- Give Super Admin ALL permissions for classroom_resources
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.slug = 'super_admin' 
  AND p.module = 'classroom_resources';

-- Give Admin all operational permissions for classroom_resources
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.slug = 'admin' 
  AND p.module = 'classroom_resources';

-- Give Moderator view and distribute permissions
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.slug = 'moderator' 
  AND p.module = 'classroom_resources'
  AND p.action IN ('view', 'distribute', 'grade');

SET FOREIGN_KEY_CHECKS = 1;
