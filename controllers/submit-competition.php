<?php
session_start();

/**
 * controllers/submit-competition.php
 *
 * Handles regular user paid competition submissions, validates inputs and files,
 * creates a Xendit Invoice, and redirects the user to the payment gateway.
 */

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../pages/auth.php");
    exit();
}

require_once '../config/database.php';             // provides $koneksi
require_once '../helpers/xendit.php';               // provides createXenditInvoice()
require_once '../helpers/telegram-notification.php'; // provides notifyUserTelegram() and wrapper notifications

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/home.php");
    exit();
}

// Referrer handling for modal redirects
$referrer = trim($_POST['referrer'] ?? '');
if (empty($referrer)) {
    $referrer = '../pages/home.php';
}

function redirectWithError($referrer, $error_msg) {
    $query = "open-submit=1&error=" . urlencode($error_msg);
    $url = $referrer;
    if (strpos($url, '?') !== false) {
        $url .= '&' . $query;
    } else {
        $url .= '?' . $query;
    }
    header("Location: " . $url);
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$username = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';

// ── 1. INPUT VALIDATION ──────────────────────────────────────────────────────
$telegram_chat_id = trim($_POST['telegram_chat_id'] ?? '');
$title            = trim($_POST['title'] ?? '');
$format           = trim($_POST['pelaksanaan'] ?? 'Online');
$target_audience  = trim($_POST['target_peserta'] ?? 'Umum');
$registration_fee = max(0, (int)($_POST['biaya'] ?? 0));
$registration_link = trim($_POST['link_pendaftaran'] ?? '');
$description      = trim($_POST['description'] ?? '');
$start_date       = trim($_POST['start_date'] ?? '');
$end_date         = trim($_POST['end_date'] ?? '');

// Simple rate limiting / basic anti spam (limit 1 submission per 10 seconds per user)
if (isset($_SESSION['last_submission_time']) && (time() - $_SESSION['last_submission_time']) < 10) {
    redirectWithError($referrer, "Too many requests. Please wait before submitting again.");
}
$_SESSION['last_submission_time'] = time();

if (empty($telegram_chat_id) || !ctype_digit($telegram_chat_id)) {
    redirectWithError($referrer, "Invalid Telegram Chat ID. Please enter numbers only.");
}

if (empty($title) || empty($registration_link) || empty($description) || empty($start_date) || empty($end_date)) {
    redirectWithError($referrer, "Please fill in all required fields.");
}

if (!filter_var($registration_link, FILTER_VALIDATE_URL)) {
    redirectWithError($referrer, "Invalid registration link format. Use: https://...");
}

if (!in_array($format, ['Online', 'Offline', 'Hybrid'])) {
    $format = 'Online';
}

$start_ts = strtotime($start_date);
$end_ts   = strtotime($end_date);
if (!$start_ts || !$end_ts || $start_ts > $end_ts) {
    redirectWithError($referrer, "Invalid start or end date range.");
}
$date_range = date('Y-m-d', $start_ts) . ',' . date('Y-m-d', $end_ts);

// Validate Categories
$raw_categories = $_POST['categories'] ?? [];
if (!is_array($raw_categories) || empty($raw_categories)) {
    redirectWithError($referrer, "Please select at least one category.");
}
$categories_ids = array_map('intval', $raw_categories);
$categories_ids = array_filter($categories_ids, function($v) { return $v > 0; });
$categories_ids = array_unique($categories_ids);

if (empty($categories_ids)) {
    redirectWithError($referrer, "Please select at least one valid category.");
}

// Fetch category names for flat category text field
$cat_names = [];
foreach ($categories_ids as $cat_id) {
    $cs = mysqli_prepare($koneksi, "SELECT name FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($cs, "i", $cat_id);
    mysqli_stmt_execute($cs);
    $cr = mysqli_stmt_get_result($cs);
    if ($row = mysqli_fetch_assoc($cr)) {
        $cat_names[] = $row['name'];
    }
    mysqli_stmt_close($cs);
}
$category_text = implode(', ', $cat_names);

// ── 2. SECURE FILE UPLOAD VALIDATION ─────────────────────────────────────────
if (empty($_FILES['image']['name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    redirectWithError($referrer, "Competition poster image is required.");
}

$file_name = $_FILES['image']['name'];
$file_size = $_FILES['image']['size'];
$file_tmp  = $_FILES['image']['tmp_name'];
$ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
    redirectWithError($referrer, "Image format not supported (JPG, PNG, WebP, GIF).");
}

if ($file_size > 3 * 1024 * 1024) {
    redirectWithError($referrer, "Maximum image size is 3MB.");
}

// Additional MIME check for security
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_tmp);
finfo_close($finfo);
if (strpos($mime_type, 'image/') !== 0) {
    redirectWithError($referrer, "Uploaded file is not a valid image.");
}

$new_img_name = uniqid('comp_') . '.' . $ext;
$upload_dir   = "../assets/images/";

if (!move_uploaded_file($file_tmp, $upload_dir . $new_img_name)) {
    redirectWithError($referrer, "Failed to upload poster image.");
}

// ── 3. UPDATE USER TELEGRAM CHAT ID IN PROFILE ──────────────────────────────
$upUser = mysqli_prepare($koneksi, "UPDATE users SET telegram_chat_id = ? WHERE id = ?");
if ($upUser) {
    mysqli_stmt_bind_param($upUser, "si", $telegram_chat_id, $user_id);
    mysqli_stmt_execute($upUser);
    mysqli_stmt_close($upUser);
}

// Get payer email
$email = '';
$email_stmt = mysqli_prepare($koneksi, "SELECT email FROM users WHERE id = ? LIMIT 1");
if ($email_stmt) {
    mysqli_stmt_bind_param($email_stmt, "i", $user_id);
    mysqli_stmt_execute($email_stmt);
    $email_res = mysqli_stmt_get_result($email_stmt);
    if ($email_row = mysqli_fetch_assoc($email_res)) {
        $email = $email_row['email'];
    }
    mysqli_stmt_close($email_stmt);
}

// ── 4. CREATE COMPETITION ENTRY (UNPAID) ──────────────────────────────────────
// Generate a 10-digit unique uid
do {
    $uid = str_pad(random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
    $uid_check = mysqli_prepare($koneksi, "SELECT id FROM competitions WHERE uid = ?");
    mysqli_stmt_bind_param($uid_check, "s", $uid);
    mysqli_stmt_execute($uid_check);
    $uid_exists = mysqli_num_rows(mysqli_stmt_get_result($uid_check)) > 0;
    mysqli_stmt_close($uid_check);
} while ($uid_exists);

$ins_query = "
INSERT INTO competitions (
    uid, title, image, format, date_range, target_audience, registration_fee, 
    category, description, registration_link, user_id, telegram_chat_id, 
    payment_status, approval_status, submission_status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', 'pending', 'unpaid')
";

$stmt = mysqli_prepare($koneksi, $ins_query);
if (!$stmt) {
    unlink($upload_dir . $new_img_name); // Clean up uploaded image
    redirectWithError($referrer, "Database error. Please try again.");
}

mysqli_stmt_bind_param(
    $stmt, "ssssssisssis", 
    $uid, $title, $new_img_name, $format, $date_range, $target_audience, $registration_fee,
    $category_text, $description, $registration_link, $user_id, $telegram_chat_id
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    unlink($upload_dir . $new_img_name);
    redirectWithError($referrer, "Failed to save competition details.");
}

$new_comp_id = mysqli_insert_id($koneksi);
mysqli_stmt_close($stmt);

// Insert categories pivot mapping
foreach ($categories_ids as $cat_id) {
    $ins_cat = mysqli_prepare($koneksi, "INSERT IGNORE INTO competition_categories (competition_id, category_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($ins_cat, "ii", $new_comp_id, $cat_id);
    mysqli_stmt_execute($ins_cat);
    mysqli_stmt_close($ins_cat);
}

// ── 5. INTEGRATE XENDIT INVOICE ──────────────────────────────────────────────
// Get configured submission fee
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

$external_id = "comp_" . $new_comp_id . "_" . time();
$description = "Pasti_Info - Submission Fee: " . $title;
$expiry = 86400; // 24 hours in seconds

$invoice = createXenditInvoice($external_id, $submission_fee, $description, $email, $expiry);

if ($invoice && isset($invoice['id']) && isset($invoice['invoice_url'])) {
    $invoice_id  = $invoice['id'];
    $invoice_url = $invoice['invoice_url'];

    // Update database with invoice details
    $upQuery = "UPDATE competitions SET xendit_invoice_id = ?, xendit_invoice_url = ? WHERE id = ?";
    $upStmt = mysqli_prepare($koneksi, $upQuery);
    if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "ssi", $invoice_id, $invoice_url, $new_comp_id);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);
    }

    // Send Telegram Notification about Pending Payment
    $msg = "🛒 <b>INVOICE SUBMISSION BERHASIL DIBUAT!</b>\n\n"
         . "Halo! Lomba baru Anda <b>" . htmlspecialchars($title) . "</b> berhasil disubmit.\n\n"
         . "Silakan lakukan pembayaran sebesar <b>Rp " . number_format($submission_fee, 0, ',', '.') . "</b> dalam waktu 24 jam untuk melanjutkannya ke proses review admin:\n\n"
         . "🔗 <b>Link Pembayaran:</b> <a href=\"" . htmlspecialchars($invoice_url) . "\">Bayar Sekarang</a>\n"
         . "🧾 <b>Invoice ID:</b> <code>" . htmlspecialchars($invoice_id) . "</code>";
    notifyUserTelegram($telegram_chat_id, $msg);

    // Redirect user to the Xendit payment link
    header("Location: " . $invoice_url);
    exit();
} else {
    // Rollback DB entry and files if Xendit fails
    mysqli_query($koneksi, "DELETE FROM competition_categories WHERE competition_id = " . $new_comp_id);
    mysqli_query($koneksi, "DELETE FROM competitions WHERE id = " . $new_comp_id);
    unlink($upload_dir . $new_img_name);

    error_log("[Xendit Submission Error] Invoice creation failed for competition ID: " . $new_comp_id);
    redirectWithError($referrer, "Failed to initialize payment gateway. Please try again.");
}
