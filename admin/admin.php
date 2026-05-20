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

try {
    $sql  = "SELECT id, username, email FROM user WHERE username = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            $email = $user_data['email'] ?? 'Email belum diatur';
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

    <style>
        :root {
            --color-background-primary: #ffffff;
            --color-background-secondary: #f0f4f9;
            --color-border-tertiary: #e2e8f0;
            --color-text-primary: #1a2a3a;
            --color-text-secondary: #4a6070;
            --border-radius-lg: 16px;
            --font-sans: "Plus Jakarta Sans", sans-serif;
            --navy:   #0d1f35;
            --blue:   #0d3055;
            --mid:    #0a4a7a;
            --accent: #5bb8f5;
            --red:    #e53e3e;
            --border: #dde6ef;
            --bg:     #f0f4f9;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--color-background-secondary);
            font-family: var(--font-sans);
            margin: 0;
            padding: 0;
            color: var(--color-text-primary);
        }

        /* ── LAYOUT ── */
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px 60px;
        }

        .layout {
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            gap: 24px;
        }

        /* ── SIDEBAR LEFT ── */
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
        .admin-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: #fff3cd; color: #856404;
            border: 1px solid #ffc107;
            border-radius: 20px;
            font-size: 11px; font-weight: 700;
            padding: 3px 10px;
            text-transform: uppercase; letter-spacing: 0.4px;
        }
        .profile-name  { font-size: 16px; font-weight: 700; color: var(--color-text-primary); text-align: center; }
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
            text-decoration: none;
            font-size: 14px; font-weight: 500;
            color: var(--color-text-secondary);
            cursor: pointer;
            transition: background 0.2s, padding-left 0.2s;
            border-left: 3px solid transparent;
        }
        .sidenav-item:last-child { border-bottom: none; }
        .sidenav-item:hover { background: #f1f7fd; padding-left: 20px; }
        .sidenav-item.active {
            background: #E6F1FB; color: #185FA5;
            border-left-color: #185FA5;
            padding-left: 17px; font-weight: 700;
        }
        .sidenav-item.danger { color: #d32f2f; }
        .sidenav-item.danger:hover { background: #fff0f0; color: #c0392b; padding-left: 20px; }

        /* ── MAIN CONTENT ── */
        .main-content {
            background: var(--color-background-primary);
            border: 1px solid var(--color-border-tertiary);
            border-radius: var(--border-radius-lg);
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── SECTION ── */
        .section { border-bottom: 1px solid var(--color-border-tertiary); }
        .section:last-child { border-bottom: none; }
        .section-head {
            padding: 16px 24px;
            border-bottom: 1px solid var(--color-border-tertiary);
            display: flex; align-items: center; justify-content: space-between;
            background: #fdfdfd;
        }
        .section-title { font-size: 16px; font-weight: 700; color: var(--color-text-primary); }

        /* ── FIELD GRID (Data Diri) ── */
        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); }
        .field-item {
            padding: 16px 24px;
            border-bottom: 1px solid var(--color-border-tertiary);
            border-right: 1px solid var(--color-border-tertiary);
        }
        .field-item:nth-child(even) { border-right: none; }
        .field-item:nth-last-child(-n+2) { border-bottom: none; }
        .field-label { font-size: 11px; color: var(--color-text-secondary); margin-bottom: 6px; text-transform: uppercase; font-weight: 600; }
        .field-value { font-size: 14px; font-weight: 600; color: var(--color-text-primary); }
        .field-empty { font-size: 14px; font-style: italic; color: #94a3b8; font-weight: 400; }

        /* ── EDIT BTN ── */
        .edit-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 14px;
            border-radius: 18px;
            border: 1.5px solid #c8ddf2;
            background: #eaf3fc;
            color: #185FA5;
            font-size: 12.5px; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: background 0.2s, border-color 0.2s, transform 0.15s;
        }
        .edit-btn:hover { background: #d6eaf8; border-color: #8ab8e0; transform: translateY(-1px); }

        /* ── ALERTS ── */
        .alert {
            margin: 16px 24px;
            padding: 11px 16px;
            border-radius: 10px;
            font-size: 14px;
        }
        .alert-success { background: #e6f4ea; color: #1e7e34; border: 1px solid #b7dfb9; }
        .alert-error   { background: #fce8e6; color: #c0392b; border: 1px solid #f5c6c6; }

        /* ── KELOLA LOMBA TOOLBAR ── */
        .kelola-toolbar {
            padding: 16px 24px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--color-border-tertiary);
            background: #fdfdfd;
        }
        .kelola-toolbar span { font-size: 13px; color: var(--color-text-secondary); }

        /* ── TABLE ── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead th {
            text-align: left; padding: 11px 14px;
            font-size: 11px; font-weight: 700; color: var(--color-text-secondary);
            text-transform: uppercase; letter-spacing: 0.05em;
            background: var(--bg); border-bottom: 1px solid var(--border);
        }
        tbody td { padding: 13px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fbff; }

        .td-img { width: 54px; height: 38px; border-radius: 6px; object-fit: cover; }

        .badge { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.3px; }
        .badge-design      { background: #e8f0fe; color: #185FA5; }
        .badge-programming { background: #e6f4ea; color: #1e7e34; }
        .badge-hacking     { background: #fce8e6; color: #c0392b; }
        .badge-all         { background: #f0f4f9; color: #5a7a99; }

        .actions { display: flex; gap: 6px; }

        /* ── BUTTONS ── */
        .btn { padding: 9px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; font-family: inherit; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-primary   { background: var(--blue); color: white; }
        .btn-primary:hover { background: var(--mid); }
        .btn-secondary { background: var(--bg); color: var(--color-text-primary); border: 1.5px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-danger    { background: var(--red); color: white; }
        .btn-danger:hover { background: #c53030; }
        .btn-sm { padding: 5px 12px; font-size: 12px; border-radius: 6px; }

        .empty-state { text-align: center; padding: 40px 20px; color: var(--color-text-secondary); font-size: 14px; }

        /* ── MODAL ── */
        .overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(10,20,35,0.5); z-index: 999;
            justify-content: center; align-items: center;
            padding: 24px; backdrop-filter: blur(3px);
        }
        .overlay.open { display: flex; }
        .modal-box {
            background: white; border-radius: 16px;
            padding: 26px; width: 100%; max-width: 620px;
            max-height: 90vh; overflow-y: auto; position: relative;
            animation: popIn 0.22s ease;
        }
        @keyframes popIn {
            from { opacity:0; transform: scale(0.96) translateY(10px); }
            to   { opacity:1; transform: scale(1) translateY(0); }
        }
        .modal-box h3 { font-size: 16px; font-weight: 800; color: var(--navy); margin-bottom: 18px; }
        .modal-close {
            position: absolute; top: 14px; right: 16px;
            width: 28px; height: 28px; background: var(--bg);
            border: none; border-radius: 50%; cursor: pointer;
            font-size: 13px; color: var(--color-text-secondary);
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .modal-close:hover { background: var(--border); color: var(--navy); }

        /* ── FORM (in modal) ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--color-text-primary); }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 9px 12px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 14px; font-family: inherit;
            color: var(--color-text-primary);
            background: white; outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { border-color: var(--accent); }
        .form-group textarea { resize: vertical; min-height: 85px; }

        .file-upload-area {
            border: 2px dashed var(--border); border-radius: 10px;
            padding: 20px; text-align: center; cursor: pointer;
            transition: border-color 0.2s, background 0.2s; position: relative;
        }
        .file-upload-area:hover { border-color: var(--accent); background: #f5f9ff; }
        .file-upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .file-upload-area .upload-icon { font-size: 26px; margin-bottom: 6px; }
        .file-upload-area p { font-size: 13px; color: var(--color-text-secondary); }
        .file-upload-area p strong { color: var(--blue); }
        .file-preview { margin-top: 10px; display: none; }
        .file-preview img { max-height: 110px; border-radius: 8px; object-fit: cover; }

        .form-actions { margin-top: 18px; display: flex; gap: 10px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .layout { grid-template-columns: 1fr; }
            .field-grid { grid-template-columns: 1fr; }
            .field-item:nth-child(odd) { border-right: none; }
            .field-item:nth-last-child(-n+2) { border-bottom: 1px solid var(--color-border-tertiary); }
            .field-item:last-child { border-bottom: none; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include '../pages/navbar.php'; ?>

<div class="profile-container">
    <div class="layout">

        <div class="sidebar">
            <div class="profile-card">
                <div class="avatar"><?= $initial ?></div>
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
                <a href="../pages/landing.php" class="sidenav-item">
                    <i class="ti ti-world" style="font-size:18px"></i> Lihat Website
                </a>
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
