<?php
session_start();
include "../config/database.php";

if (isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    
    // Verify token with Google API
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = file_get_contents($url);
    $payload = json_decode($response, true);

    if ($payload && isset($payload['email'])) {
        $email = $payload['email'];
        $username = $payload['name'];

        // Check if user already exists
        $query = mysqli_query($koneksi, "SELECT * FROM users WHERE email = '$email'");
        
        if (mysqli_num_rows($query) > 0) {
            // User exists, log in
            $user = mysqli_fetch_assoc($query);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            unset($_SESSION['guest_start_time']);
            $_SESSION['status'] = "login";
            $_SESSION['last_activity'] = time();
            header("Location: ../pages/home.php");
            exit();
        } else {
            // New user, register first
            $insert = mysqli_query($koneksi, "INSERT INTO users (username, email) VALUES ('$username', '$email')");
            if ($insert) {
                $_SESSION['user_id'] = mysqli_insert_id($koneksi);
                $_SESSION['user_username'] = $username;
                $_SESSION['user_role'] = 'user';
                unset($_SESSION['guest_start_time']);
                $_SESSION['status'] = "login";
                $_SESSION['last_activity'] = time();
                header("Location: ../pages/home.php");
                exit();
            }
        }
    } else {
        echo "Login Failed!";
    }
}
?>
