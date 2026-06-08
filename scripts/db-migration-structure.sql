-- scripts/db-migration-structure.sql
-- Database structural, constraint, and consistency migration for db_pinfo.

-- =============================================================================
-- FIX 1 — Add Missing NOT NULL Constraints
-- =============================================================================

-- Update existing NULL values to defaults in target columns
UPDATE users SET role = 'user' WHERE role IS NULL;
UPDATE users SET profile_picture = 'default-avatar.jpg' WHERE profile_picture IS NULL;

UPDATE competitions SET registration_fee = 0 WHERE registration_fee IS NULL;
UPDATE competitions SET payment_status = 'unpaid' WHERE payment_status IS NULL;
UPDATE competitions SET approval_status = 'pending' WHERE approval_status IS NULL;
UPDATE competitions SET submission_status = 'draft' WHERE submission_status IS NULL;
UPDATE competitions SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL;

UPDATE telegram_notification_logs SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL;
UPDATE telegram_notification_logs SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL;

-- Modify columns to be NOT NULL with defaults
ALTER TABLE users
  MODIFY role VARCHAR(20) NOT NULL DEFAULT 'user',
  MODIFY profile_picture VARCHAR(255) NOT NULL DEFAULT 'default-avatar.jpg';

ALTER TABLE competitions
  MODIFY registration_fee INT(11) NOT NULL DEFAULT 0,
  MODIFY payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
  MODIFY approval_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  MODIFY submission_status VARCHAR(20) NOT NULL DEFAULT 'draft',
  MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE telegram_notification_logs
  MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP;


-- =============================================================================
-- FIX 8 — Fix Inconsistent Column Naming (Part 1: Rename Column)
-- =============================================================================
ALTER TABLE telegram_notification_logs
  CHANGE chat_id telegram_chat_id VARCHAR(50) NULL;


-- =============================================================================
-- FIX 2 — Add Missing UNIQUE Constraints
-- =============================================================================
ALTER TABLE competition_categories
  ADD UNIQUE KEY uq_comp_cat (competition_id, category_id);

ALTER TABLE saved_competitions
  ADD UNIQUE KEY uq_saved_comp (user_id, competition_id);

ALTER TABLE users
  ADD UNIQUE KEY uq_users_telegram_chat_id (telegram_chat_id);


-- =============================================================================
-- FIX 3 — Add Missing Foreign Key Constraints
-- =============================================================================

-- Update any NULL or invalid user_id/reviewed_by values first
UPDATE competitions SET user_id = NULL
  WHERE user_id NOT IN (SELECT id FROM users);
UPDATE competitions SET reviewed_by = NULL
  WHERE reviewed_by IS NOT NULL
  AND reviewed_by NOT IN (SELECT id FROM users);

-- Add Foreign Keys
ALTER TABLE competitions
  ADD CONSTRAINT fk_competitions_user_id
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_competitions_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE;


-- =============================================================================
-- FIX 4 — Add Missing Indexes
-- =============================================================================
ALTER TABLE competitions
  ADD INDEX idx_competitions_user_id (user_id),
  ADD INDEX idx_competitions_reviewed_by (reviewed_by),
  ADD INDEX idx_competitions_submission_status (submission_status),
  ADD INDEX idx_competitions_xendit_invoice_id (xendit_invoice_id),
  ADD INDEX idx_competitions_title (title);

ALTER TABLE users
  ADD INDEX idx_users_telegram_chat_id (telegram_chat_id);

-- Note: indexing telegram_chat_id since column was renamed from chat_id
ALTER TABLE telegram_notification_logs
  ADD INDEX idx_tnl_status (status),
  ADD INDEX idx_tnl_chat_id (telegram_chat_id);


-- =============================================================================
-- FIX 5 — Convert VARCHAR status columns to ENUM (with Extended Options)
-- =============================================================================
-- First, ensure any NULL refund_status is defaulted to 'none' as required
UPDATE competitions SET refund_status = 'none' WHERE refund_status IS NULL;

ALTER TABLE competitions
  MODIFY submission_status
    ENUM('draft','published','unpublished','pending_review','rejected','expired','unpaid')
    NOT NULL DEFAULT 'draft',
  MODIFY approval_status
    ENUM('pending','approved','rejected')
    NOT NULL DEFAULT 'pending',
  MODIFY payment_status
    ENUM('unpaid','paid','refunded','failed','expired')
    NOT NULL DEFAULT 'unpaid',
  MODIFY refund_status
    ENUM('none','pending','success','failed') DEFAULT 'none';

ALTER TABLE telegram_notification_logs
  MODIFY status
    ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending';


-- =============================================================================
-- FIX 6 — Split date_range into proper DATE columns
-- =============================================================================
ALTER TABLE competitions
  ADD COLUMN start_date DATE NULL AFTER date_range,
  ADD COLUMN end_date DATE NULL AFTER start_date;


-- =============================================================================
-- FIX 8 — Fix Inconsistent Column Naming (Part 2: Foreign Keys)
-- =============================================================================
ALTER TABLE competition_categories
  DROP FOREIGN KEY competition_categories_ibfk_1,
  DROP FOREIGN KEY competition_categories_ibfk_2,
  ADD CONSTRAINT fk_comp_cat_competition_id
    FOREIGN KEY (competition_id) REFERENCES competitions(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_comp_cat_category_id
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE saved_competitions
  DROP FOREIGN KEY saved_competitions_ibfk_1,
  DROP FOREIGN KEY saved_competitions_ibfk_2,
  ADD CONSTRAINT fk_saved_comp_user_id
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_saved_comp_competition_id
    FOREIGN KEY (competition_id) REFERENCES competitions(id)
    ON DELETE CASCADE ON UPDATE CASCADE;


-- =============================================================================
-- FIX 9 — Create Audit Trail Table
-- =============================================================================
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  target_table VARCHAR(50) NULL,
  target_id INT NULL,
  old_value TEXT NULL,
  new_value TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_logs_user_id
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_activity_logs_user_id (user_id),
  INDEX idx_activity_logs_action (action),
  INDEX idx_activity_logs_created_at (created_at)
);
