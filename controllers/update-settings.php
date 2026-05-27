<?php
session_start();

/**
 * controllers/update-settings.php
 *
 * Updates admin configuration settings (such as submission fee) in the database.
 */

$session_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? '';
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || $session_user !== 'admin') {
    header("Location: ../pages/auth.php?error=" . urlencode("Access Denied! Admin only."));
    exit();
}

require_once '../config/database.php'; // provides $koneksi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fee = max(0, (int)($_POST['submission_fee'] ?? 20000));

    $stmt = mysqli_prepare($koneksi, "INSERT INTO settings (`key`, `value`) VALUES ('submission_fee', ?) ON DUPLICATE KEY UPDATE `value` = ?");
    if ($stmt) {
        $fee_str = (string)$fee;
        mysqli_stmt_bind_param($stmt, "ss", $fee_str, $fee_str);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: ../admin/dashboard.php?tab=pengaturan&success=" . urlencode("Settings updated successfully!"));
            exit();
        }
        mysqli_stmt_close($stmt);
    }
    
    header("Location: ../admin/dashboard.php?tab=pengaturan&error=" . urlencode("Failed to update settings."));
    exit();
}
