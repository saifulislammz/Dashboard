-- Migration: Multi-Account Meeting Provider System
-- Target: provider_accounts & class_sessions tables

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- 1. Drop the old UNIQUE constraint
ALTER TABLE provider_accounts DROP INDEX uq_provider;

-- 2. Add nickname and display_order columns
ALTER TABLE provider_accounts 
    ADD COLUMN nickname VARCHAR(100) DEFAULT NULL AFTER provider,
    ADD COLUMN display_order TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER nickname;

-- 3. Add composite unique index (provider + account_email)
-- This prevents the exact same Google account from being connected twice,
-- but allows multiple different Google accounts.
ALTER TABLE provider_accounts 
    ADD UNIQUE KEY uq_provider_email (provider, account_email);

-- 4. Add provider_account_id to class_sessions
ALTER TABLE class_sessions 
    ADD COLUMN provider_account_id INT UNSIGNED DEFAULT NULL 
    COMMENT 'FK → provider_accounts.id — which account was used';

ALTER TABLE class_sessions
    ADD CONSTRAINT fk_cs_provider_account 
    FOREIGN KEY (provider_account_id) REFERENCES provider_accounts(id) ON DELETE SET NULL;

-- 5. Add indexes for performance
ALTER TABLE class_sessions
    ADD INDEX idx_provider_account_id (provider_account_id);

ALTER TABLE provider_accounts
    ADD INDEX idx_provider_connected_order (provider, is_connected, display_order);

SET foreign_key_checks = 1;
