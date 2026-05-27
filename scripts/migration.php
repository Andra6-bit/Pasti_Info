<?php

/**
 * scripts/migration.php
 *
 * Runs database migrations to add columns to the `competitions` table
 * and create the `settings` table.
 */

$root = dirname(__DIR__);
require_once $root . '/config/database.php'; // provides $koneksi

echo "Starting Database Migration...\n";

// 1. Create `settings` table
$createSettingsTable = "
CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(50) NOT NULL PRIMARY KEY,
  `value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if (mysqli_query($koneksi, $createSettingsTable)) {
    echo "✓ Table `settings` verified/created.\n";
} else {
    die("✗ Failed to create `settings` table: " . mysqli_error($koneksi) . "\n");
}

// Seed default submission fee
$seedFee = "INSERT IGNORE INTO `settings` (`key`, `value`) VALUES ('submission_fee', '20000');";
mysqli_query($koneksi, $seedFee);

// 2. Add columns to `competitions` table
$columnsToAdd = [
    'user_id' => "INT(11) DEFAULT NULL",
    'telegram_chat_id' => "VARCHAR(50) DEFAULT NULL",
    'payment_status' => "VARCHAR(20) DEFAULT 'unpaid'",
    'approval_status' => "VARCHAR(20) DEFAULT 'pending'",
    'submission_status' => "VARCHAR(20) DEFAULT 'draft'",
    'xendit_invoice_id' => "VARCHAR(100) DEFAULT NULL",
    'xendit_invoice_url' => "VARCHAR(500) DEFAULT NULL",
    'paid_at' => "DATETIME DEFAULT NULL",
    'refunded_at' => "DATETIME DEFAULT NULL",
    'refund_status' => "VARCHAR(20) DEFAULT NULL",
    'reviewed_by' => "INT(11) DEFAULT NULL",
    'review_note' => "TEXT DEFAULT NULL",
    'published_at' => "DATETIME DEFAULT NULL",
    'created_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP"
];

// Check existing columns
$existingColumns = [];
$res = mysqli_query($koneksi, "SHOW COLUMNS FROM `competitions`");
while ($row = mysqli_fetch_assoc($res)) {
    $existingColumns[] = strtolower($row['Field']);
}

foreach ($columnsToAdd as $colName => $colDef) {
    if (!in_array(strtolower($colName), $existingColumns)) {
        $alterQuery = "ALTER TABLE `competitions` ADD COLUMN `{$colName}` {$colDef};";
        if (mysqli_query($koneksi, $alterQuery)) {
            echo "✓ Column `{$colName}` successfully added to `competitions`.\n";
        } else {
            echo "✗ Failed to add column `{$colName}`: " . mysqli_error($koneksi) . "\n";
        }
    } else {
        echo "✓ Column `{$colName}` already exists in `competitions`.\n";
    }
}

// 3. Create telegram retry/logs table (optional but great for retry/duplicates)
$createTelegramLogsTable = "
CREATE TABLE IF NOT EXISTS `telegram_notification_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `chat_id` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, sent, failed
  `attempts` INT(11) NOT NULL DEFAULT 0,
  `error_message` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if (mysqli_query($koneksi, $createTelegramLogsTable)) {
    echo "✓ Table `telegram_notification_logs` verified/created.\n";
} else {
    echo "✗ Failed to create `telegram_notification_logs` table: " . mysqli_error($koneksi) . "\n";
}

echo "Database Migration Completed Successfully!\n";
