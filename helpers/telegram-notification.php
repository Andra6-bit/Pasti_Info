<?php

/**
 * helpers/telegram-notification.php
 *
 * Manages formatted Telegram notification alerts for competition submission states,
 * including logging, preventing duplicate notification delivery, and retrying failed attempts.
 */

$root = dirname(__DIR__);
require_once $root . '/helpers/telegram.php'; // provides kirimNotifikasiTelegram()
require_once $root . '/config/database.php';  // provides $koneksi

/**
 * Logs and queues a Telegram notification.
 * Checks if the exact same message was already sent to the chat ID within the last 1 minute to prevent duplicates.
 *
 * @param string $chat_id
 * @param string $message
 * @return int|false The log ID or false on failure.
 */
function queueTelegramNotification(string $chat_id, string $message)
{
    global $koneksi;

    $chat_id = trim($chat_id);
    if (empty($chat_id) || !ctype_digit($chat_id)) {
        error_log("[Telegram Notification Error] Invalid Telegram Chat ID: '{$chat_id}'");
        return false;
    }

    // Check for recent duplicate (last 60 seconds)
    $dupQuery = "SELECT id FROM telegram_notification_logs 
                 WHERE telegram_chat_id = ? AND message = ? AND status = 'sent' AND created_at >= NOW() - INTERVAL 1 MINUTE LIMIT 1";
    $dupStmt = mysqli_prepare($koneksi, $dupQuery);
    if ($dupStmt) {
        mysqli_stmt_bind_param($dupStmt, "ss", $chat_id, $message);
        mysqli_stmt_execute($dupStmt);
        mysqli_stmt_store_result($dupStmt);
        if (mysqli_stmt_num_rows($dupStmt) > 0) {
            mysqli_stmt_close($dupStmt);
            error_log("[Telegram Notification Duplicate] Duplicate message skipped for Chat ID: {$chat_id}");
            return false;
        }
        mysqli_stmt_close($dupStmt);
    }

    // Insert log in 'pending' status
    $stmt = mysqli_prepare($koneksi, "INSERT INTO telegram_notification_logs (telegram_chat_id, message, status) VALUES (?, ?, 'pending')");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $chat_id, $message);
        if (mysqli_stmt_execute($stmt)) {
            $log_id = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt);
            return $log_id;
        }
        mysqli_stmt_close($stmt);
    }

    return false;
}

/**
 * Sends a queued Telegram notification immediately.
 *
 * @param int $log_id
 * @return bool True if successfully sent.
 */
function sendQueuedTelegramNotification(int $log_id): bool
{
    global $koneksi;

    $stmt = mysqli_prepare($koneksi, "SELECT telegram_chat_id, message, attempts FROM telegram_notification_logs WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "i", $log_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $log = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$log) {
        return false;
    }

    $chat_id  = $log['telegram_chat_id'];
    $message  = $log['message'];
    $attempts = (int)$log['attempts'] + 1;

    // Attempt sending
    $success = kirimNotifikasiTelegram($chat_id, $message);

    if ($success) {
        $updateStmt = mysqli_prepare($koneksi, "UPDATE telegram_notification_logs SET status = 'sent', attempts = ?, error_message = NULL WHERE id = ?");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "ii", $attempts, $log_id);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
        return true;
    } else {
        $error = "Failed to send message via Telegram Bot API.";
        $updateStmt = mysqli_prepare($koneksi, "UPDATE telegram_notification_logs SET status = 'failed', attempts = ?, error_message = ? WHERE id = ?");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "isi", $attempts, $error, $log_id);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
        return false;
    }
}

/**
 * Helper to queue and immediately attempt sending a Telegram message.
 *
 * @param string $chat_id
 * @param string $message
 * @return bool
 */
function notifyUserTelegram(string $chat_id, string $message): bool
{
    $log_id = queueTelegramNotification($chat_id, $message);
    if ($log_id) {
        return sendQueuedTelegramNotification($log_id);
    }
    return false;
}

/**
 * Retries failed Telegram notifications.
 * Can be called via cron script or inline during actions.
 *
 * @param int $max_attempts Maximum attempts to retry before giving up.
 * @return int Number of successfully retried notifications.
 */
function retryFailedTelegramNotifications(int $max_attempts = 3): int
{
    global $koneksi;

    $query = "SELECT id FROM telegram_notification_logs WHERE status = 'failed' AND attempts < ? ORDER BY id ASC";
    $stmt = mysqli_prepare($koneksi, $query);
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "i", $max_attempts);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $success_count = 0;
    while ($row = mysqli_fetch_assoc($res)) {
        if (sendQueuedTelegramNotification((int)$row['id'])) {
            $success_count++;
        }
    }
    mysqli_stmt_close($stmt);

    return $success_count;
}

