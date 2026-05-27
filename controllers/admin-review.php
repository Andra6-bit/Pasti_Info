<?php
session_start();

/**
 * controllers/admin-review.php
 *
 * Processes admin approval and rejection of user-submitted competitions,
 * handles automated Xendit refunds on rejection, and triggers Telegram alerts.
 */

$session_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? '';
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $session_user !== 'admin') {
    header("Location: ../pages/auth.php?error=" . urlencode("Access Denied! Admin only."));
    exit();
}

require_once '../config/database.php';             // provides $koneksi
require_once '../helpers/xendit.php';               // provides refundXenditInvoice()
require_once '../helpers/telegram-notification.php'; // provides Telegram notification helpers

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/dashboard.php?tab=review-lomba");
    exit();
}

$action = trim($_POST['action'] ?? '');
$comp_id = (int)($_POST['id'] ?? 0);
$admin_id = (int)($_SESSION['user_id'] ?? 1); // fallback to 1 (admin)

if ($comp_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Invalid parameters.") . "&msg_type=error");
    exit();
}

// Fetch the competition details
$compQuery = "SELECT title, telegram_chat_id, xendit_invoice_id, payment_status, submission_status FROM competitions WHERE id = ? LIMIT 1";
$compStmt = mysqli_prepare($koneksi, $compQuery);
if (!$compStmt) {
    header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Database query error.") . "&msg_type=error");
    exit();
}
mysqli_stmt_bind_param($compStmt, "i", $comp_id);
mysqli_stmt_execute($compStmt);
$compRes = mysqli_stmt_get_result($compStmt);
$lomba = mysqli_fetch_assoc($compRes);
mysqli_stmt_close($compStmt);

if (!$lomba) {
    header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Competition not found.") . "&msg_type=error");
    exit();
}

$title   = $lomba['title'];
$chat_id = $lomba['telegram_chat_id'];
$invoice_id = $lomba['xendit_invoice_id'];
$pay_status = strtolower($lomba['payment_status']);
$sub_status = strtolower($lomba['submission_status']);

if ($sub_status !== 'pending_review' || $pay_status !== 'paid') {
    header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Competition is not eligible for review (either unpaid or already reviewed).") . "&msg_type=error");
    exit();
}

// Get configured submission fee for refund amount
$submission_fee = 20000;
$fee_stmt = mysqli_prepare($koneksi, "SELECT value FROM settings WHERE `key` = 'submission_fee' LIMIT 1");
if ($fee_stmt) {
    mysqli_stmt_execute($fee_stmt);
    $fee_res = mysqli_stmt_get_result($fee_stmt);
    if ($fee_row = mysqli_fetch_assoc($fee_res)) {
        $submission_fee = (int)$fee_row['value'];
    }
    mysqli_stmt_close($fee_stmt);
}

if ($action === 'approve') {
    // ── APPROVED ──
    $upQuery = "
        UPDATE competitions 
        SET approval_status = 'approved', submission_status = 'published', reviewed_by = ?, published_at = NOW() 
        WHERE id = ?
    ";
    $upStmt = mysqli_prepare($koneksi, $upQuery);
    if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "ii", $admin_id, $comp_id);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);

        // Notify submitter via Telegram
        notifyApproved($chat_id, $title);

        // ─── TELEGRAM BROADCAST (background exec) ─────────────────────────────
        // Spawn a separate PHP CLI process so the broadcast never blocks the HTTP
        // response. exec() detaches immediately; redirect happens right after.
        $php_bin = '/opt/lampp/bin/php';
        if (!file_exists($php_bin) || !is_executable($php_bin)) {
            $php_bin = 'php'; // Fallback to system PATH
        }
        $script    = escapeshellarg(dirname(__DIR__) . '/scripts/broadcast-new-competition.php');
        $comp_arg  = escapeshellarg((string)$comp_id);
        $log_file  = escapeshellarg(dirname(__DIR__) . '/logs/broadcast.log');

        // Ensure log directory exists
        $log_dir = dirname(__DIR__) . '/logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        // Fire and forget — redirect stdin/stdout/stderr, append &
        exec("{$php_bin} {$script} {$comp_arg} >> {$log_file} 2>&1 &");

        header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Competition successfully approved and published!") . "&msg_type=success");
        exit();
    }
} elseif ($action === 'reject') {
    // ── REJECTED ──
    $note = trim($_POST['note'] ?? '');
    if (empty($note)) {
        header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Rejection note is required.") . "&msg_type=error");
        exit();
    }

    // Step 1: Update competition database records to 'rejected'
    $upQuery = "
        UPDATE competitions 
        SET approval_status = 'rejected', submission_status = 'rejected', reviewed_by = ?, review_note = ? 
        WHERE id = ?
    ";
    $upStmt = mysqli_prepare($koneksi, $upQuery);
    if (!$upStmt) {
        header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Failed to update status in database.") . "&msg_type=error");
        exit();
    }
    mysqli_stmt_bind_param($upStmt, "isi", $admin_id, $note, $comp_id);
    mysqli_stmt_execute($upStmt);
    mysqli_stmt_close($upStmt);

    // Notify submitter about rejection
    notifyRejected($chat_id, $title, $note);

    // Step 2: Trigger automated Xendit Refund
    if (!empty($invoice_id)) {
        $ref_id = "ref_" . $comp_id . "_" . time();
        $refund = refundXenditInvoice($invoice_id, $submission_fee, 'CANCELLATION', $ref_id);

        if ($refund && isset($refund['status'])) {
            $ref_status = strtolower($refund['status']); // e.g. PENDING, SUCCEEDED, FAILED
            $refund_db_status = 'pending';
            if ($ref_status === 'succeeded') {
                $refund_db_status = 'succeeded';
            } elseif ($ref_status === 'failed') {
                $refund_db_status = 'failed';
            }

            // Update refund details in database
            $refQuery = "UPDATE competitions SET payment_status = 'refunded', refund_status = ?, refunded_at = NOW() WHERE id = ?";
            $refStmt = mysqli_prepare($koneksi, $refQuery);
            if ($refStmt) {
                mysqli_stmt_bind_param($refStmt, "si", $refund_db_status, $comp_id);
                mysqli_stmt_execute($refStmt);
                mysqli_stmt_close($refStmt);
            }

            // Send Telegram Notification about refund success
            notifyRefundSuccess($chat_id, $title, $submission_fee);

            header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Competition rejected. Automated refund processed via Xendit.") . "&msg_type=success");
            exit();
        } else {
            // Refund request call failed
            $refQuery = "UPDATE competitions SET refund_status = 'failed' WHERE id = ?";
            $refStmt = mysqli_prepare($koneksi, $refQuery);
            if ($refStmt) {
                mysqli_stmt_bind_param($refStmt, "i", $comp_id);
                mysqli_stmt_execute($refStmt);
                mysqli_stmt_close($refStmt);
            }

            error_log("[Xendit Refund Error] Failed to initiate refund for competition ID: " . $comp_id);
            header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Competition rejected, but automated Xendit refund failed. Please process refund manually.") . "&msg_type=warning");
            exit();
        }
    } else {
        header("Location: ../admin/dashboard.php?tab=review-lomba&msg=" . urlencode("Competition rejected. No Xendit invoice was linked to this submission.") . "&msg_type=warning");
        exit();
    }
}
