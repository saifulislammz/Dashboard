-- =============================================================
-- Database Optimization & Index Enhancement Migration
-- Applied: 2026-10-31
-- Ensures all filter, composite, and search queries are indexed.
-- =============================================================

-- 1. Invoices Table Indexing (Filter & Sort Optimization)
-- Ensures fast filtering on status, currency, date, and composite dashboards
ALTER TABLE `invoices`
    ADD INDEX IF NOT EXISTS `idx_status` (`status`),
    ADD INDEX IF NOT EXISTS `idx_currency` (`currency`),
    ADD INDEX IF NOT EXISTS `idx_invoice_date` (`invoice_date`),
    ADD INDEX IF NOT EXISTS `idx_deleted_at` (`deleted_at`),
    ADD INDEX IF NOT EXISTS `idx_status_created_at` (`status`, `created_at`),
    ADD INDEX IF NOT EXISTS `idx_currency_deleted` (`currency`, `deleted_at`);

-- 2. Quiz Attempts Composite Indexing
-- Optimizes Dashboard stats and attempt queries checking voice submissions per quiz
ALTER TABLE `quiz_attempts`
    ADD INDEX IF NOT EXISTS `idx_qa_quiz_voice` (`quiz_id`, `voice_submitted`);

-- 3. Classroom Search & Ordering Indexes
ALTER TABLE `classrooms`
    ADD INDEX IF NOT EXISTS `idx_class_name` (`class_name`),
    ADD INDEX IF NOT EXISTS `idx_class_title` (`class_title`),
    ADD INDEX IF NOT EXISTS `idx_classroom_status_created` (`status`, `created_at`);

