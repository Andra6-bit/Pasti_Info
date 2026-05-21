<?php
session_start();
include "../config/koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php");
    exit();
}

$username         = trim($_POST['username'] ?? '');
$email            = trim($_POST['email'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validasi kosong
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    header("Location: auth.php?tab=register&error=" . urlencode("Semua field wajib diisi!"));
    exit();
}

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: auth.php?tab=register&error=" . urlencode("Format email tidak valid!") . "&email=" . urlencode($email));
    exit();
}

// Validasi password cocok
if ($password !== $confirm_password) {
    header("Location: auth.php?tab=register&error=" . urlencode("Password dan konfirmasi tidak cocok!") . "&email=" . urlencode($email));
    exit();
}

// Cek username sudah terdaftar
$check_user = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($check_user, "s", $username);
mysqli_stmt_execute($check_user);
mysqli_stmt_store_result($check_user);

if (mysqli_stmt_num_rows($check_user) > 0) {
    header("Location: auth.php?tab=register&error=" . urlencode("Username sudah digunakan!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit();
}
mysqli_stmt_close($check_user);

// Cek email sudah terdaftar
$stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    header("Location: auth.php?tab=register&error=" . urlencode("Email sudah terdaftar!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit();
}
mysqli_stmt_close($stmt);

// Simpan user baru (password plaintext)
$stmt = mysqli_prepare($koneksi, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);

if (mysqli_stmt_execute($stmt)) {
    header("Location: auth.php?success=1");
    exit();
} else {
    header("Location: register.php?error=" . urlencode("Terjadi kesalahan, coba lagi."));
    exit();
}