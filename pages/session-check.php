<?php
// session-check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$guest_timeout = 60; // Guest timeout: 1 minute

// 1. If user is logged in — no automatic timeout, manual logout only
if (isset($_SESSION['status']) && $_SESSION['status'] === "login") {
    $_SESSION['last_activity'] = time(); // update last activity only
} 
// 2. If user is a guest (not logged in)
else {
    if (!isset($_SESSION['guest_start_time'])) {
        $_SESSION['guest_start_time'] = time();
    }

    $time_spent = time() - $_SESSION['guest_start_time'];

    if ($time_spent > $guest_timeout) {
        header("Location: auth.php?error=" . urlencode("Guest preview time expired! Please login to continue searching for competitions."));
        exit();
    }
}
?>