// ─── EVENT SPECIFIC FORMATTERS ───────────────────────────────────────────────

function notifyPaymentSuccess(string $chat_id, string $title, string $invoice_id)
{
    $msg = "✅ <b>PEMBAYARAN BERHASIL!</b>\n\n"
         . "Halo! Pembayaran submission lomba Anda telah kami terima:\n\n"
         . "🏆 <b>Lomba:</b> " . htmlspecialchars($title) . "\n"
         . "🧾 <b>Invoice ID:</b> <code>" . htmlspecialchars($invoice_id) . "</code>\n\n"
         . "Lomba Anda saat ini masuk antrean <b>Review Admin</b>. Kami akan memberi tahu Anda setelah review selesai.";
    notifyUserTelegram($chat_id, $msg);
}

function notifyPaymentFailed(string $chat_id, string $title, string $invoice_id)
{
    $msg = "❌ <b>PEMBAYARAN GAGAL</b>\n\n"
         . "Halo! Kami mendeteksi bahwa pembayaran submission lomba Anda gagal:\n\n"
         . "🏆 <b>Lomba:</b> " . htmlspecialchars($title) . "\n"
         . "🧾 <b>Invoice ID:</b> <code>" . htmlspecialchars($invoice_id) . "</code>\n\n"
         . "Silakan lakukan submit ulang atau hubungi tim bantuan jika Anda merasa ada kesalahan.";
    notifyUserTelegram($chat_id, $msg);
}

function notifyPaymentExpired(string $chat_id, string $title, string $invoice_id)
{
    $msg = "⏳ <b>PEMBAYARAN EXPIRED</b>\n\n"
         . "Halo! Masa pembayaran submission lomba Anda telah berakhir (melebihi 24 jam):\n\n"
         . "🏆 <b>Lomba:</b> " . htmlspecialchars($title) . "\n"
         . "🧾 <b>Invoice ID:</b> <code>" . htmlspecialchars($invoice_id) . "</code>\n\n"
         . "Silakan submit ulang jika Anda masih ingin mendaftarkan kompetisi Anda.";
    notifyUserTelegram($chat_id, $msg);
}

function notifyUnderReview(string $chat_id, string $title)
{
    $msg = "🔍 <b>LOMBA SEDANG DIREVIEW</b>\n\n"
         . "Halo! Kompetisi Anda sedang diperiksa oleh Admin:\n\n"
         . "🏆 <b>Lomba:</b> " . htmlspecialchars($title) . "\n\n"
         . "Kami akan mengirimkan notifikasi baru segera setelah status disetujui atau ditolak.";
    notifyUserTelegram($chat_id, $msg);
}

function notifyApproved(string $chat_id, string $title)
{
    $msg = "🎉 <b>CONGRATS! LOMBA DI-APPROVE!</b>\n\n"
         . "Halo! Lomba yang Anda kirim telah disetujui oleh Admin dan resmi dipublish:\n\n"
         . "🏆 <b>Lomba:</b> " . htmlspecialchars($title) . "\n\n"
         . "Sekarang lomba Anda dapat dilihat oleh publik di platform LombaID. Terima kasih atas partisipasi Anda!";
    notifyUserTelegram($chat_id, $msg);
}

function notifyRejected(string $chat_id, string $title, string $note)
{
    $msg = "⚠️ <b>LOMBA DITOLAK</b>\n\n"
         . "Halo! Mohon maaf, lomba yang Anda kirim belum disetujui oleh Admin:\n\n"
         . "🏆 <b>Lomba:</b> " . htmlspecialchars($title) . "\n"
         . "📝 <b>Alasan Penolakan:</b> " . htmlspecialchars($note) . "\n\n"
         . "Dana pembayaran Anda akan <b>direfund secara penuh</b> melalui Xendit. Proses refund sedang diajukan.";
    notifyUserTelegram($chat_id, $msg);
}

function notifyRefundSuccess(string $chat_id, string $title, int $amount)
{
    $msg = "💸 <b>REFUND BERHASIL DIPROSES!</b>\n\n"
         . "Halo! Refund pembayaran submission Anda telah sukses diajukan ke Xendit:\n\n"
         . "🏆 <b>Lomba:</b> " . htmlspecialchars($title) . "\n"
         . "💰 <b>Jumlah:</b> Rp " . number_format($amount, 0, ',', '.') . "\n\n"
         . "Waktu masuknya dana kembali bergantung pada kanal pembayaran asal (e-wallet instan, kartu kredit 7-14 hari kerja).";
    notifyUserTelegram($chat_id, $msg);
}
