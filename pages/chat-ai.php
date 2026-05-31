<?php
// pages/chat-ai.php
include "./session-check.php";
include "../config/database.php";

// Pengguna harus login
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || !isset($_SESSION['user_id'])) {
    header("Location: auth.php?error=" . urlencode("Silakan login terlebih dahulu untuk mengakses Chat AI."));
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// 1. Ambil Profil User Aktif (untuk foto profil di chat bubble)
$user_pic = '../assets/images/default_user.png';
$user_stmt = mysqli_prepare($koneksi, "SELECT profile_picture FROM users WHERE id = ?");
if ($user_stmt) {
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_res = mysqli_stmt_get_result($user_stmt);
    if ($user_row = mysqli_fetch_assoc($user_res)) {
        $pic_name = $user_row['profile_picture'] ?? 'default_user.png';
        $user_pic = '../assets/images/' . $pic_name;
    }
    mysqli_stmt_close($user_stmt);
}

// 2. Ambil Daftar Lomba Aktif (published) untuk Picker Modal
$competitions = [];
$comp_sql = "SELECT id, title, image, format, date_range, target_audience, registration_fee, category, description, registration_link 
             FROM competitions 
             WHERE submission_status = 'published' 
             ORDER BY id DESC";
$comp_res = mysqli_query($koneksi, $comp_sql);
if ($comp_res) {
    while ($row = mysqli_fetch_assoc($comp_res)) {
        $row['id'] = (int)$row['id'];
        $row['registration_fee'] = (int)$row['registration_fee'];
        $competitions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AI Debate Arena. Diskusikan kelayakan keikutsertaan kompetisi bersama 3 Persona AI (Karin, Tiara, Raka).">
    <title>Konsultasi AI — Pasti Info</title>
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../assets/css/global.css') ?>">
    <link rel="stylesheet" href="../assets/css/navbar.css?v=<?= filemtime(__DIR__ . '/../assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../assets/css/chat-ai.css?v=<?= filemtime(__DIR__ . '/../assets/css/chat-ai.css') ?>">
    
    <!-- Material Symbols for Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
</head>
<body class="chat-ai-page">

    <!-- Header Navbar -->
    <?php include '../components/navbar.php'; ?>

    <!-- Main App Shell -->
    <div class="app-shell">
        <div class="body-wrap">
            
            <!-- Sidebar Drawer -->
            <aside class="chat-sidebar" id="sidebar">
                <div class="chat-sidebar-section">
                    <p class="chat-sidebar-label">Kompetisi Aktif</p>
                </div>

                <!-- Info Lomba Terpilih -->
                <div id="compCardContainer">
                    <!-- Dinamis via chat-ai.js -->
                </div>

                <!-- Riwayat Diskusi Scroll List -->
                <div class="chat-sidebar-section" style="margin-top: 14px;">
                    <p class="chat-sidebar-label">Riwayat Diskusi</p>
                </div>
                
                <div class="history-scroll">
                    <!-- Tombol Diskusi Baru -->
                    <div class="new-chat-btn" id="newChatBtn">
                        <span class="mat">add</span>
                        Diskusi Baru
                    </div>
                    
                    <div id="historyScroll">
                        <!-- Daftar Diskusi dari LocalStorage via chat-ai.js -->
                    </div>
                </div>

                <div class="chat-sidebar-footer">
                    <button class="reset-btn" id="resetBtn">
                        <span class="mat">restart_alt</span>
                        Reset Diskusi
                    </button>
                </div>
            </aside>

            <!-- Sidebar Overlay backdrop for mobile -->
            <div class="chat-sidebar-overlay" id="sidebarOverlay"></div>

            <!-- Main Chat Arena -->
            <main class="chat-area">
                <div class="chat-header">
                    <button class="chat-sidebar-toggle" id="sidebarToggle">
                        <span class="mat">menu</span>
                    </button>
                    <div class="chat-header-icon">
                        <span class="mat">cognition</span>
                    </div>
                    <div class="chat-header-info">
                        <div class="chat-header-title">Ruang Diskusi AI</div>
                        <div class="chat-header-sub" id="chatHeaderSub">3 konsultan aktif</div>
                    </div>
                </div>

                <!-- Chat Feed Messages -->
                <div class="chat-feed" id="chatFeed">
                    <!-- Obrolan dirender di sini via chat-ai.js -->
                </div>

                <!-- Input Area obrolan -->
                <div class="input-area">
                    <div class="input-row">
                        <div class="input-box">
                            <textarea id="chatInput" placeholder="Tulis tanggapanmu..." rows="1"></textarea>
                        </div>
                        
                        <button class="send-btn" id="sendBtn" title="Kirim pesan">
                            <span class="mat">send</span>
                        </button>
                    </div>
                </div>
            </main>

        </div>
    </div>

    <!-- pickerModal: Modal Pilihan Lomba -->
    <div class="modal-overlay" id="pickerModal" style="display: none;">
        <div class="modal-sheet">
            <div class="modal-handle"></div>
            <div class="modal-header">
                <div>
                    <div class="modal-title">Mulai Diskusi Baru</div>
                    <div class="modal-sub">Pilih salah satu kompetisi aktif untuk dibahas</div>
                </div>
                <button class="modal-close-btn" id="closeModalBtn">
                    <span class="mat">close</span>
                </button>
            </div>
            
            <div class="modal-search">
                <div class="modal-search-inner">
                    <span class="mat">search</span>
                    <input type="text" id="modalSearchInput" placeholder="Cari kompetisi...">
                </div>
            </div>

            <!-- Scrollable list -->
            <div class="modal-list" id="modalList">
                <!-- Diisi via chat-ai.js -->
            </div>

            <div style="padding: 16px; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 8px;">
                <div style="text-align: center; font-size: 11px; color: var(--text-ter);">ATAU</div>
                <button class="reset-btn" id="externalChatBtn" style="background: var(--blue-soft); border-color: #c5daf5; color: var(--blue); padding: 11px;">
                    <span class="mat">chat</span>
                    Konsultasi Lomba Luar (Eksternal)
                </button>
            </div>
        </div>
    </div>

    <!-- Inject data dari server PHP ke Javascript -->
    <script>
        window.currentUserProfilePicture = "<?= htmlspecialchars($user_pic) ?>";
        window.dbCompetitions = <?= json_encode($competitions) ?>;
    </script>

    <!-- JS Dependencies -->
    <script src="../assets/js/chat-ai.js?v=<?= filemtime(__DIR__ . '/../assets/js/chat-ai.js') ?>"></script>

</body>
</html>
