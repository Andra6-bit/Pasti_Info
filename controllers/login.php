<?php
session_start();
include "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // 1-line reason: Remove plaintext password verification fallback to enforce secure password hashing (BCrypt).
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']            = $user['id'];
            $_SESSION['user_username']      = $user['username'];
            $_SESSION['user_role']          = $user['role'];
            unset($_SESSION['guest_start_time']);
            $_SESSION['status']             = "login";
            $_SESSION['last_activity']      = time();

            header("Location: ../pages/home.php");
            exit();
        } else {
            header("Location: ../pages/auth.php?tab=login&error=" . urlencode("Incorrect Username or Password!"));
            exit();
        }
    } else {
        header("Location: ../pages/auth.php?tab=login&error=" . urlencode("Username or Password is not registered!"));
        exit();
    }
}
?>
