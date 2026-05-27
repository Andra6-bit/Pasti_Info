<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || !isset($_SESSION['user_id'])) {
    header("Location: ../pages/auth.php?error=" . urlencode("Please login first."));
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($koneksi, "SELECT role, telegram_chat_id FROM users WHERE id = ?");
if (!$stmt) {
    header("Location: ../pages/profile.php?tab=pengaturan&error=" . urlencode("Database error occurred."));
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: ../pages/auth.php?error=" . urlencode("User not found."));
    exit();
}

$current_role = $user['role'];
$telegram_chat_id = $user['telegram_chat_id'];
$redirect_url = ($current_role === 'admin') ? "../admin/dashboard.php?tab=settings" : "../pages/profile.php?tab=settings";



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_categories = $_POST['categories'] ?? [];
    if (!is_array($raw_categories)) {
        $raw_categories = [];
    }
    
    $category_ids = array_map('intval', $raw_categories);
    $category_ids = array_filter($category_ids, function($id) { return $id > 0; });
    $category_ids = array_unique($category_ids);

    $del_stmt = mysqli_prepare($koneksi, "DELETE FROM user_category_subscriptions WHERE user_id = ?");
    if (!$del_stmt) {
        header("Location: " . $redirect_url . "&error=" . urlencode("Failed to update subscriptions."));
        exit();
    }
    mysqli_stmt_bind_param($del_stmt, "i", $user_id);
    mysqli_stmt_execute($del_stmt);
    mysqli_stmt_close($del_stmt);

    $success_count = 0;
    if (!empty($category_ids)) {
        $ins_stmt = mysqli_prepare($koneksi, "INSERT IGNORE INTO user_category_subscriptions (user_id, category_id) VALUES (?, ?)");
        if ($ins_stmt) {
            foreach ($category_ids as $cat_id) {
                mysqli_stmt_bind_param($ins_stmt, "ii", $user_id, $cat_id);
                mysqli_stmt_execute($ins_stmt);
                $success_count++;
            }
            mysqli_stmt_close($ins_stmt);
        }
    }

    if (isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'msg' => 'Category subscriptions updated successfully!']);
        exit();
    }

    header("Location: " . $redirect_url . "&success=" . urlencode("Category subscriptions updated successfully!"));
    exit();
} else {
    header("Location: " . $redirect_url);
    exit();
}

