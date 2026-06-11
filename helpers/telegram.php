<?php
// 1-line reason: Helper function to trigger background Telegram broadcast process for a new competition.

function formatTanggalTelegram($date_range) {
    if (empty($date_range)) return '';

    $bulan_indo = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $format_single = function($date_str) use ($bulan_indo) {
        $normalized = trim(str_replace('/', '-', $date_str));
        $time = strtotime($normalized);
        if (!$time) return trim($date_str);
        
        $day = (int)date('d', $time);
        $month = (int)date('m', $time);
        $year = date('Y', $time);
        
        $month_text = $bulan_indo[$month] ?? date('M', $time);
        return "{$day} {$month_text} {$year}";
    };

    if (strpos($date_range, ',') !== false) {
        $parts = explode(',', $date_range);
        if (count($parts) === 2) {
            $t1 = $format_single($parts[0]);
            $t2 = $format_single($parts[1]);
            return ($t1 === $t2) ? $t1 : "{$t1} - {$t2}";
        }
    }

    if (strpos($date_range, '/') !== false && strpos($date_range, '-') !== false) {
        $parts = explode('-', $date_range);
        if (count($parts) === 2) {
            $t1 = $format_single($parts[0]);
            $t2 = $format_single($parts[1]);
            return ($t1 === $t2) ? $t1 : "{$t1} - {$t2}";
        }
    }

    return $format_single($date_range);
}

function runTelegramBroadcastInline($comp_id) {
    global $koneksi;
    if (!isset($koneksi)) {
        include_once __DIR__ . '/../config/database.php';
    }
    if (!isset($koneksi)) {
        error_log("[Telegram Broadcast Inline Error] Database connection not found.");
        return;
    }

    $stmt = mysqli_prepare($koneksi, 'SELECT id, title, format, date_range, registration_fee FROM competitions WHERE id = ?');
    if (!$stmt) {
        error_log('[Telegram Broadcast Inline] Failed to prepare competition query: ' . mysqli_error($koneksi));
        return;
    }
    mysqli_stmt_bind_param($stmt, 'i', $comp_id);
    mysqli_stmt_execute($stmt);
    $lomba = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$lomba) {
        error_log("[Telegram Broadcast Inline] Competition not found: id={$comp_id}");
        return;
    }

    $cat_stmt = mysqli_prepare($koneksi, 'SELECT category_id FROM competition_categories WHERE competition_id = ?');
    if (!$cat_stmt) {
        error_log('[Telegram Broadcast Inline] Failed to prepare categories query: ' . mysqli_error($koneksi));
        return;
    }
    mysqli_stmt_bind_param($cat_stmt, 'i', $comp_id);
    mysqli_stmt_execute($cat_stmt);
    $cat_res = mysqli_stmt_get_result($cat_stmt);
    $category_ids = [];
    while ($row = mysqli_fetch_assoc($cat_res)) {
        $category_ids[] = (int)$row['category_id'];
    }
    mysqli_stmt_close($cat_stmt);

    if (empty($category_ids)) {
        return;
    }

    $chat_ids = [];
    $sub_query = 'SELECT u.telegram_chat_id
                  FROM users u
                  JOIN user_category_subscriptions ucs ON u.id = ucs.user_id
                  WHERE ucs.category_id = ?
                    AND u.telegram_chat_id IS NOT NULL
                    AND u.telegram_chat_id <> \'\'';

    foreach ($category_ids as $cat_id) {
        $sub_stmt = mysqli_prepare($koneksi, $sub_query);
        if ($sub_stmt) {
            mysqli_stmt_bind_param($sub_stmt, 'i', $cat_id);
            mysqli_stmt_execute($sub_stmt);
            $sub_res = mysqli_stmt_get_result($sub_stmt);
            while ($row = mysqli_fetch_assoc($sub_res)) {
                $chat_ids[] = $row['telegram_chat_id'];
            }
            mysqli_stmt_close($sub_stmt);
        }
    }

    $chat_ids = array_unique($chat_ids);
    if (empty($chat_ids)) {
        return;
    }

    $biaya = (int)($lomba['registration_fee'] ?? 0);
    $biaya_text = ($biaya === 0) ? 'Gratis' : 'Rp ' . number_format($biaya, 0, ',', '.');
    $app_url = rtrim($_ENV['APP_URL'] ?? 'http://localhost/Pasti_Info', '/');

    $formatted_date = formatTanggalTelegram($lomba['date_range'] ?? '');

    $pesan = "🚀 <b>INFO LOMBA BARU UNTUKMU!</b>\n\n"
           . "Halo! Ada kompetisi baru di kategori yang kamu ikuti:\n\n"
           . "🏆 <b>Judul:</b> "      . htmlspecialchars($lomba['title'] ?? '', ENT_XML1) . "\n"
           . "📍 <b>Pelaksanaan:</b> " . htmlspecialchars($lomba['format'] ?? 'Online', ENT_XML1) . "\n"
           . "📅 <b>Deadline:</b> "    . htmlspecialchars($formatted_date, ENT_XML1) . "\n"
           . "💰 <b>Biaya:</b> "       . htmlspecialchars($biaya_text, ENT_XML1) . "\n\n"
           . "Cek detailnya: <a href=\"" . $app_url . '/pages/competition-details.php?id=' . (int)$lomba['id'] . "\">Lihat Detail</a>";

    foreach ($chat_ids as $chat_id) {
        kirimNotifikasiTelegram($chat_id, $pesan);
    }
}

function sendTelegramBroadcast($comp_id) {
    $php_bin = '/opt/lampp/bin/php';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $php_bin = 'C:\\xampp\\php\\php.exe';
    }
    if (!file_exists($php_bin) || !is_executable($php_bin)) {
        $php_bin = 'php'; // Fallback to system PATH
    }
    $script    = escapeshellarg(dirname(__DIR__) . '/scripts/broadcast-new-competition.php');
    $comp_arg  = escapeshellarg((string)$comp_id);
    $log_file  = escapeshellarg(dirname(__DIR__) . '/logs/broadcast.log');

    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $run_background_success = false;

    try {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (function_exists('popen')) {
                pclose(popen("start /B \"\" " . escapeshellcmd("{$php_bin} {$script} {$comp_arg}") . " > {$log_file} 2>&1", "r"));
                $run_background_success = true;
            }
        } else {
            if (function_exists('exec')) {
                exec("{$php_bin} {$script} {$comp_arg} >> {$log_file} 2>&1 &");
                $run_background_success = true;
            }
        }
    } catch (Throwable $e) {
        error_log("[Telegram Broadcast Error] Shell execution failed: " . $e->getMessage());
    }

    if (!$run_background_success) {
        runTelegramBroadcastInline($comp_id);
    }
}

/**
 * Sends a Telegram notification message using Telegram Bot API.
 *
 * @param string $chat_id The target Telegram Chat ID.
 * @param string $pesan The message text to be sent (HTML supported).
 * @return bool True if the message was sent successfully, false otherwise.
 */
function kirimNotifikasiTelegram(string $chat_id, string $pesan): bool
{
    $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';

    if (empty($token)) {
        error_log("[Telegram Error] chat_id={$chat_id} | response=Telegram Bot Token not configured in \$_ENV.");
        return false;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $payload = [
        'chat_id'    => $chat_id,
        'text'       => $pesan,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log("[Telegram Error] chat_id={$chat_id} | response=cURL Error: {$curl_error}");
        return false;
    }

    $result = json_decode($response, true);
    if (isset($result['ok']) && $result['ok'] === true) {
        return true;
    }

    error_log("[Telegram Error] chat_id={$chat_id} | response={$response}");
    return false;
}

