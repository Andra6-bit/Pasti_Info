<?php
session_start();
include "../config/koneksi.php";

if (isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    
    // Verifikasi token ke API Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = file_get_contents($url);
    $payload = json_decode($response, true);

    if ($payload && isset($payload['email'])) {
        $email = $payload['email'];
        $username = $payload['name']; // Mengambil nama sebagai username sesuai permintaan Tuan

        // Cek apakah user sudah ada di database
        $query = mysqli_query($koneksi, "SELECT * FROM users WHERE email = '$email'");
        
        if (mysqli_num_rows($query) > 0) {
            // User sudah ada, langsung login
            $user = mysqli_fetch_assoc($query);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            unset($_SESSION['guest_start_time']);
            $_SESSION['status'] = "login";
            $_SESSION['last_activity'] = time();
            header("Location: ./landing.php");
            exit();
        } else {
            // User baru, daftarkan dulu ke database
            $insert = mysqli_query($koneksi, "INSERT INTO users (username, email) VALUES ('$username', '$email')");
            if ($insert) {
                $_SESSION['user_id'] = mysqli_insert_id($koneksi);
                $_SESSION['user_username'] = $username;
                unset($_SESSION['guest_start_time']);
                $_SESSION['status'] = "login";
                $_SESSION['last_activity'] = time();
                header("Location: ./landing.php");
                exit();
            }
        }
    } else {
        echo "Login Gagal!";
    }
}
?>