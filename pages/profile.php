<?php
include "./session_check.php";
include "../config/koneksi.php";

// Pastikan yang mengakses ini adalah user yang sudah login
if (!isset($_SESSION['status']) || $_SESSION['status'] !== "login") {
    header("Location: auth.php");
    exit();
}

// Menghindari error jika session beda nama
$username = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
$initial  = strtoupper(substr($username, 0, 1));

// Ambil data user dari database dengan aman
$email = 'Email belum diatur'; // Default jika gagal ambil dari database
$foto_profile = 'default_user.png';
$user_id = 0;

try {
    $sql = "SELECT id, username, email, foto_profile FROM users WHERE username = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            $email = $user_data['email'] ?? 'Email belum diatur';
            $foto_profile = $user_data['foto_profile'] ?? 'default_user.png';
            $user_id = (int)$user_data['id'];
        }
    }
} catch (Exception $e) {
    // Jika kolom email belum ada di database, lewati saja tanpa error blank
}

$saved_competitions = [];
if ($user_id > 0) {
    $savedSql = "SELECT c.* FROM saved_competitions sc JOIN competitions c ON sc.competition_id = c.id WHERE sc.user_id = ? ORDER BY sc.saved_at DESC";
    $savedStmt = mysqli_prepare($koneksi, $savedSql);
    if ($savedStmt) {
        mysqli_stmt_bind_param($savedStmt, "i", $user_id);
        mysqli_stmt_execute($savedStmt);
        $savedResult = mysqli_stmt_get_result($savedStmt);
        while ($savedRow = mysqli_fetch_assoc($savedResult)) {
            $saved_competitions[] = $savedRow;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - LombaID</title>
    <link rel="stylesheet" href="../Assets/css/navbar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/landing.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/floating_search.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    
    <link rel="stylesheet" href="../Assets/css/profile.css?v=<?php echo time(); ?>">
    <script src="../Assets/js/profile.js?v=<?= filemtime(__DIR__ . '/../Assets/js/profile.js') ?>" defer></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="profile-container">
    <div class="layout">
        
        <div class="sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <img src="../Assets/images/<?= htmlspecialchars($foto_profile) ?>" alt="Foto Profil" onerror="this.src='../Assets/images/default_user.png'">
                </div>
                <div class="profile-name"><?= htmlspecialchars($username) ?></div>
                <div class="profile-email"><?= htmlspecialchars($email) ?></div>
            </div>

            <div class="sidenav">
                <div class="sidenav-item active" onclick="switchTab('data-diri', this)">
                    <i class="ti ti-user" style="font-size:18px"></i> Data diri
                </div>
                <div class="sidenav-item" onclick="switchTab('lomba-tersimpan', this)">
                    <i class="ti ti-bookmark" style="font-size:18px"></i> Lomba tersimpan
                </div>
                <div class="sidenav-item" onclick="switchTab('pengaturan', this)">
                    <i class="ti ti-settings" style="font-size:18px"></i> Pengaturan
                </div>
                <a href="logout.php" class="sidenav-item danger">
                    <i class="ti ti-logout" style="font-size:18px"></i> Keluar
                </a>
            </div>
        </div>

        <div class="main-content">

            <!-- TAB: Data Diri -->
            <div class="tab-panel active" id="tab-data-diri">
                <div class="section">
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
                            <div class="field-label">Asal Instansi/Sekolah</div>
                            <div class="field-empty">Belum diatur</div>
                        </div>
                        <div class="field-item">
                            <div class="field-label">Nomor WhatsApp</div>
                            <div class="field-empty">Belum diatur</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Lomba Tersimpan -->
            <div class="tab-panel" id="tab-lomba-tersimpan">
                <div class="section">
                    <div class="section-head">
                        <span class="section-title">Lomba Tersimpan</span>
                    </div>
                    <?php if (count($saved_competitions) === 0): ?>
                    <div style="padding: 40px 20px; text-align: center; color: var(--color-text-secondary);">
                        <i class="ti ti-bookmark-off" style="font-size: 48px; opacity: 0.5; margin-bottom: 10px; display: block;"></i>
                        <p style="font-size: 14px; font-weight: 500;">Belum ada lomba yang kamu simpan.</p>
                    </div>
                    <?php else: ?>
                    <div class="saved-list">
                        <?php foreach ($saved_competitions as $saved): ?>
                            <a href="detail_lomba.php?id=<?php echo urlencode($saved['id']); ?>" class="saved-item">
                                <div class="saved-item-thumb">
                                    <img src="../Assets/images/<?= htmlspecialchars($saved['foto'] ?? $saved['image'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($saved['title']); ?>" onerror="this.src='../Assets/images/default.jpg'">
                                </div>
                                <div class="saved-item-content">
                                    <div class="saved-title"><?= htmlspecialchars($saved['title']); ?></div>
                                    <div class="saved-meta">
                                        <span><?= htmlspecialchars($saved['pelaksanaan'] ?? $saved['location'] ?? 'Online/Offline'); ?></span>
                                        <span>•</span>
                                        <span><?= isset($saved['biaya']) && is_numeric($saved['biaya']) && $saved['biaya'] > 0 ? 'Rp ' . number_format($saved['biaya'], 0, ',', '.') : 'Gratis'; ?></span>
                                        <span>•</span>
                                        <span><?= htmlspecialchars($saved['target_peserta'] ?? $saved['target'] ?? 'Umum'); ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB: Pengaturan -->
            <div class="tab-panel" id="tab-pengaturan">
                <div class="section">
                    <div class="section-head">
                        <span class="section-title">Pengaturan Akun</span>
                    </div>
                    <div class="field-grid">
                        <div class="field-item" style="cursor: pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div class="field-label" style="color: var(--color-text-primary); font-size: 13px;">
                                <i class="ti ti-lock" style="vertical-align: middle; margin-right: 4px;"></i> Ganti Password
                            </div>
                        </div>
                        <div class="field-item" style="cursor: pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <div class="field-label" style="color: var(--color-text-primary); font-size: 13px;">
                                <i class="ti ti-bell" style="vertical-align: middle; margin-right: 4px;"></i> Preferensi Notifikasi
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</div>

</body>
</html>