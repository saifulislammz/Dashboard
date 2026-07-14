-- PHP-Auth (https://github.com/delight-im/PHP-Auth)
-- Copyright (c) delight.im (https://www.delight.im/)
-- Licensed under the MIT License (https://opensource.org/licenses/MIT)

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '0',
  `verified` tinyint unsigned NOT NULL DEFAULT '0',
  `resettable` tinyint unsigned NOT NULL DEFAULT '1',
  `roles_mask` int unsigned NOT NULL DEFAULT '0',
  `registered` int unsigned NOT NULL,
  `last_login` int unsigned DEFAULT NULL,
  `force_logout` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users_2fa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `mechanism` tinyint unsigned NOT NULL,
  `seed` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` int unsigned NOT NULL,
  `expires_at` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_mechanism` (`user_id`,`mechanism`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users_audit_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `event_at` int unsigned NOT NULL,
  `event_type` varchar(128) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `admin_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(49) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details_json` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_at` (`event_at`),
  KEY `user_id_event_at` (`user_id`,`event_at`),
  KEY `user_id_event_type_event_at` (`user_id`,`event_type`,`event_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users_confirmations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `selector` varchar(16) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `email_expires` (`email`,`expires`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users_otps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `mechanism` tinyint unsigned NOT NULL,
  `single_factor` tinyint unsigned NOT NULL DEFAULT '0',
  `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires_at` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_mechanism` (`user_id`,`mechanism`),
  KEY `selector_user_id` (`selector`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users_remembered` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user` int unsigned NOT NULL,
  `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users_resets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user` int unsigned NOT NULL,
  `selector` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `user_expires` (`user`,`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users_throttling` (
  `bucket` varchar(44) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `tokens` float NOT NULL,
  `replenished_at` int unsigned NOT NULL,
  `expires_at` int unsigned NOT NULL,
  PRIMARY KEY (`bucket`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- Future Ready Permission System Architecture
-- Similar to WordPress role/permission mapping

CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `classrooms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `class_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_id` int unsigned NOT NULL,
  `student_id` int unsigned NOT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_by` int unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_code` (`class_code`),
  KEY `teacher_id` (`teacher_id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `class_name` (`class_name`),
  KEY `class_title` (`class_title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ============================================================
-- MEETING INTEGRATION MODULE — Database Schema
-- Compatible with: MySQL 8+ / MariaDB
-- Project: Pure PHP LMS/EMS (phprulse stack)
-- Author: Meeting Module v1.0
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ============================================================
-- TABLE 1: provider_accounts
-- Stores OAuth tokens & credentials for Google Meet / Zoom
-- One record per connected provider (institute-level)
-- ============================================================
CREATE TABLE IF NOT EXISTS `provider_accounts` (
    `id`                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `provider`             ENUM('google_meet', 'zoom') NOT NULL,
    -- Google: the connected Google account email
    -- Zoom: the Zoom account email / account ID
    `account_email`        VARCHAR(249)    COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `account_id`           VARCHAR(128)    COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Zoom Account ID / Google sub',
    -- Encrypted tokens (AES-256 via openssl_encrypt)
    `access_token`         TEXT            COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted access token',
    `refresh_token`        TEXT            COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted refresh token',
    `token_expires_at`     INT UNSIGNED    DEFAULT NULL COMMENT 'Unix timestamp of token expiry',
    -- App credentials (encrypted) — Client ID / Secret stored here OR in .env
    -- Storing here allows per-provider DB-driven config from settings page
    `client_id`            TEXT            COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted Client ID',
    `client_secret`        TEXT            COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted Client Secret',
    -- Zoom specific
    `zoom_account_id`      VARCHAR(128)    DEFAULT NULL COMMENT 'Zoom Server-to-Server Account ID',
    -- Status
    `is_connected`         TINYINT(1)      NOT NULL DEFAULT 0,
    `connected_at`         DATETIME        DEFAULT NULL,
    `connected_by`         INT UNSIGNED    DEFAULT NULL COMMENT 'Admin user_id who connected',
    `last_token_refresh`   DATETIME        DEFAULT NULL,
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_provider` (`provider`),
    KEY `idx_provider_connected` (`provider`, `is_connected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stores OAuth tokens and credentials for meeting providers';


-- ============================================================
-- TABLE 2: meeting_settings
-- Global settings for the meeting module (key-value store)
-- ============================================================
CREATE TABLE IF NOT EXISTS `meeting_settings` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100)  NOT NULL COLLATE utf8mb4_unicode_ci,
    `setting_val` TEXT          COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Key-value store for global meeting module settings';

-- Default settings seed
INSERT IGNORE INTO `meeting_settings` (`setting_key`, `setting_val`) VALUES
('default_timezone',          'Asia/Dhaka'),
('join_open_minutes_before',  '10'),
('reminder_enabled',          '1'),
('reminder_minutes_before',   '30'),
('expose_direct_link',        '0'),  -- 0 = secure redirect only
('recording_sync_enabled',    '0'),
('attendance_sync_enabled',   '0'),
('default_provider',          'zoom');


-- ============================================================
-- TABLE 3: class_sessions
-- One record per scheduled class session
-- ONE SESSION = ONE MEETING (core rule)
-- ============================================================
CREATE TABLE IF NOT EXISTS `class_sessions` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `classroom_id`      INT UNSIGNED    NOT NULL COMMENT 'FK → classrooms.id',
    -- Session schedule
    `session_date`      DATE            NOT NULL COMMENT 'Date of the session (YYYY-MM-DD)',
    `start_time`        TIME            NOT NULL COMMENT 'Start time (HH:MM:SS)',
    `end_time`          TIME            NOT NULL COMMENT 'End time (HH:MM:SS)',
    `timezone`          VARCHAR(64)     NOT NULL DEFAULT 'Asia/Dhaka',
    -- Session info
    `topic`             VARCHAR(255)    COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Session topic / title',
    `agenda`            TEXT            COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Optional agenda notes',
    `session_number`    SMALLINT UNSIGNED DEFAULT NULL COMMENT 'e.g. Session #3 of 15',
    -- Provider
    `provider`          ENUM('google_meet', 'zoom') NOT NULL DEFAULT 'zoom',
    -- Status lifecycle: scheduled → meeting_pending → active → completed | cancelled | failed
    `status`            ENUM('scheduled','meeting_pending','active','completed','cancelled','failed')
                        NOT NULL DEFAULT 'scheduled',
    -- Batch tracking (sessions created together in one bulk action)
    `job_id`            INT UNSIGNED    DEFAULT NULL COMMENT 'FK → meeting_generation_jobs.id',
    -- Audit
    `created_by`        INT UNSIGNED    NOT NULL COMMENT 'FK → users.id (admin)',
    `cancelled_by`      INT UNSIGNED    DEFAULT NULL,
    `cancelled_at`      DATETIME        DEFAULT NULL,
    `cancel_reason`     VARCHAR(255)    DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_classroom_date`   (`classroom_id`, `session_date`),
    KEY `idx_classroom_status` (`classroom_id`, `status`),
    KEY `idx_session_date`     (`session_date`),
    KEY `idx_provider`         (`provider`),
    KEY `idx_job_id`           (`job_id`),
    KEY `idx_status`           (`status`),
    KEY `idx_session_date_time` (`session_date`, `start_time`),
    -- Prevent exact duplicate sessions for same class, date, start time, provider
    UNIQUE KEY `uq_class_session` (`classroom_id`, `session_date`, `start_time`, `provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='One record per scheduled live class session';


-- ============================================================
-- TABLE 4: session_meetings
-- Stores the provider meeting link & full API response
-- One-to-one with class_sessions
-- ============================================================
CREATE TABLE IF NOT EXISTS `session_meetings` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `session_id`            INT UNSIGNED    NOT NULL COMMENT 'FK → class_sessions.id',
    `provider`              ENUM('google_meet', 'zoom') NOT NULL,
    -- Provider-assigned IDs
    `provider_meeting_id`   VARCHAR(255)    DEFAULT NULL COMMENT 'Zoom meeting ID or Google Calendar event ID',
    `provider_event_id`     VARCHAR(255)    DEFAULT NULL COMMENT 'Google Calendar event ID',
    -- Meeting URLs
    `join_url`              TEXT            DEFAULT NULL COMMENT 'Participant join URL',
    `start_url`             TEXT            DEFAULT NULL COMMENT 'Host start URL (Zoom) / NULL for Google',
    `meet_link`             VARCHAR(512)    DEFAULT NULL COMMENT 'Google Meet link (meet.google.com/xxx)',
    `passcode`              VARCHAR(64)     DEFAULT NULL COMMENT 'Zoom passcode if set',
    -- Generation status
    `generation_status`     ENUM('pending','success','failed','cancelled') NOT NULL DEFAULT 'pending',
    `generation_attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `last_attempt_at`       DATETIME        DEFAULT NULL,
    `error_message`         VARCHAR(512)    DEFAULT NULL,
    -- Raw API response (for debugging & future extensions)
    `provider_response_raw` JSON            DEFAULT NULL COMMENT 'Full provider API response',
    -- Future fields (populated later via webhook/sync)
    `recording_url`         TEXT            DEFAULT NULL,
    `recording_synced_at`   DATETIME        DEFAULT NULL,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_session_id`          (`session_id`),
    UNIQUE KEY `uq_provider_meeting_id` (`provider`, `provider_meeting_id`),
    KEY `idx_generation_status`         (`generation_status`),
    KEY `idx_provider`                  (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stores meeting link and full provider API response per session';


-- ============================================================
-- TABLE 5: meeting_generation_jobs
-- Tracks a bulk session generation action
-- e.g. "Admin generated 15 sessions on 25 Jun 2026"
-- ============================================================
CREATE TABLE IF NOT EXISTS `meeting_generation_jobs` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `classroom_id`  INT UNSIGNED    NOT NULL,
    `provider`      ENUM('google_meet', 'zoom') NOT NULL,
    `total_sessions` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `processed`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `succeeded`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `failed`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    -- Job lifecycle
    `status`        ENUM('queued','processing','completed','partial','failed')
                    NOT NULL DEFAULT 'queued',
    `started_at`    DATETIME        DEFAULT NULL,
    `completed_at`  DATETIME        DEFAULT NULL,
    `created_by`    INT UNSIGNED    NOT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_classroom_id` (`classroom_id`),
    KEY `idx_status`       (`status`),
    KEY `idx_created_at`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks bulk session+meeting generation jobs';


-- ============================================================
-- TABLE 6: meeting_generation_job_items
-- Per-session status inside a bulk job
-- ============================================================
CREATE TABLE IF NOT EXISTS `meeting_generation_job_items` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `job_id`        INT UNSIGNED    NOT NULL COMMENT 'FK → meeting_generation_jobs.id',
    `session_id`    INT UNSIGNED    NOT NULL COMMENT 'FK → class_sessions.id',
    `status`        ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    `attempts`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `last_error`    VARCHAR(512)    DEFAULT NULL,
    `processed_at`  DATETIME        DEFAULT NULL,

    PRIMARY KEY (`id`),
    KEY `idx_job_id`        (`job_id`),
    KEY `idx_session_id`    (`session_id`),
    KEY `idx_job_status`    (`job_id`, `status`),
    UNIQUE KEY `uq_job_session` (`job_id`, `session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-session status tracking inside a bulk generation job';


-- ============================================================
-- TABLE 7: session_audit_log
-- Security audit trail for all meeting actions
-- ============================================================
CREATE TABLE IF NOT EXISTS `session_audit_log` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id`  INT UNSIGNED    DEFAULT NULL,
    `user_id`     INT UNSIGNED    DEFAULT NULL,
    `action`      VARCHAR(64)     NOT NULL COMMENT 'e.g. session_created, meeting_generated, join_accessed, session_cancelled',
    `details`     JSON            DEFAULT NULL,
    `ip_address`  VARCHAR(45)     DEFAULT NULL,
    `user_agent`  TEXT            DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_session_id`  (`session_id`),
    KEY `idx_user_id`     (`user_id`),
    KEY `idx_action`      (`action`),
    KEY `idx_created_at`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log for all meeting module actions';


-- ============================================================
-- TABLE 8: session_recordings (Future-ready placeholder)
-- Populated later via provider webhook / manual sync
-- ============================================================
CREATE TABLE IF NOT EXISTS `session_recordings` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `session_id`        INT UNSIGNED    NOT NULL,
    `provider`          ENUM('google_meet', 'zoom') NOT NULL,
    `recording_url`     TEXT            DEFAULT NULL,
    `download_url`      TEXT            DEFAULT NULL,
    `file_size_bytes`   BIGINT UNSIGNED DEFAULT NULL,
    `duration_seconds`  INT UNSIGNED    DEFAULT NULL,
    `recording_start`   DATETIME        DEFAULT NULL,
    `recording_end`     DATETIME        DEFAULT NULL,
    `provider_file_id`  VARCHAR(255)    DEFAULT NULL,
    `is_visible_to_student` TINYINT(1)  NOT NULL DEFAULT 0,
    `synced_at`         DATETIME        DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_provider`   (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Future-ready: session recording links synced from provider';


-- ============================================================
-- TABLE 9: session_attendance (Future-ready placeholder)
-- ============================================================
CREATE TABLE IF NOT EXISTS `session_attendance` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `session_id`    INT UNSIGNED    NOT NULL,
    `user_id`       INT UNSIGNED    NOT NULL COMMENT 'FK → users.id',
    `role`          ENUM('teacher', 'student') NOT NULL,
    `join_time`     DATETIME        DEFAULT NULL,
    `ip_address`    VARCHAR(45)     COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `leave_time`    DATETIME        DEFAULT NULL,
    `duration_seconds` INT UNSIGNED DEFAULT NULL,
    `source`        ENUM('provider_sync', 'manual', 'webhook') NOT NULL DEFAULT 'manual',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_session_user` (`session_id`, `user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_user_id`    (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Future-ready: attendance records per session';


-- ============================================================
-- FOREIGN KEYS (Add after all tables exist)
-- ============================================================
ALTER TABLE `class_sessions`
    ADD CONSTRAINT `fk_cs_classroom` FOREIGN KEY (`classroom_id`)
        REFERENCES `classrooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_cs_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_cs_job` FOREIGN KEY (`job_id`)
        REFERENCES `meeting_generation_jobs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `session_meetings`
    ADD CONSTRAINT `fk_sm_session` FOREIGN KEY (`session_id`)
        REFERENCES `class_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `meeting_generation_jobs`
    ADD CONSTRAINT `fk_mgj_classroom` FOREIGN KEY (`classroom_id`)
        REFERENCES `classrooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_mgj_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `meeting_generation_job_items`
    ADD CONSTRAINT `fk_mgji_job` FOREIGN KEY (`job_id`)
        REFERENCES `meeting_generation_jobs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_mgji_session` FOREIGN KEY (`session_id`)
        REFERENCES `class_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `session_recordings`
    ADD CONSTRAINT `fk_sr_session` FOREIGN KEY (`session_id`)
        REFERENCES `class_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `session_attendance`
    ADD CONSTRAINT `fk_sa_session` FOREIGN KEY (`session_id`)
        REFERENCES `class_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fk_sa_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

SET foreign_key_checks = 1;
CREATE TABLE `notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_audience` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_notices_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notice_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notice_id` int(11) NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notice_id` (`notice_id`),
  CONSTRAINT `fk_attachment_notice` FOREIGN KEY (`notice_id`) REFERENCES `notices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add FULLTEXT indexes for searching in notices and users tables
-- This is required to comply with database optimization rules (No LIKE '%x%')

-- For notices table
ALTER TABLE notices ADD FULLTEXT INDEX idx_notices_fulltext (title, content);

-- For users table
ALTER TABLE users ADD FULLTEXT INDEX idx_users_fulltext (username, email);
ALTER TABLE `users` ADD INDEX `idx_username` (`username`);
ALTER TABLE `classrooms` ADD INDEX `idx_class_name` (`class_name`);
ALTER TABLE `classrooms` ADD INDEX `idx_class_title` (`class_title`);
ALTER TABLE `class_sessions` ADD INDEX `idx_session_date_time` (`session_date`, `start_time`);

-- =============================================================
-- Attendance System Enhancement
-- Add ip_address column to existing session_attendance table
-- =============================================================

ALTER TABLE `session_attendance`
    ADD COLUMN `ip_address` VARCHAR(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `join_time`,
    ADD KEY `idx_att_join_time` (`join_time`);

-- Enable attendance tracking by default
INSERT IGNORE INTO `meeting_settings` (`setting_key`, `setting_val`)
VALUES ('attendance_sync_enabled', '1');
