<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || !isset($_SESSION['user_id'])) {
    header("Location: ../pages/auth.php?error=" . urlencode("Please login first."));
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($koneksi, "SELECT role, profile_picture FROM users WHERE id = ?");
if (!$stmt) {
    header("Location: ../pages/home.php?error=" . urlencode("Database error occurred."));
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);

if (!$user) {
    header("Location: ../pages/auth.php?error=" . urlencode("User not found."));
    exit();
}

$current_role = $user['role'];
$profile_picture = $user['profile_picture'];

$redirect_url = ($current_role === 'admin') ? "../admin/dashboard.php?tab=data-diri" : "../pages/profile.php?tab=data-diri";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_telegram_chat_id = trim($_POST['telegram_chat_id'] ?? '');
    $new_institution = trim($_POST['instansi'] ?? ''); // Map form field 'instansi' to 'institution'

    if (empty($new_username) || empty($new_email)) {
        header("Location: " . $redirect_url . "&error=" . urlencode("Username and Email are required!"));
        exit();
    }

    $check_stmt = mysqli_prepare($koneksi, "SELECT username, email FROM users WHERE (username = ? OR email = ?) AND id != ?");
    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, "ssi", $new_username, $new_email, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_res = mysqli_stmt_get_result($check_stmt);
        if ($row = mysqli_fetch_assoc($check_res)) {
            if (strcasecmp($row['username'], $new_username) === 0) {
                header("Location: " . $redirect_url . "&error=" . urlencode("Username is already in use!"));
                exit();
            } else {
                header("Location: " . $redirect_url . "&error=" . urlencode("Email is already in use!"));
                exit();
            }
        }
    }

    $file_uploaded = isset($_FILES['foto_profile']) && $_FILES['foto_profile']['error'] === UPLOAD_ERR_OK;
    $hapus_foto = isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1';

    if ($file_uploaded) {
        $file_tmp = $_FILES['foto_profile']['tmp_name'];
        $file_name = $_FILES['foto_profile']['name'];
        $file_size = $_FILES['foto_profile']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed_exts)) {
            header("Location: " . $redirect_url . "&error=" . urlencode("Profile photo format must be JPG, JPEG, PNG, GIF, or WEBP!"));
            exit();
        } elseif ($file_size > 3 * 1024 * 1024) {
            header("Location: " . $redirect_url . "&error=" . urlencode("Maximum profile photo size is 3MB!"));
            exit();
        } else {
            $new_filename = uniqid('user_') . '.' . $ext;
            $upload_dir = '../assets/images/';
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                if (!empty($profile_picture) && $profile_picture !== 'default_user.png') {
                    $old_path = $upload_dir . $profile_picture;
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                $profile_picture = $new_filename;
            } else {
                header("Location: " . $redirect_url . "&error=" . urlencode("Failed to upload profile photo!"));
                exit();
            }
        }
    } elseif ($hapus_foto) {
        $upload_dir = '../assets/images/';
        if (!empty($profile_picture) && $profile_picture !== 'default_user.png') {
            $old_path = $upload_dir . $profile_picture;
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }
        $profile_picture = 'default_user.png';
    }

    $update_stmt = mysqli_prepare($koneksi, "UPDATE users SET username = ?, email = ?, telegram_chat_id = ?, institution = ?, profile_picture = ? WHERE id = ?");
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "sssssi", $new_username, $new_email, $new_telegram_chat_id, $new_institution, $profile_picture, $user_id);
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['user_username'] = $new_username;
            if (isset($_SESSION['username'])) {
                $_SESSION['username'] = $new_username;
            }
            header("Location: " . $redirect_url . "&success=" . urlencode("Profile successfully updated!"));
            exit();
        } else {
            header("Location: " . $redirect_url . "&error=" . urlencode("Failed to update profile in database."));
            exit();
        }
    } else {
        header("Location: " . $redirect_url . "&error=" . urlencode("System error occurred."));
        exit();
    }
} else {
    header("Location: " . $redirect_url);
    exit();
}
?>
