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
$bookmarked_competitions = [];

try {
    $sql = "SELECT id, username, email FROM users WHERE username = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            $email = $user_data['email'] ?? 'Email belum diatur';
            
            // --- KODE BARU: Tarik data lomba yang di-bookmark ---
            $userId = $user_data['id'];
            $bookmark_sql = "SELECT c.*, 1 as is_bookmarked FROM bookmarks b 
                             JOIN competitions c ON b.competition_id = c.id 
                             WHERE b.user_id = ?";
            $stmt_book = mysqli_prepare($koneksi, $bookmark_sql);
            mysqli_stmt_bind_param($stmt_book, "i", $userId);
            mysqli_stmt_execute($stmt_book);
            $bookmark_result = mysqli_stmt_get_result($stmt_book);
            
            while($row_book = mysqli_fetch_assoc($bookmark_result)){
                $bookmarked_competitions[] = $row_book;
            }
        }
    }
} catch (Exception $e) {
    // Jika kolom email belum ada di database, lewati saja tanpa error blank
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
    
    <style>
        :root {
            --color-background-primary: #ffffff;
            --color-background-secondary: #f0f4f9;
            --color-border-tertiary: #e2e8f0;
            --color-text-primary: #1a2a3a;
            --color-text-secondary: #4a6070;
            --border-radius-lg: 16px;
            --font-sans: "Plus Jakarta Sans", sans-serif;
        }

        body {
            background: var(--color-background-secondary); 
            font-family: var(--font-sans); 
            margin: 0; 
            padding: 0; 
            color: var(--color-text-primary);
        }

        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .layout { 
            display: grid; 
            grid-template-columns: 240px minmax(0, 1fr); 
            gap: 24px; 
        }

        .sidebar { display: flex; flex-direction: column; gap: 16px; }
        .profile-card { 
            background: var(--color-background-primary); 
            border: 1px solid var(--color-border-tertiary); 
            border-radius: var(--border-radius-lg); 
            padding: 24px 16px; 
            display: flex; flex-direction: column; align-items: center; gap: 8px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .avatar { 
            width: 72px; height: 72px; 
            border-radius: 50%; 
            background: #B5D4F4; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 24px; font-weight: 700; color: #0C447C; 
        }
        .profile-name { font-size: 16px; font-weight: 700; color: var(--color-text-primary); text-align: center; }
        .profile-email { font-size: 13px; color: var(--color-text-secondary); text-align: center; }

        .sidenav { 
            background: var(--color-background-primary); 
            border: 1px solid var(--color-border-tertiary); 
            border-radius: var(--border-radius-lg); 
            overflow: hidden; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .sidenav-item { 
            display: flex; align-items: center; gap: 10px; 
            padding: 14px 16px; 
            border-bottom: 1px solid var(--color-border-tertiary); 
            text-decoration: none; font-size: 14px; font-weight: 500; color: var(--color-text-secondary); 
            transition: background 0.2s;
        }
        .sidenav-item:hover { background: #f8fafc; }
        .sidenav-item:last-child { border-bottom: none; }
        .sidenav-item.active { background: #E6F1FB; color: #185FA5; border-left: 4px solid #185FA5; }
        .sidenav-item.danger { color: #d32f2f; }
        .sidenav-item.danger:hover { background: #fdecea; }

        .main-content { 
            background: var(--color-background-primary); 
            border: 1px solid var(--color-border-tertiary); 
            border-radius: var(--border-radius-lg); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow: hidden;
        }
        .section { border-bottom: 1px solid var(--color-border-tertiary); }
        .section:last-child { border-bottom: none; }
        .section-head { 
            padding: 16px 24px; 
            border-bottom: 1px solid var(--color-border-tertiary); 
            display: flex; align-items: center; justify-content: space-between; 
            background: #fdfdfd;
        }
        .section-title { font-size: 16px; font-weight: 700; color: var(--color-text-primary); }
        .edit-btn { font-size: 13px; color: #185FA5; cursor: pointer; display: flex; align-items: center; gap: 4px; font-weight: 600; }
        
        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); }
        .field-item { padding: 16px 24px; border-bottom: 1px solid var(--color-border-tertiary); border-right: 1px solid var(--color-border-tertiary); }
        .field-item:nth-child(even) { border-right: none; }
        .field-item:nth-last-child(-n+2) { border-bottom: none; }
        .field-label { font-size: 11px; color: var(--color-text-secondary); margin-bottom: 6px; text-transform: uppercase; font-weight: 600;}
        .field-value { font-size: 14px; font-weight: 600; color: var(--color-text-primary); }
        .field-empty { font-size: 14px; font-style: italic; color: #94a3b8; font-weight: 400; }

        @media (max-width: 768px) {
            .layout { grid-template-columns: 1fr; }
            .field-grid { grid-template-columns: 1fr; }
            .field-item:nth-child(odd) { border-right: none; }
            .field-item:nth-last-child(-n+2) { border-bottom: 1px solid var(--color-border-tertiary); }
            .field-item:last-child { border-bottom: none; }
        }

        /* ============================================
           PROFILE PAGE — Button System
           ============================================ */

        /* Edit Button */
        .edit-btn {
          display: inline-flex;
          align-items: center;
          gap: 5px;
          padding: 6px 14px;
          border-radius: 18px;
          border: 1.5px solid #c8ddf2;
          background: #eaf3fc;
          color: #185FA5;
          font-size: 12.5px;
          font-weight: 600;
          cursor: pointer;
          transition: background 0.2s, border-color 0.2s, transform 0.15s;
          text-decoration: none;
        }
        .edit-btn:hover {
          background: #d6eaf8;
          border-color: #8ab8e0;
          transform: translateY(-1px);
        }

        /* Sidenav Items — tighten hover state */
        .sidenav-item {
          transition: background 0.2s, padding-left 0.2s;
        }
        .sidenav-item:hover {
          background: #f1f7fd;
          padding-left: 20px; /* subtle slide-in */
        }
        .sidenav-item.active {
          background: #E6F1FB;
          color: #185FA5;
          border-left: 3px solid #185FA5;
          padding-left: 17px; /* compensate border width */
          font-weight: 700;
        }
        .sidenav-item.danger:hover {
          background: #fff0f0;
          color: #c0392b;
          padding-left: 20px;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        /* Settings clickable field items */
        .field-item[style*="cursor: pointer"] {
          border-radius: 0;
          transition: background 0.2s, padding-left 0.2s !important;
        }
        .field-item[style*="cursor: pointer"]:hover {
          background: #f1f7fd !important;
          padding-left: 28px;
        }
        .field-item[style*="cursor: pointer"] .field-label {
          color: #185FA5 !important;
          font-size: 13px;
          font-weight: 600;
        }
                /* ── TAB SYSTEM (dari admin) ── */
                .tab-panel { display: none; }
                .tab-panel.active { display: block; }

                /* ── sidenav pakai div, bukan <a> ── */
                .sidenav-item {
                        cursor: pointer;
                        border-left: 3px solid transparent;
                }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="profile-container">
    <div class="layout">
        
        <div class="sidebar">
            <div class="profile-card">
                <div class="avatar"><?= $initial ?></div>
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
                    <div style="padding: 24px;">
                        <?php if (empty($bookmarked_competitions)): ?>
                            <div style="padding: 40px 20px; text-align: center; color: var(--color-text-secondary);">
                                <p style="font-size: 14px; font-weight: 500;">Belum ada lomba yang kamu simpan.</p>
                            </div>
                        <?php else: ?>
                            <div class="card-grid">
                                <?php 
                                foreach ($bookmarked_competitions as $row) {
                                    include 'card_competition.php';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
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

<script>
function switchTab(tab, el) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidenav-item').forEach(i => i.classList.remove('active'));
    var panel = document.getElementById('tab-' + tab);
    if (panel) panel.classList.add('active');
    if (el) el.classList.add('active');
}

function toggleBookmark(button, competitionId) {
    var formData = new FormData();
    formData.append('competition_id', competitionId);

    fetch('toggle_bookmark.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data.status === 'success') {
            var isNowBookmarked = data.action === 'added';
            button.dataset.active = isNowBookmarked ? 'true' : 'false';
            button.innerText = isNowBookmarked ? '♥' : '♡';
            button.style.color = isNowBookmarked ? '#e53e3e' : '#aaa';
        } else {
            alert(data.message || 'Gagal memperbarui bookmark.');
        }
    })
    .catch(function() {
        alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
    });
}
</script>

</body>
</html>