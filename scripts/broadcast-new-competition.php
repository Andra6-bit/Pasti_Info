<?php

/**
 * scripts/broadcast-new-competition.php
 *
 * Background CLI script — invoked via exec() from manage-competitions.php.
 * Sends Telegram notifications to all users subscribed to a competition's categories.
 *
 * Usage (CLI only):
 *   php broadcast-new-competition.php <competition_id>
 */

// ── CLI guard ─────────────────────────────────────────────────────────────────
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Access denied.');
}

if (empty($argv[1]) || !ctype_digit((string)$argv[1])) {
    error_log('[Broadcast Script] Invalid or missing competition_id argument.');
    exit(1);
}

$competition_id = (int)$argv[1];

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$root = dirname(__DIR__);

require_once $root . '/helpers/env.php';
loadEnv($root . '/.env');

require_once $root . '/config/database.php';   // provides $koneksi
require_once $root . '/helpers/telegram.php';  // provides kirimNotifikasiTelegram()

// ── STEP 1: Fetch competition data ───────────────────────────────────────────
$stmt = mysqli_prepare($koneksi, 'SELECT id, title, format, date_range, registration_fee FROM competitions WHERE id = ?');
if (!$stmt) {
    error_log('[Broadcast Script] Failed to prepare competition query: ' . mysqli_error($koneksi));
    exit(1);
}
mysqli_stmt_bind_param($stmt, 'i', $competition_id);
mysqli_stmt_execute($stmt);
$lomba = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$lomba) {
    error_log("[Broadcast Script] Competition not found: id={$competition_id}");
    exit(1);
}

// ── STEP 2: Fetch category IDs for this competition ──────────────────────────
$cat_stmt = mysqli_prepare($koneksi, 'SELECT category_id FROM competition_categories WHERE competition_id = ?');
if (!$cat_stmt) {
    error_log('[Broadcast Script] Failed to prepare categories query: ' . mysqli_error($koneksi));
    exit(1);
}
mysqli_stmt_bind_param($cat_stmt, 'i', $competition_id);
mysqli_stmt_execute($cat_stmt);
$cat_res     = mysqli_stmt_get_result($cat_stmt);
$category_ids = [];
while ($row = mysqli_fetch_assoc($cat_res)) {
    $category_ids[] = (int)$row['category_id'];
}
mysqli_stmt_close($cat_stmt);

if (empty($category_ids)) {
    exit(0); // No categories — nothing to broadcast
}

// ── STEP 3: Collect unique telegram_chat_ids of subscribed users ─────────────
$chat_ids = [];
$sub_query = 'SELECT u.telegram_chat_id
              FROM users u
              JOIN user_category_subscriptions ucs ON u.id = ucs.user_id
              WHERE ucs.category_id = ?
                AND u.telegram_chat_id IS NOT NULL
                AND u.telegram_chat_id <> \'\'';

foreach ($category_ids as $cat_id) {
    $sub_stmt = mysqli_prepare($koneksi, $sub_query);
    if (!$sub_stmt) {
        error_log('[Broadcast Script] Failed to prepare subscription query: ' . mysqli_error($koneksi));
        continue;
    }
    mysqli_stmt_bind_param($sub_stmt, 'i', $cat_id);
    mysqli_stmt_execute($sub_stmt);
    $sub_res = mysqli_stmt_get_result($sub_stmt);
    while ($row = mysqli_fetch_assoc($sub_res)) {
        $chat_ids[] = $row['telegram_chat_id'];
    }
    mysqli_stmt_close($sub_stmt);
}

$chat_ids = array_unique($chat_ids);

if (empty($chat_ids)) {
    exit(0); // No eligible subscribers
}

// ── STEP 4: Format notification message ──────────────────────────────────────
$biaya      = (int)($lomba['registration_fee'] ?? 0);
$biaya_text = ($biaya === 0) ? 'Gratis' : 'Rp ' . number_format($biaya, 0, ',', '.');
$app_url    = rtrim($_ENV['APP_URL'] ?? 'http://localhost/Pasti_Info', '/');

// HTML parse_mode avoids Markdown v1 issues with underscores in URLs/titles
$pesan = "🚀 <b>INFO LOMBA BARU UNTUKMU!</b>\n\n"
       . "Halo! Ada kompetisi baru di kategori yang kamu ikuti:\n\n"
       . "🏆 <b>Judul:</b> "      . htmlspecialchars($lomba['title']     ?? '', ENT_XML1) . "\n"
       . "📍 <b>Pelaksanaan:</b> " . htmlspecialchars($lomba['format']    ?? 'Online', ENT_XML1) . "\n"
       . "📅 <b>Deadline:</b> "    . htmlspecialchars(formatTanggalTelegram($lomba['date_range'] ?? ''), ENT_XML1) . "\n"
       . "💰 <b>Biaya:</b> "       . htmlspecialchars($biaya_text, ENT_XML1) . "\n\n"
       . "Cek detailnya: <a href=\"" . $app_url . '/pages/competition-details.php?id=' . (int)$lomba['id'] . "\">Lihat Detail</a>";

// ── STEP 5: Send notifications ────────────────────────────────────────────────
foreach ($chat_ids as $chat_id) {
    kirimNotifikasiTelegram($chat_id, $pesan);
}

exit(0);
