-- scripts/db-reset-fresh.sql
-- SQL Script to safely wipe all transaction/submission data and seed fresh defaults.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Clear all tables (using DELETE FROM since TRUNCATE is restricted by Foreign Keys)
DELETE FROM `activity_logs`;
DELETE FROM `competition_categories`;
DELETE FROM `saved_competitions`;
DELETE FROM `telegram_notification_logs`;
DELETE FROM `user_category_subscriptions`;
DELETE FROM `debate_messages`;
DELETE FROM `debate_sessions`;
DELETE FROM `competitions`;
DELETE FROM `users`;
DELETE FROM `categories`;
DELETE FROM `settings`;

ALTER TABLE `activity_logs` AUTO_INCREMENT = 1;
ALTER TABLE `competition_categories` AUTO_INCREMENT = 1;
ALTER TABLE `saved_competitions` AUTO_INCREMENT = 1;
ALTER TABLE `telegram_notification_logs` AUTO_INCREMENT = 1;
ALTER TABLE `debate_messages` AUTO_INCREMENT = 1;
ALTER TABLE `competitions` AUTO_INCREMENT = 1;
ALTER TABLE `users` AUTO_INCREMENT = 1;
ALTER TABLE `categories` AUTO_INCREMENT = 1;

-- 2. Seed default categories
INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Design'),
(2, 'Programming'),
(3, 'Hacking'),
(4, 'Matematika'),
(5, 'Sains'),
(6, 'Robotika'),
(7, 'Bisnis'),
(8, 'Fotografi'),
(9, 'Musik'),
(10, 'Debat'),
(11, 'Riset'),
(12, 'Olahraga'),
(13, 'Esports'),
(14, 'Umum');

-- 3. Seed default settings
INSERT INTO `settings` (`key`, `value`) VALUES
('submission_fee', '20000');

-- 4. Seed default users (with secure BCrypt passwords)
-- Admin: username 'admin', password 'admin'
-- User: username 'budbud', password 'budi'
INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_picture`, `role`, `telegram_chat_id`, `institution`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$12zO0ZvvFfoVmIY5GzgPq.4HCvDmWYvqDYE.w71SOG9ZfXqVLPd3a', 'default_user.png', 'admin', '1123456', ''),
(2, 'budbud', 'budi@gmail.com', '$2y$10$lHnofQwoi8ohzqYp5/bUJOWU0JP.88RbwoJTgt0XjpiaPUeq58OPK', 'default_user.png', 'user', '6278216449', '');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
