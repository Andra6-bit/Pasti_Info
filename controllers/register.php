<?php
session_start();
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/auth.php?tab=register");
    exit();
}

$username         = trim($_POST['username'] ?? '');
$email            = trim($_POST['email'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    header("Location: ../pages/auth.php?tab=register&error=" . urlencode("All fields are required!"));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../pages/auth.php?tab=register&error=" . urlencode("Invalid email format!") . "&email=" . urlencode($email));
    exit();
}

if ($password !== $confirm_password) {
    header("Location: ../pages/auth.php?tab=register&error=" . urlencode("Passwords do not match!") . "&email=" . urlencode($email));
    exit();
}

$check_user = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($check_user, "s", $username);
mysqli_stmt_execute($check_user);
mysqli_stmt_store_result($check_user);

if (mysqli_stmt_num_rows($check_user) > 0) {
    header("Location: ../pages/auth.php?tab=register&error=" . urlencode("Username is already in use!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit();
}
mysqli_stmt_close($check_user);

$stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    header("Location: ../pages/auth.php?tab=register&error=" . urlencode("Email is already registered!") . "&username=" . urlencode($username) . "&email=" . urlencode($email));
    exit();
}
mysqli_stmt_close($stmt);

// Save new user (plaintext password as original design requirement)
$stmt = mysqli_prepare($koneksi, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password);

if (mysqli_stmt_execute($stmt)) {
    header("Location: ../pages/auth.php?success=1");
    exit();
} else {
    header("Location: ../pages/auth.php?tab=register&error=" . urlencode("An error occurred. Please try again."));
    exit();
}
?>
