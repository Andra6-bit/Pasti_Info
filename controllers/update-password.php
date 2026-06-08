<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || !isset($_SESSION['user_id'])) {
    header("Location: ../pages/auth.php?error=" . urlencode("Please login first."));
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($koneksi, "SELECT role, password FROM users WHERE id = ?");
if (!$stmt) {
    header("Location: ../pages/home.php?error=" . urlencode("Database error occurred."));
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);

if (!$user) {
    header("Location: ../pages/auth.php?error=" . urlencode("User not found."));
    exit();
}

$current_role = $user['role'];
$hashed_password = $user['password'];

$redirect_url = ($current_role === 'admin') ? "../admin/dashboard.php?tab=pengaturan" : "../pages/profile.php?tab=pengaturan";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        header("Location: " . $redirect_url . "&error=" . urlencode("All password fields are required!"));
        exit();
    }

    // 1-line reason: Remove plaintext password verification fallback to enforce secure password hashing (BCrypt).
    if (!password_verify($old_password, $hashed_password)) {
        header("Location: " . $redirect_url . "&error=" . urlencode("Incorrect old password!"));
        exit();
    }

    if (strlen($new_password) < 8) {
        header("Location: " . $redirect_url . "&error=" . urlencode("New password must be at least 8 characters long!"));
        exit();
    }

    if ($new_password !== $confirm_password) {
        header("Location: " . $redirect_url . "&error=" . urlencode("New password confirmation does not match!"));
        exit();
    }

    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $update_stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id = ?");
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "si", $new_hashed_password, $user_id);
        if (mysqli_stmt_execute($update_stmt)) {
            header("Location: " . $redirect_url . "&success=" . urlencode("Password successfully updated!"));
            exit();
        } else {
            header("Location: " . $redirect_url . "&error=" . urlencode("Failed to update password in database."));
            exit();
        }
    } else {
        header("Location: " . $redirect_url . "&error=" . urlencode("System error occurred."));
        exit();
    }
} else {
    header("Location: " . $redirect_url);
    exit();
}
?>
