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

    // Fire and forget
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        pclose(popen("start /B \"\" " . escapeshellcmd("{$php_bin} {$script} {$comp_arg}") . " > {$log_file} 2>&1", "r"));
    } else {
        exec("{$php_bin} {$script} {$comp_arg} >> {$log_file} 2>&1 &");
    }
}
