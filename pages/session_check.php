<?php
// session_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$guest_timeout = 60; // Tamu: 1 menit

// 1. Jika user SUDAH login — tidak ada timeout otomatis, logout hanya manual
if (isset($_SESSION['status']) && $_SESSION['status'] === "login") {
    $_SESSION['last_activity'] = time(); // update aktivitas saja, tanpa cek expired
} 
// 2. Jika user ADALAH TAMU (belum login)
else {
    if (!isset($_SESSION['guest_start_time'])) {
        $_SESSION['guest_start_time'] = time();
    }

    $time_spent = time() - $_SESSION['guest_start_time'];

    if ($time_spent > $guest_timeout) {
        header("Location: auth.php?error=" . urlencode("Waktu mengintip habis! Silakan login untuk lanjut mencari lomba."));
        exit();
    }
}