<?php
session_start();
include "../config/koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php");
    exit();
}

$email            = trim($_POST['email'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validasi kosong
if (empty($email) || empty($password) || empty($confirm_password)) {
    header("Location: register.php?error=" . urlencode("Semua field wajib diisi!"));
    exit();
}

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=" . urlencode("Format email tidak valid!") . "&email=" . urlencode($email));
    exit();
}

// Validasi password cocok
if ($password !== $confirm_password) {
    header("Location: register.php?error=" . urlencode("Password dan konfirmasi tidak cocok!") . "&email=" . urlencode($email));
    exit();
}

// Cek email sudah terdaftar
$stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    header("Location: register.php?error=" . urlencode("Email sudah terdaftar!") . "&email=" . urlencode($email));
    exit();
}
mysqli_stmt_close($stmt);

// Simpan user baru (password plaintext)
$stmt = mysqli_prepare($koneksi, "INSERT INTO users (email, password) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, "ss", $email, $password);

if (mysqli_stmt_execute($stmt)) {
    header("Location: login.php?success=1");
    exit();
} else {
    header("Location: register.php?error=" . urlencode("Terjadi kesalahan, coba lagi."));
    exit();
}