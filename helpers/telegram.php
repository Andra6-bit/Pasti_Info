<?php

/**
 * helpers/telegram.php
 *
 * Utility to send notification messages via Telegram Bot API using cURL.
 * Place this file in: /helpers/telegram.php
 */

/**
 * Sends a Telegram notification message using Telegram Bot API.
 *
 * @param string $chat_id The target Telegram Chat ID.
 * @param string $pesan The message text to be sent (Markdown supported).
 * @return bool True if the message was sent successfully, false otherwise.
 */
function kirimNotifikasiTelegram(string $chat_id, string $pesan): bool
{
    // Retrieve Telegram Bot Token from environment variable
    $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';

    if (empty($token)) {
        error_log("[Telegram Error] chat_id={$chat_id} | response=Telegram Bot Token not configured in \$_ENV.");
        return false;
    }

    // Set target API URL
    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    // Setup POST request payload
    // Use HTML parse_mode — safer than Markdown v1 which misinterprets
    // underscores in URLs and titles as italic markers.
    $payload = [
        'chat_id'    => $chat_id,
        'text'       => $pesan,
        'parse_mode' => 'HTML'
    ];

    // Initialize cURL session
    $ch = curl_init();

    // Configure cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    
    // Disable SSL verification for localhost XAMPP compatibility
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Set response timeout limit to 10 seconds
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Execute the request
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);

    // Close the cURL session
    curl_close($ch);

    // Handle cURL level execution failures
    if ($response === false) {
        error_log("[Telegram Error] chat_id={$chat_id} | response=cURL Error: {$curl_error}");
        return false;
    }

    // Parse the JSON response
    $result = json_decode($response, true);

    // Verify response status from Telegram API
    if (isset($result['ok']) && $result['ok'] === true) {
        return true;
    }

    // Log the raw failure response from Telegram API
    error_log("[Telegram Error] chat_id={$chat_id} | response={$response}");
    return false;
}

/**
 * Formats competition date range into a readable Indonesian format.
 * E.g. "2026-05-23,2026-06-23" becomes "23 Mei 2026 - 23 Juni 2026"
 *
 * @param string $date_range The raw date range from the database.
 * @return string The formatted date range.
 */
function formatTanggalTelegram(string $date_range): string
{
    $date_range = trim($date_range);
    if (empty($date_range)) {
        return '';
    }

    $bulanIndo = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des'
    ];

    $formatSingle = function (string $dateStr) use ($bulanIndo): string {
        $ts = strtotime(trim($dateStr));
        if (!$ts) {
            return $dateStr;
        }
        $d = date('j', $ts);
        $m = (int)date('n', $ts);
        $y = date('Y', $ts);
        return $d . ' ' . ($bulanIndo[$m] ?? date('M', $ts)) . ' ' . $y;
    };

    // If date range is stored as "YYYY-MM-DD,YYYY-MM-DD"
    if (strpos($date_range, ',') !== false) {
        $parts = explode(',', $date_range, 2);
        $t1 = $formatSingle($parts[0]);
        $t2 = $formatSingle($parts[1]);
        if ($t1 === $t2) {
            return $t1;
        }
        return $t1 . ' - ' . $t2;
    }

    // If date range is stored with slash "/" or hyphen "-"
    if (strpos($date_range, '/') !== false) {
        $parts = explode('-', $date_range);
        if (count($parts) === 2) {
            $t1 = $formatSingle(str_replace('/', '-', $parts[0]));
            $t2 = $formatSingle(str_replace('/', '-', $parts[1]));
            if ($t1 === $t2) {
                return $t1;
            }
            return $t1 . ' - ' . $t2;
        }
    }

    return $formatSingle($date_range);
}

/**
 * Broadcasts notification about a new competition to all subscribed Telegram users.
 *
 * @param mysqli $koneksi The database connection.
 * @param int $competition_id The newly inserted competition ID.
 * @return void
 */
