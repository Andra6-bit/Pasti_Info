<?php
// Pastikan session dimulai jika session_check.php belum memulainya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../config/koneksi.php";

header('Content-Type: application/json');

// 1. Cek Login secara mandiri agar tidak terjadi redirect HTML dari session_check
if (!isset($_SESSION['status']) || $_SESSION['status'] !== "login") {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Silahkan login terlebih dahulu']);
    exit();
}

// 2. Ambil username dari session
$username = $_SESSION['username'] ?? $_SESSION['user_username'] ?? '';

// 3. Cari id user dari tabel `users`
$user_query = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($user_query, "s", $username);
mysqli_stmt_execute($user_query);
$user_res = mysqli_stmt_get_result($user_query);
$user = mysqli_fetch_assoc($user_res);

if (!$user) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan di database']);
    exit();
}

$user_id = $user['id'];
$competition_id = intval($_POST['competition_id'] ?? 0);

if ($competition_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Kompetisi tidak valid']);
    exit();
}

// 4. Proses Cek / Tambah / Hapus Bookmark
$check_query = mysqli_prepare($koneksi, "SELECT id FROM bookmarks WHERE user_id = ? AND competition_id = ?");
mysqli_stmt_bind_param($check_query, "ii", $user_id, $competition_id);
mysqli_stmt_execute($check_query);
$check_res = mysqli_stmt_get_result($check_query);

if (mysqli_num_rows($check_res) > 0) {
    // Unbookmark
    $delete_query = mysqli_prepare($koneksi, "DELETE FROM bookmarks WHERE user_id = ? AND competition_id = ?");
    mysqli_stmt_bind_param($delete_query, "ii", $user_id, $competition_id);
    mysqli_stmt_execute($delete_query);
    echo json_encode(['status' => 'success', 'action' => 'removed', 'message' => 'Bookmark dihapus']);
} else {
    // Bookmark
    $insert_query = mysqli_prepare($koneksi, "INSERT INTO bookmarks (user_id, competition_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($insert_query, "ii", $user_id, $competition_id);
    mysqli_stmt_execute($insert_query);
    echo json_encode(['status' => 'success', 'action' => 'added', 'message' => 'Lomba berhasil disimpan']);
}
exit();