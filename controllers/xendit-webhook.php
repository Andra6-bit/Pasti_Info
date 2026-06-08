<?php

/**
 * controllers/xendit-webhook.php
 *
 * Webhook endpoint for Xendit callbacks. Validates webhook token/signature,
 * updates payment and submission statuses, and triggers Telegram alerts.
 */

require_once '../config/database.php';             // provides $koneksi
require_once '../helpers/telegram-notification.php'; // provides notifyPaymentSuccess(), etc.

// Enforce POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ── 1. WEBHOOK VERIFICATION ──────────────────────────────────────────────────
// 1-line reason: Verify HMAC-SHA256 signature using x-callback-signature header, falling back to callback token if absent.
$headerSignature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
$headerToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';
$configToken = $_ENV['XENDIT_WEBHOOK_TOKEN'] ?? '';
$payloadRaw = file_get_contents('php://input');
$verified = false;
if (!empty($configToken)) {
    if (!empty($headerSignature) && hash_equals(hash_hmac('sha256', $payloadRaw, $configToken), $headerSignature)) {
        $verified = true;
    } elseif (!empty($headerToken) && hash_equals($configToken, $headerToken)) {
        $verified = true;
    }
}
if (!$verified) {
    http_response_code(403);
    error_log("[Xendit Webhook Warning] Unauthorized webhook attempt.");
    exit('Forbidden');
}

// ── 2. READ & PARSE PAYLOAD ──────────────────────────────────────────────────
$payloadRaw = file_get_contents('php://input');
$payload = json_decode($payloadRaw, true);

if (!$payload || !isset($payload['id']) || !isset($payload['status'])) {
    http_response_code(400);
    error_log("[Xendit Webhook Error] Invalid payload structure.");
    exit('Bad Request');
}

$invoice_id  = $payload['id'];
$status      = strtoupper($payload['status']);
$external_id = $payload['external_id'] ?? '';

error_log("[Xendit Webhook Info] Received callback. invoice_id={$invoice_id} | status={$status} | external_id={$external_id}");

// 1-line reason: Retrieve submitter Telegram chat ID from users table using a JOIN to replace redundant telegram_chat_id column.
$compQuery = "SELECT c.id, c.title, u.telegram_chat_id, c.payment_status, c.submission_status 
              FROM competitions c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.xendit_invoice_id = ? LIMIT 1";
$compStmt  = mysqli_prepare($koneksi, $compQuery);

if (!$compStmt) {
    http_response_code(500);
    exit('Database Error');
}

mysqli_stmt_bind_param($compStmt, "s", $invoice_id);
mysqli_stmt_execute($compStmt);
$compRes = mysqli_stmt_get_result($compStmt);
$competition = mysqli_fetch_assoc($compRes);
mysqli_stmt_close($compStmt);

if (!$competition) {
    // Return 200 to Xendit so it doesn't keep retrying, but log the issue
    error_log("[Xendit Webhook Warning] Competition not found for invoice_id={$invoice_id}");
    http_response_code(200);
    exit('Invoice not found in system');
}

$competition_id = (int)$competition['id'];
$title          = $competition['title'];
$chat_id        = $competition['telegram_chat_id'];
$current_pay    = strtolower($competition['payment_status']);

// ── 4. PROCESS STATUS TRANSITIONS ────────────────────────────────────────────
if ($status === 'PAID' || $status === 'SETTLED') {
    // Anti-duplicate protection: skip if already processed
    if ($current_pay === 'paid') {
        error_log("[Xendit Webhook Info] Invoice={$invoice_id} already marked as paid. Skipping.");
        http_response_code(200);
        exit('OK');
    }

    $updateQuery = "
        UPDATE competitions 
        SET payment_status = 'paid', submission_status = 'pending_review', paid_at = NOW() 
        WHERE id = ?
    ";
    $upStmt = mysqli_prepare($koneksi, $updateQuery);
    if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "i", $competition_id);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);
        
        // Notify submitter via Telegram
        notifyPaymentSuccess($chat_id, $title, $invoice_id);
        error_log("[Xendit Webhook Success] Competition ID={$competition_id} marked as PAID.");
    }
} elseif ($status === 'EXPIRED') {
    if ($current_pay === 'expired') {
        http_response_code(200);
        exit('OK');
    }

    $updateQuery = "
        UPDATE competitions 
        SET payment_status = 'expired', submission_status = 'expired' 
        WHERE id = ?
    ";
    $upStmt = mysqli_prepare($koneksi, $updateQuery);
    if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "i", $competition_id);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);

        // Notify submitter via Telegram
        notifyPaymentExpired($chat_id, $title, $invoice_id);
        error_log("[Xendit Webhook Info] Competition ID={$competition_id} marked as EXPIRED.");
    }
} else {
    error_log("[Xendit Webhook Info] Unhandled status={$status} for invoice={$invoice_id}");
}

http_response_code(200);
echo "OK";