function broadcastLombaBaru(mysqli $koneksi, int $competition_id): void
{
    // LANGKAH 5 — Kirim notifikasi non-blocking:
    // Gunakan ignore_user_abort(true) dan set_time_limit(0)
    ignore_user_abort(true);
    set_time_limit(0);

    // Flush response ke browser lebih dulu sebelum loop pengiriman
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        // Method alternatif untuk web server non-FPM
        // Pastikan header connection close dikirim agar browser tidak menunggu
        header("Connection: close");
        header("Content-Length: 0");
        
        // Bersihkan dan keluarkan output buffer
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }

    // LANGKAH 1 — Ambil data lomba baru:
    // - Gunakan prepared statement untuk mengambil data lomba
    $query = "SELECT id, title, format, date_range, registration_fee FROM competitions WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    if (!$stmt) {
        error_log("[Telegram Broadcast Error] Failed to prepare competition query: " . mysqli_error($koneksi));
        return;
    }
    mysqli_stmt_bind_param($stmt, "i", $competition_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $lomba = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$lomba) {
        error_log("[Telegram Broadcast Error] Competition not found: id={$competition_id}");
        return;
    }

    // LANGKAH 2 — Ambil category_id lomba:
    // - Query SELECT category_id FROM competition_categories WHERE competition_id = ?
    // - Gunakan prepared statement
    // - Simpan semua category_id dalam array
    $categories_ids = [];
    $cat_query = "SELECT category_id FROM competition_categories WHERE competition_id = ?";
    $cat_stmt = mysqli_prepare($koneksi, $cat_query);
    if (!$cat_stmt) {
        error_log("[Telegram Broadcast Error] Failed to prepare competition categories query: " . mysqli_error($koneksi));
        return;
    }
    mysqli_stmt_bind_param($cat_stmt, "i", $competition_id);
    mysqli_stmt_execute($cat_stmt);
    $cat_res = mysqli_stmt_get_result($cat_stmt);
    while ($row = mysqli_fetch_assoc($cat_res)) {
        $categories_ids[] = (int)$row['category_id'];
    }
    mysqli_stmt_close($cat_stmt);

    if (empty($categories_ids)) {
        // Jika tidak ada kategori yang dipilih, tidak ada yang perlu di-broadcast
        return;
    }

    // LANGKAH 3 — Ambil penerima notifikasi:
    // - Untuk setiap category_id, query JOIN ke user_category_subscriptions dan users
    // - Ambil telegram_chat_id yang NOT NULL dan NOT EMPTY
    // - Hindari duplikat penerima (gunakan array_unique pada chat_id)
    // - Gunakan prepared statement
    $chat_ids = [];
    foreach ($categories_ids as $cat_id) {
        $sub_query = "SELECT u.telegram_chat_id 
                      FROM users u
                      JOIN user_category_subscriptions ucs ON u.id = ucs.user_id
                      WHERE ucs.category_id = ? 
                        AND u.telegram_chat_id IS NOT NULL 
                        AND u.telegram_chat_id <> ''";
        $sub_stmt = mysqli_prepare($koneksi, $sub_query);
        if (!$sub_stmt) {
            error_log("[Telegram Broadcast Error] Failed to prepare subscription query: " . mysqli_error($koneksi));
            continue;
        }
        mysqli_stmt_bind_param($sub_stmt, "i", $cat_id);
        mysqli_stmt_execute($sub_stmt);
        $sub_res = mysqli_stmt_get_result($sub_stmt);
        while ($row = mysqli_fetch_assoc($sub_res)) {
            $chat_ids[] = $row['telegram_chat_id'];
        }
        mysqli_stmt_close($sub_stmt);
    }

    $chat_ids = array_unique($chat_ids);

    if (empty($chat_ids)) {
        return; // Tidak ada pelanggan Telegram
    }

    // LANGKAH 4 — Format pesan:
    // Format biaya pendaftaran
    $biaya = (int)($lomba['registration_fee'] ?? 0);
    $biaya_text = ($biaya === 0) ? "Gratis" : "Rp " . number_format($biaya, 0, ',', '.');

    // Dapatkan URL aplikasi
    $app_url = rtrim($_ENV['APP_URL'] ?? 'http://localhost/Pasti_Info', '/');

    // Format pesan menggunakan HTML (lebih aman dari Markdown v1 untuk URL dengan underscore)
    $pesan = "🚀 <b>INFO LOMBA BARU UNTUKMU!</b>\n\n"
           . "Halo! Ada kompetisi baru di kategori yang kamu ikuti:\n\n"
           . "🏆 <b>Judul:</b> "      . htmlspecialchars($lomba['title']     ?? '', ENT_XML1) . "\n"
           . "📍 <b>Pelaksanaan:</b> " . htmlspecialchars($lomba['format']    ?? 'Online', ENT_XML1) . "\n"
           . "📅 <b>Deadline:</b> "    . htmlspecialchars(formatTanggalTelegram($lomba['date_range'] ?? ''), ENT_XML1) . "\n"
           . "💰 <b>Biaya:</b> "       . htmlspecialchars($biaya_text, ENT_XML1) . "\n\n"
           . "Cek detailnya: <a href=\"" . $app_url . "/pages/competition-details.php?id=" . (int)($lomba['id'] ?? 0) . "\">Lihat Detail</a>";

    // Loop kirimNotifikasiTelegram() untuk setiap chat_id
    // Jangan hentikan eksekusi utama jika satu pengiriman gagal
    foreach ($chat_ids as $chat_id) {
        kirimNotifikasiTelegram($chat_id, $pesan);
    }
}

/*
// ─── Contoh Pemanggilan Fungsi ───────────────────────────────────────────────
//
// // Load environment variables first (if not already done by framework/entry point)
// require_once __DIR__ . '/env.php';
// loadEnv(__DIR__ . '/../.env');
//
// // Include this telegram helper
// require_once __DIR__ . '/telegram.php';
//
// $target_chat_id = '123456789'; // Ganti dengan ID Chat Telegram tujuan
// $message = "*Pemberitahuan Baru!*\n\nLomba baru telah ditambahkan ke sistem Pasti_Info.";
//
// if (kirimNotifikasiTelegram($target_chat_id, $message)) {
//     echo "Notifikasi berhasil dikirim!";
// } else {
//     echo "Gagal mengirim notifikasi. Periksa error_log untuk info lebih lanjut.";
// }
*/

