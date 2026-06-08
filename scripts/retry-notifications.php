<?php
// 1-line reason: Enforce admin session verification to restrict web-based execution to logged-in admins.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if ((!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($_SESSION['user_role'] ?? '') !== 'admin') && php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Access Denied! Admin only.');
}

/**
 * scripts/retry-notifications.php
 *
 * Background/CLI script to retry failed Telegram notification delivery.
 * Can be run via a system cron job or executed manually.
 *
 * Usage:
 *   php retry-notifications.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Access denied. CLI only.');
}

$root = dirname(__DIR__);
require_once $root . '/helpers/telegram-notification.php'; // provides retryFailedTelegramNotifications()

echo "[" . date('Y-m-d H:i:s') . "] Starting Telegram Notification Retry Process...\n";

try {
    $retried_count = retryFailedTelegramNotifications(3);
    echo "[" . date('Y-m-d H:i:s') . "] Retry completed. Successfully sent {$retried_count} failed notifications.\n";
} catch (Exception $e) {
    error_log("[Telegram Retry CLI Error] Exception: " . $e->getMessage());
    echo "[" . date('Y-m-d H:i:s') . "] Error occurred: " . $e->getMessage() . "\n";
}
