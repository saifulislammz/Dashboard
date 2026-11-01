-- ============================================================
-- Student & Teacher Email Management — Permission Migration
-- File: database/email_management_permission_migration.sql
-- ============================================================

INSERT INTO `permissions` (`module`, `action`, `slug`, `name`, `description`, `sort_order`) 
VALUES (
    'users', 
    'change_email', 
    'users.change_email', 
    'Change User Email', 
    'Allow administrator to update primary login and account email for students and teachers', 
    55
)
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`), 
    `description` = VALUES(`description`),
    `sort_order` = VALUES(`sort_order`);

