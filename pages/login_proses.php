<?php
session_start();
include "../config/koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['status']     = "login";

            header("Location: landing.php");
            exit();
        } else {
            echo "<script>alert('Email atau Password salah!'); window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('Email atau Password salah!'); window.location='login.php';</script>";
    }
}
?>