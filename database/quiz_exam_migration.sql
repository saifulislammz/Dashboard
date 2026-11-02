-- ============================================================
-- Unified Quiz & Exam System — Database Migration
-- File: database/quiz_exam_migration.sql
-- Compatible: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.2+
-- Safe to re-run: Uses information_schema checks
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Extend `quizzes` table
-- ------------------------------------------------------------
-- type
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quizzes' AND COLUMN_NAME = 'type');
SET @sql := IF(@col = 0, 'ALTER TABLE quizzes ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT "quiz" AFTER title', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- duration_minutes
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quizzes' AND COLUMN_NAME = 'duration_minutes');
SET @sql := IF(@col = 0, 'ALTER TABLE quizzes ADD COLUMN duration_minutes INT UNSIGNED NOT NULL DEFAULT 0 AFTER type', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- attempt_limit
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quizzes' AND COLUMN_NAME = 'attempt_limit');
SET @sql := IF(@col = 0, 'ALTER TABLE quizzes ADD COLUMN attempt_limit TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER duration_minutes', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- total_marks
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quizzes' AND COLUMN_NAME = 'total_marks');
SET @sql := IF(@col = 0, 'ALTER TABLE quizzes ADD COLUMN total_marks DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER attempt_limit', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- pass_marks
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quizzes' AND COLUMN_NAME = 'pass_marks');
SET @sql := IF(@col = 0, 'ALTER TABLE quizzes ADD COLUMN pass_marks DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER total_marks', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- custom_fields
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quizzes' AND COLUMN_NAME = 'custom_fields');
SET @sql := IF(@col = 0, 'ALTER TABLE quizzes ADD COLUMN custom_fields JSON NULL AFTER pass_marks', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indexes for `quizzes`
SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quizzes' AND INDEX_NAME = 'idx_quizzes_type');
SET @sql := IF(@idx = 0, 'ALTER TABLE quizzes ADD INDEX idx_quizzes_type (type)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quizzes' AND INDEX_NAME = 'idx_quizzes_type_status');
SET @sql := IF(@idx = 0, 'ALTER TABLE quizzes ADD INDEX idx_quizzes_type_status (type, status, deleted_at)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 2. Extend `quiz_questions` table
-- ------------------------------------------------------------
-- marks
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_questions' AND COLUMN_NAME = 'marks');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_questions ADD COLUMN marks DECIMAL(5,2) NOT NULL DEFAULT 1.00 AFTER question_text', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index for `quiz_questions`
SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_questions' AND INDEX_NAME = 'idx_qq_marks');
SET @sql := IF(@idx = 0, 'ALTER TABLE quiz_questions ADD INDEX idx_qq_marks (marks)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 3. Extend `quiz_attempts` table
-- ------------------------------------------------------------
-- attempt_number
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND COLUMN_NAME = 'attempt_number');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_attempts ADD COLUMN attempt_number TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER whatsapp_number', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- custom_fields_data
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND COLUMN_NAME = 'custom_fields_data');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_attempts ADD COLUMN custom_fields_data JSON NULL AFTER attempt_number', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- total_marks
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND COLUMN_NAME = 'total_marks');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_attempts ADD COLUMN total_marks DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER correct_answers', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- obtained_marks
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND COLUMN_NAME = 'obtained_marks');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_attempts ADD COLUMN obtained_marks DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER total_marks', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- expires_at
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND COLUMN_NAME = 'expires_at');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_attempts ADD COLUMN expires_at DATETIME NULL AFTER started_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- auto_submitted
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND COLUMN_NAME = 'auto_submitted');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_attempts ADD COLUMN auto_submitted TINYINT(1) NOT NULL DEFAULT 0 AFTER completed_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- admin_voice_file_path
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND COLUMN_NAME = 'admin_voice_file_path');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_attempts ADD COLUMN admin_voice_file_path VARCHAR(500) NULL AFTER voice_file_path', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- voice_marks
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND COLUMN_NAME = 'voice_marks');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_attempts ADD COLUMN voice_marks DECIMAL(5,2) NULL AFTER admin_voice_file_path', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indexes for `quiz_attempts`
SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND INDEX_NAME = 'idx_qa_quiz_phone_attempt');
SET @sql := IF(@idx = 0, 'ALTER TABLE quiz_attempts ADD INDEX idx_qa_quiz_phone_attempt (quiz_id, whatsapp_number, attempt_number)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND INDEX_NAME = 'idx_qa_expires_at');
SET @sql := IF(@idx = 0, 'ALTER TABLE quiz_attempts ADD INDEX idx_qa_expires_at (expires_at)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_attempts' AND INDEX_NAME = 'idx_qa_auto_submitted');
SET @sql := IF(@idx = 0, 'ALTER TABLE quiz_attempts ADD INDEX idx_qa_auto_submitted (auto_submitted)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 4. Extend `quiz_answers` table
-- ------------------------------------------------------------
-- selected_option_ids
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_answers' AND COLUMN_NAME = 'selected_option_ids');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_answers ADD COLUMN selected_option_ids JSON NULL AFTER selected_option_id', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- marks_awarded
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quiz_answers' AND COLUMN_NAME = 'marks_awarded');
SET @sql := IF(@col = 0, 'ALTER TABLE quiz_answers ADD COLUMN marks_awarded DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER is_correct', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 5. Data Backfill for Existing Records
-- ------------------------------------------------------------
-- Backfill total_marks on existing quizzes based on sum of question marks (1.00 each)
UPDATE quizzes q 
SET q.total_marks = (
    SELECT COALESCE(SUM(qq.marks), 0.00) 
    FROM quiz_questions qq 
    WHERE qq.quiz_id = q.id
)
WHERE q.total_marks = 0.00;

-- Backfill total_marks and obtained_marks on existing completed/submitted attempts
UPDATE quiz_attempts 
SET total_marks = total_questions, 
    obtained_marks = correct_answers 
WHERE total_marks = 0.00 AND total_questions > 0;

SET FOREIGN_KEY_CHECKS = 1;
