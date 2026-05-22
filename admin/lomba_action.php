<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($_SESSION['user_username'] ?? '') !== 'admin') {
    header("Location: ../pages/auth.php?error=" . urlencode("Akses Ditolak! Khusus Admin."));
    exit();
}
include "../config/koneksi.php";

$msg      = '';
$msg_type = '';

// ── HAPUS ───────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $stmt = mysqli_prepare($koneksi, "SELECT foto FROM competitions WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $del_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $stmt2 = mysqli_prepare($koneksi, "DELETE FROM competitions WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    if (mysqli_stmt_execute($stmt2)) {
        if ($del_row && $del_row['foto'] !== 'default.jpg') {
            $img_path = "../Assets/images/" . $del_row['foto'];
            if (file_exists($img_path)) unlink($img_path);
        }
        $msg = "Kompetisi berhasil dihapus.";
        $msg_type = "success";
    } else {
        $msg = "Gagal menghapus kompetisi.";
        $msg_type = "error";
    }
    
    header("Location: admin.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
    exit();
}

// ── TAMBAH / EDIT (POST) ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action         = $_POST['action'] ?? 'add';
    $post_id        = (int)($_POST['id'] ?? 0);
    $title          = trim($_POST['title'] ?? '');
    $pelaksanaan    = trim($_POST['pelaksanaan'] ?? '');
    $target_peserta = trim($_POST['target_peserta'] ?? 'Umum');
    $biaya          = (int)($_POST['biaya'] ?? 0);

    // 1. PELAKSANAAN (Hanya izinkan Online / Offline)
    if (!in_array($pelaksanaan, ['Online', 'Offline'])) $pelaksanaan = 'Online';

    // 2. TANGGAL (Gabungkan pakai koma untuk disimpan ke 1 kolom database)
    $start_date  = $_POST['start_date'] ?? '';
    $end_date    = $_POST['end_date'] ?? '';
    $date_range  = $start_date . ',' . $end_date;

    // 3. KATEGORI (Ubah jadi Title Case. Cth: "hAcKIng" -> "Hacking")
    $raw_category = trim($_POST['category'] ?? 'Umum');
    $category     = ucwords(strtolower($raw_category));

    $description = trim($_POST['description'] ?? '');

    $image_name = $_POST['old_image'] ?? 'default.jpg';

    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $msg = "Format gambar tidak didukung."; $msg_type = "error";
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $msg = "Ukuran gambar maksimal 3MB."; $msg_type = "error";
        } else {
            $image_name = uniqid('comp_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], "../Assets/images/" . $image_name)) {
                $msg = "Gagal upload gambar."; $msg_type = "error";
                $image_name = $_POST['old_image'] ?? 'default.jpg';
            }
        }
    }

    if (empty($msg)) {
        if ($action === 'add') {
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO competitions (title, foto, pelaksanaan, date_range, target_peserta, biaya, category, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssssiss", $title, $image_name, $pelaksanaan, $date_range, $target_peserta, $biaya, $category, $description);
            $ok = mysqli_stmt_execute($stmt);
            $msg = $ok ? "Kompetisi berhasil ditambahkan!" : "Gagal menyimpan data.";
            $msg_type = $ok ? "success" : "error";
        } else {
            $old_img = $_POST['old_image'] ?? 'default.jpg';
            if ($image_name !== $old_img && $old_img !== 'default.jpg') {
                $p = "../Assets/images/" . $old_img;
                if (file_exists($p)) unlink($p);
            }
            $stmt = mysqli_prepare($koneksi,
                "UPDATE competitions SET title=?, foto=?, pelaksanaan=?, date_range=?, target_peserta=?, biaya=?, category=?, description=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssisssi", $title, $image_name, $pelaksanaan, $date_range, $target_peserta, $biaya, $category, $description, $post_id);
            $ok = mysqli_stmt_execute($stmt);
            $msg = $ok ? "Kompetisi berhasil diperbarui!" : "Gagal memperbarui data.";
            $msg_type = $ok ? "success" : "error";
        }
    }
    
    header("Location: admin.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
    exit();
}
?>