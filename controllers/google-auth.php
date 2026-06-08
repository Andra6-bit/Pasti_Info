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
        // 1-line reason: Validate Google ID token claims (iss, aud, exp) to prevent token spoofing or expiration bypass.
        $iss = $payload['iss'] ?? '';
        $aud = $payload['aud'] ?? '';
        $exp = (int)($payload['exp'] ?? 0);
        $allowed_aud = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?: '171421878386-imt8jhr76mglv6dkb9ibrijst01dndn1.apps.googleusercontent.com';
        if (($iss !== 'accounts.google.com' && $iss !== 'https://accounts.google.com') || $aud !== $allowed_aud || $exp < time()) {
            echo "Login Failed!";
            exit();
        }
        $email = $payload['email'];
        $username = $payload['name'];

        // Check if user already exists
        // 1-line reason: Replace raw query with a prepared statement to prevent SQL injection vulnerability.
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);
        
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
            // 1-line reason: Sanitize and truncate the username before database insertion to prevent XSS and column overflow.
            $username = mb_substr(trim($username), 0, 50);
            $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            // 1-line reason: Replace raw query with a prepared statement to prevent SQL injection vulnerability.
            $insertStmt = mysqli_prepare($koneksi, "INSERT INTO users (username, email) VALUES (?, ?)");
            mysqli_stmt_bind_param($insertStmt, "ss", $username, $email);
            $insert = mysqli_stmt_execute($insertStmt);
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
        // UI-GAP fix: Redirect with error param instead of bare echo so the user sees a styled error page.
        header("Location: /Pasti_Info/pages/auth.php?error=google_failed");
        exit();
    }
}
?>
