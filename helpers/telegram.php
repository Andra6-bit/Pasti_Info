<?php
// 1-line reason: Helper function to trigger background Telegram broadcast process for a new competition.
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

    // Ensure log directory exists
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    // Fire and forget (safely wrapped to prevent crashes if shell functions are disabled)
    try {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (function_exists('popen')) {
                pclose(popen("start /B \"\" " . escapeshellcmd("{$php_bin} {$script} {$comp_arg}") . " > {$log_file} 2>&1", "r"));
            } else {
                error_log("[Telegram Broadcast Warning] popen() is disabled on this server.");
            }
        } else {
            if (function_exists('exec')) {
                exec("{$php_bin} {$script} {$comp_arg} >> {$log_file} 2>&1 &");
            } else {
                error_log("[Telegram Broadcast Warning] exec() is disabled on this server.");
            }
        }
    } catch (Throwable $e) {
        error_log("[Telegram Broadcast Error] Shell execution failed: " . $e->getMessage());
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

