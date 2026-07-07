-- ============================================================
-- Arabic Quiz System — Database Migration
-- File: database/quiz_migration.sql
-- Run AFTER: database_schema.sql (users table must exist)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. quizzes — কুইজের মূল রেকর্ড
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quizzes (
    id            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    title         VARCHAR(255)      NOT NULL,
    description   TEXT              NULL,
    status        VARCHAR(20)       NOT NULL DEFAULT 'active',  -- active | inactive
    created_by    BIGINT UNSIGNED   NOT NULL,                   -- FK → users.id (admin)
    created_at    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at    DATETIME          NULL,

    PRIMARY KEY (id),
    INDEX idx_quizzes_status      (status),
    INDEX idx_quizzes_created_by  (created_by),
    INDEX idx_quizzes_deleted_at  (deleted_at),
    INDEX idx_quizzes_status_del  (status, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. quiz_questions — প্রতিটি প্রশ্ন
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_questions (
    id            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    quiz_id       BIGINT UNSIGNED   NOT NULL,
    type          VARCHAR(30)       NOT NULL,   -- 'letter' | 'pronunciation' | 'voice'
    question_text TEXT              NOT NULL,   -- আরবি অক্ষর / শব্দ / প্যারাগ্রাফ
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_qq_quiz_id       (quiz_id),
    INDEX idx_qq_type          (type),
    INDEX idx_qq_quiz_order    (quiz_id, display_order),

    CONSTRAINT fk_qq_quiz_id
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. quiz_options — MCQ অপশন (letter & pronunciation প্রশ্নের জন্য)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_options (
    id            BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    question_id   BIGINT UNSIGNED   NOT NULL,
    option_text   VARCHAR(255)      NOT NULL,   -- বাংলা / আরবি উচ্চারণ টেক্সট
    is_correct    TINYINT(1)        NOT NULL DEFAULT 0,
    option_order  TINYINT UNSIGNED  NOT NULL DEFAULT 0,

    PRIMARY KEY (id),
    INDEX idx_qo_question_id   (question_id),
    INDEX idx_qo_is_correct    (question_id, is_correct),

    CONSTRAINT fk_qo_question_id
        FOREIGN KEY (question_id) REFERENCES quiz_questions(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. quiz_attempts — অংশগ্রহণকারীর কুইজ সেশন
--    Guest-based: login ছাড়া, participant info stored here
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    quiz_id          BIGINT UNSIGNED   NOT NULL,

    -- অংশগ্রহণকারীর তথ্য (guest — কোনো user account নেই)
    participant_name VARCHAR(150)      NOT NULL,
    gender           VARCHAR(10)       NOT NULL,   -- 'male' | 'female'
    whatsapp_number  VARCHAR(20)       NOT NULL,
    email            VARCHAR(150)      NULL,        -- অপশনাল

    -- কুইজের অবস্থা
    -- 'in_progress'     → মাঝে ছেড়ে গেছে (retry করা যাবে)
    -- 'completed'       → MCQ শেষ, ভয়েস বাকি
    -- 'voice_submitted' → সম্পূর্ণ শেষ, এই নম্বর চিরতরে block
    status           VARCHAR(20)       NOT NULL DEFAULT 'in_progress',

    -- স্কোর
    total_questions  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    correct_answers  SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    -- ভয়েস রেকর্ডিং
    voice_submitted  TINYINT(1)        NOT NULL DEFAULT 0,
    voice_file_path  VARCHAR(500)      NULL,        -- storage/quiz_voices/yyyy/mm/xxx.webm
    voice_reviewed   TINYINT(1)        NOT NULL DEFAULT 0,
    voice_note       TEXT              NULL,        -- অ্যাডমিনের রিভিউ নোট

    -- অ্যাডমিন notification badge (0 = নতুন / অদেখা)
    admin_notified   TINYINT(1)        NOT NULL DEFAULT 0,

    -- সেশন token (IDOR প্রতিরোধ — URL-এ DB id expose হয় না)
    session_token    VARCHAR(64)       NOT NULL,

    started_at       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at     DATETIME          NULL,

    PRIMARY KEY (id),

    -- Token unique — প্রতিটি attempt-এর আলাদা token
    UNIQUE KEY uq_attempt_token       (session_token),

    INDEX idx_qa_quiz_id              (quiz_id),
    INDEX idx_qa_whatsapp             (whatsapp_number),
    INDEX idx_qa_quiz_whatsapp        (quiz_id, whatsapp_number),
    INDEX idx_qa_status               (status),
    INDEX idx_qa_voice_submitted      (voice_submitted),
    INDEX idx_qa_admin_notified       (admin_notified),
    INDEX idx_qa_started_at           (started_at),
    INDEX idx_qa_voice_unreviewed     (voice_submitted, admin_notified),

    CONSTRAINT fk_qa_quiz_id
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. quiz_answers — প্রতিটি MCQ উত্তরের লগ
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS quiz_answers (
    id                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    attempt_id           BIGINT UNSIGNED  NOT NULL,
    question_id          BIGINT UNSIGNED  NOT NULL,
    selected_option_id   BIGINT UNSIGNED  NULL,   -- voice প্রশ্নে NULL
    is_correct           TINYINT(1)       NOT NULL DEFAULT 0,
    answered_at          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    -- একটি attempt-এ একটি প্রশ্নের একটিই উত্তর
    UNIQUE KEY uq_ans_attempt_question (attempt_id, question_id),
    INDEX idx_ans_attempt_id           (attempt_id),
    INDEX idx_ans_question_id          (question_id),

    CONSTRAINT fk_ans_attempt_id
        FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_ans_question_id
        FOREIGN KEY (question_id) REFERENCES quiz_questions(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Migration complete. Tables created:
--   1. quizzes
--   2. quiz_questions
--   3. quiz_options
--   4. quiz_attempts
--   5. quiz_answers
-- ============================================================
