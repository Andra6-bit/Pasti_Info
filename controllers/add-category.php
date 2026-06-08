<?php
session_start();
include "../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || !isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'Please log in first.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid request method.']);
    exit();
}

$cat_name = trim($_POST['name'] ?? '');
$cat_name = ucwords(strtolower($cat_name));

if (empty($cat_name)) {
    echo json_encode(['ok' => false, 'msg' => 'Category name cannot be empty.']);
    exit();
}

// 1-line reason: Apply role-based category name validation (stricter for standard users, loose character regex for admin).
if ($user_role === 'admin') {
    if (mb_strlen($cat_name) > 40) {
        echo json_encode(['ok' => false, 'msg' => 'Category name is too long (max 40 characters).']);
        exit();
    }
    if (!preg_match('/^[\p{L}\p{N}\s\/\-&+.]+$/u', $cat_name)) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid characters in category name.']);
        exit();
    }
} else {
    if (mb_strlen($cat_name) > 30) {
        echo json_encode(['ok' => false, 'msg' => 'Category name must be maximum 30 characters.']);
        exit();
    }
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $cat_name)) {
        echo json_encode(['ok' => false, 'msg' => 'Category name must only contain letters, numbers, and spaces.']);
        exit();
    }
}

$cat_id = 0;
$check = mysqli_prepare($koneksi, "SELECT id, name FROM categories WHERE LOWER(name) = LOWER(?)");
if ($check) {
    mysqli_stmt_bind_param($check, "s", $cat_name);
    mysqli_stmt_execute($check);
    $check_res = mysqli_stmt_get_result($check);
    if ($existing = mysqli_fetch_assoc($check_res)) {
        $cat_id = (int)$existing['id'];
        $cat_name = $existing['name']; // Use existing name casing
    }
    mysqli_stmt_close($check);
}

if ($cat_id === 0) {
    $ins = mysqli_prepare($koneksi, "INSERT INTO categories (name) VALUES (?)");
    if ($ins) {
        mysqli_stmt_bind_param($ins, "s", $cat_name);
        if (mysqli_stmt_execute($ins)) {
            $cat_id = mysqli_insert_id($koneksi);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Failed to create category.']);
            exit();
        }
        mysqli_stmt_close($ins);
    }
} else {
    // 1-line reason: Return already_exists flag for admin client rendering to auto-select existing categories.
    if ($user_role === 'admin') {
        echo json_encode(['ok' => true, 'id' => $cat_id, 'name' => $cat_name, 'already_exists' => true]);
        exit();
    }
}

// 1-line reason: Only auto-subscribe non-admin users to new categories.
if ($user_role !== 'admin') {
    $sub_ins = mysqli_prepare($koneksi, "INSERT IGNORE INTO user_category_subscriptions (user_id, category_id) VALUES (?, ?)");
    if ($sub_ins) {
        mysqli_stmt_bind_param($sub_ins, "ii", $user_id, $cat_id);
        mysqli_stmt_execute($sub_ins);
        mysqli_stmt_close($sub_ins);
    }
}

echo json_encode(['ok' => true, 'id' => $cat_id, 'name' => $cat_name]);
exit();
