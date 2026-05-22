<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($_SESSION['user_username'] ?? '') !== 'admin') {
    header("Location: ../pages/auth.php?error=" . urlencode("Akses Ditolak! Khusus Admin."));
    exit();
}
include "../config/koneksi.php";

$username = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'admin';
$initial  = strtoupper(substr($username, 0, 1));
$email = 'Email belum diatur';
$foto_profile = 'default_user.png';

try {
    $sql  = "SELECT id, username, email, foto_profile FROM users WHERE username = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            $email = $user_data['email'] ?? 'Email belum diatur';
            $foto_profile = $user_data['foto_profile'] ?? 'default_user.png';
        }
    }
} catch (Exception $e) {}

$active_tab = $_GET['tab'] ?? 'data-diri';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — LombaID</title>
    <link rel="stylesheet" href="../Assets/css/navbar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/landing.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/floating_search.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <link rel="stylesheet" href="../Assets/css/admin.css?v=<?php echo time(); ?>">
    <script src="../Assets/js/admin.js?v=<?= time(); ?>"></script>
</head>
<body>

<?php include '../pages/navbar.php'; ?>

<div class="profile-container">
    <div class="layout">

        <div class="sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <img src="../Assets/images/<?= htmlspecialchars($foto_profile) ?>" alt="Foto Profil" onerror="this.src='../Assets/images/default_user.png'">
                </div>
                <div class="profile-name"><?= htmlspecialchars($username) ?></div>
                <div class="profile-email"><?= htmlspecialchars($email) ?></div>
                <span class="admin-badge">⭐ Administrator</span>
            </div>

            <div class="sidenav">
                <div class="sidenav-item <?= $active_tab === 'data-diri' ? 'active' : '' ?>" onclick="switchTab('data-diri')">
                    <i class="ti ti-user" style="font-size:18px"></i> Data Diri
                </div>
                <div class="sidenav-item <?= $active_tab === 'kelola' ? 'active' : '' ?>" onclick="switchTab('kelola')">
                    <i class="ti ti-trophy" style="font-size:18px"></i> Kelola Lomba
                </div>
                <div class="sidenav-item <?= $active_tab === 'pengaturan' ? 'active' : '' ?>" onclick="switchTab('pengaturan')">
                    <i class="ti ti-settings" style="font-size:18px"></i> Pengaturan
                </div>
                <a href="../pages/logout.php" class="sidenav-item danger">
                    <i class="ti ti-logout" style="font-size:18px"></i> Keluar
                </a>
            </div>
        </div>

        <div class="main-content">

            <div class="tab-panel <?= $active_tab === 'data-diri' ? 'active' : '' ?>" id="tab-data-diri">
                <div class="section" id="data-diri">
                    <div class="section-head">
                        <span class="section-title">Informasi Data Diri</span>
                        <span class="edit-btn"><i class="ti ti-edit" style="font-size:16px"></i> Edit</span>
                    </div>
                    <div class="field-grid">
                        <div class="field-item">
                            <div class="field-label">Username</div>
                            <div class="field-value"><?= htmlspecialchars($username) ?></div>
                        </div>
                        <div class="field-item">
                            <div class="field-label">Email</div>
                            <div class="field-value"><?= htmlspecialchars($email) ?></div>
                        </div>
                        <div class="field-item">
                            <div class="field-label">Role</div>
                            <div class="field-value">Administrator</div>
                        </div>
                        <div class="field-item">
                            <div class="field-label">Nomor WhatsApp</div>
                            <div class="field-empty">Belum diatur</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'kelola_lomba.php'; ?>

            <div class="tab-panel <?= $active_tab === 'pengaturan' ? 'active' : '' ?>" id="tab-pengaturan">
                <div class="section">
                    <div class="section-head">
                        <span class="section-title">Pengaturan Akun</span>
                    </div>
                    <div class="field-grid">
                        <div class="field-item" style="cursor:pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div class="field-label" style="color:var(--color-text-primary);font-size:13px;">
                                <i class="ti ti-lock" style="vertical-align:middle;margin-right:4px;"></i> Ganti Password
                            </div>
                        </div>
                        <div class="field-item" style="cursor:pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div class="field-label" style="color:var(--color-text-primary);font-size:13px;">
                                <i class="ti ti-bell" style="vertical-align:middle;margin-right:4px;"></i> Preferensi Notifikasi
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// ── TAB SWITCHING UTAMA ──
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidenav-item').forEach(i => i.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
    history.replaceState(null, '', '?tab=' + tab);
}
</script>

</body>
</html>
