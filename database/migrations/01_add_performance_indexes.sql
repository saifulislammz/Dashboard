ALTER TABLE `users` ADD INDEX `idx_username` (`username`);
ALTER TABLE `classrooms` ADD INDEX `idx_class_name` (`class_name`);
ALTER TABLE `classrooms` ADD INDEX `idx_class_title` (`class_title`);
ALTER TABLE `class_sessions` ADD INDEX `idx_session_date_time` (`session_date`, `start_time`);
