<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($_SESSION['user_username'] ?? '') !== 'admin') {
    header("Location: ../pages/auth.php?error=" . urlencode("Access Denied! Admin only."));
    exit();
}
include "../config/database.php";

$msg      = '';
$msg_type = '';

// ── ADD NEW CATEGORY (AJAX) ───────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
    header('Content-Type: application/json');
    $cat_name = trim($_POST['name'] ?? '');
    $cat_name = ucwords(strtolower($cat_name));

    if (empty($cat_name)) {
        echo json_encode(['ok' => false, 'msg' => 'Category name is empty.']);
        exit();
    }
    if (mb_strlen($cat_name) > 40) {
        echo json_encode(['ok' => false, 'msg' => 'Category name is too long (max 40 characters).']);
        exit();
    }
    if (!preg_match('/^[\p{L}\p{N}\s\/\-&+.]+$/u', $cat_name)) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid characters in category name.']);
        exit();
    }

    $check = mysqli_prepare($koneksi, "SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
    mysqli_stmt_bind_param($check, "s", $cat_name);
    mysqli_stmt_execute($check);
    $check_res = mysqli_stmt_get_result($check);
    if ($existing = mysqli_fetch_assoc($check_res)) {
        echo json_encode(['ok' => true, 'id' => $existing['id'], 'name' => $cat_name, 'already_exists' => true]);
        exit();
    }

    $ins = mysqli_prepare($koneksi, "INSERT INTO categories (name) VALUES (?)");
    mysqli_stmt_bind_param($ins, "s", $cat_name);
    if (mysqli_stmt_execute($ins)) {
        $new_id = mysqli_insert_id($koneksi);
        echo json_encode(['ok' => true, 'id' => $new_id, 'name' => $cat_name]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Failed to save category.']);
    }
    exit();
}

// ── DELETE COMPETITION ──────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $stmt = mysqli_prepare($koneksi, "SELECT image FROM competitions WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $del_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Delete category relations first
    $del_cat = mysqli_prepare($koneksi, "DELETE FROM competition_categories WHERE competition_id = ?");
    mysqli_stmt_bind_param($del_cat, "i", $id);
    mysqli_stmt_execute($del_cat);

    $stmt2 = mysqli_prepare($koneksi, "DELETE FROM competitions WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);
    if (mysqli_stmt_execute($stmt2)) {
        if ($del_row && $del_row['image'] !== 'default.jpg') {
            $img_path = "../assets/images/" . $del_row['image'];
            if (file_exists($img_path)) unlink($img_path);
        }
        $msg = "Competition successfully deleted.";
        $msg_type = "success";
    } else {
        $msg = "Failed to delete competition.";
        $msg_type = "error";
    }

    header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
    exit();
}

// ── ADD / EDIT (POST) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action           = $_POST['action'] ?? 'add';
    $post_id          = (int)($_POST['id'] ?? 0);
    $title            = trim($_POST['title'] ?? '');
    $format           = trim($_POST['pelaksanaan'] ?? ''); // Map pelaksanaan form input to format
    $target_audience  = trim($_POST['target_peserta'] ?? 'General'); // Map target_peserta input to target_audience
    $registration_fee = max(0, (int)($_POST['biaya'] ?? 0)); // Map biaya input to registration_fee
    $description      = trim($_POST['description'] ?? '');
    $registration_link = trim($_POST['link_pendaftaran'] ?? ''); // Map link_pendaftaran input to registration_link

    if (empty($registration_link)) {
        $msg = "Registration link is required."; $msg_type = "error";
        header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
        exit();
    }
    if (!filter_var($registration_link, FILTER_VALIDATE_URL)) {
        $msg = "Invalid registration link format. Use: https://..."; $msg_type = "error";
        header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
        exit();
    }

    if (empty($title)) {
        $msg = "Competition title is required."; $msg_type = "error";
        header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
        exit();
    }

    if (!in_array($format, ['Online', 'Offline', 'Hybrid'])) $format = 'Online';

    $start_date = trim($_POST['start_date'] ?? '');
    $end_date   = trim($_POST['end_date'] ?? '');
    if (empty($start_date) || empty($end_date)) {
        $msg = "Start and end dates are required."; $msg_type = "error";
        header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
        exit();
    }
    
    $start_ts = strtotime($start_date);
    $end_ts   = strtotime($end_date);
    if (!$start_ts || !$end_ts) {
        $msg = "Invalid date format."; $msg_type = "error";
        header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
        exit();
    }
    $date_range = date('Y-m-d', $start_ts) . ',' . date('Y-m-d', $end_ts);

    // Category ids mapping
    $raw_categories = $_POST['categories'] ?? [];
    if (!is_array($raw_categories)) $raw_categories = [];
    $categories_ids = array_map('intval', $raw_categories);
    $categories_ids = array_filter($categories_ids, function($v) { return $v > 0; });
    $categories_ids = array_unique($categories_ids);

    if (empty($categories_ids)) {
        $msg = "Select at least one category."; $msg_type = "error";
        header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
        exit();
    }

    // Dynamic display category text for backward compatibility
    $cat_names = [];
    foreach ($categories_ids as $cat_id) {
        $cs = mysqli_prepare($koneksi, "SELECT name FROM categories WHERE id = ?");
        mysqli_stmt_bind_param($cs, "i", $cat_id);
        mysqli_stmt_execute($cs);
        $cr = mysqli_stmt_get_result($cs);
        if ($row = mysqli_fetch_assoc($cr)) {
            $cat_names[] = $row['name'];
        }
    }
    $category = implode(', ', $cat_names);

    $image_name = $_POST['old_image'] ?? 'default.jpg';
    $upload_err  = '';

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $upload_err = "Image format not supported (JPG, PNG, WebP, GIF).";
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $upload_err = "Maximum image size is 3MB.";
        } else {
            $new_img = uniqid('comp_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/" . $new_img)) {
                $old_img_val = $_POST['old_image'] ?? 'default.jpg';
                if ($action === 'edit' && $old_img_val !== 'default.jpg') {
                    $old_path = "../assets/images/" . $old_img_val;
                    if (file_exists($old_path)) unlink($old_path);
                }
                $image_name = $new_img;
            } else {
                $upload_err = "Failed to upload image.";
            }
        }
    } elseif ($action === 'add') {
        $upload_err = "Competition poster/image is required.";
    }

    if (!empty($upload_err)) {
        $msg = $upload_err; $msg_type = "error";
        header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
        exit();
    }

    if ($action === 'add') {
        // Generate a 10-digit unique uid
        do {
            $uid = str_pad(random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
            $uid_check = mysqli_prepare($koneksi, "SELECT id FROM competitions WHERE uid = ?");
            mysqli_stmt_bind_param($uid_check, "s", $uid);
            mysqli_stmt_execute($uid_check);
            $uid_exists = mysqli_num_rows(mysqli_stmt_get_result($uid_check)) > 0;
        } while ($uid_exists);

        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO competitions (uid, title, image, format, date_range, target_audience, registration_fee, category, description, registration_link)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssssisss", $uid, $title, $image_name, $format, $date_range, $target_audience, $registration_fee, $category, $description, $registration_link);
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            $new_comp_id = mysqli_insert_id($koneksi);
            foreach ($categories_ids as $cat_id) {
                $ins_cat = mysqli_prepare($koneksi, "INSERT IGNORE INTO competition_categories (competition_id, category_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($ins_cat, "ii", $new_comp_id, $cat_id);
                mysqli_stmt_execute($ins_cat);
            }
            $msg = "Competition successfully added!";
            $msg_type = "success";

            // ─── TELEGRAM BROADCAST (background exec) ─────────────────────────────
            // Spawn a separate PHP CLI process so the broadcast never blocks the HTTP
            // response. exec() detaches immediately; redirect happens right after.
            $php_bin = '/opt/lampp/bin/php';
            if (!file_exists($php_bin) || !is_executable($php_bin)) {
                $php_bin = 'php'; // Fallback to system PATH
            }
            $script    = escapeshellarg(dirname(__DIR__) . '/scripts/broadcast-new-competition.php');
            $comp_arg  = escapeshellarg((string)$new_comp_id);
            $log_file  = escapeshellarg(dirname(__DIR__) . '/logs/broadcast.log');

            // Ensure log directory exists
            $log_dir = dirname(__DIR__) . '/logs';
            if (!is_dir($log_dir)) {
                mkdir($log_dir, 0755, true);
            }

            // Fire and forget — redirect stdin/stdout/stderr, append &
            exec("{$php_bin} {$script} {$comp_arg} >> {$log_file} 2>&1 &");

            header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
            exit();
        } else {
            $msg = "Failed to save data: " . mysqli_error($koneksi);
            $msg_type = "error";
        }
    } else {
        // Edit — Update data
        $stmt = mysqli_prepare($koneksi,
            "UPDATE competitions SET title=?, image=?, format=?, date_range=?, target_audience=?, registration_fee=?, category=?, description=?, registration_link=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssssisssi", $title, $image_name, $format, $date_range, $target_audience, $registration_fee, $category, $description, $registration_link, $post_id);
        $ok = mysqli_stmt_execute($stmt);
        if ($ok) {
            // Update pivot categories table
            $del_pivot = mysqli_prepare($koneksi, "DELETE FROM competition_categories WHERE competition_id = ?");
            mysqli_stmt_bind_param($del_pivot, "i", $post_id);
            mysqli_stmt_execute($del_pivot);

            foreach ($categories_ids as $cat_id) {
                $ins_cat = mysqli_prepare($koneksi, "INSERT IGNORE INTO competition_categories (competition_id, category_id) VALUES (?, ?)");
                mysqli_stmt_bind_param($ins_cat, "ii", $post_id, $cat_id);
                mysqli_stmt_execute($ins_cat);
            }
            $msg = "Competition successfully updated!";
            $msg_type = "success";
        } else {
            $msg = "Failed to update data: " . mysqli_error($koneksi);
            $msg_type = "error";
        }
    }

    header("Location: ../admin/dashboard.php?tab=kelola&msg=" . urlencode($msg) . "&msg_type=" . urlencode($msg_type));
    exit();
}
?>
