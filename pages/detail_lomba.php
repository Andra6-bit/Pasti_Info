<?php
include "./session_check.php";
include "../config/koneksi.php";

$id = trim($_GET['id'] ?? '');
if ($id === '') {
    header("Location: landing.php");
    exit();
}

if (ctype_digit($id)) {
    $sql = "SELECT * FROM competitions WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
} else {
    $sql = "SELECT * FROM competitions WHERE title = ? LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "s", $id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$lomba = mysqli_fetch_assoc($result);
if (!$lomba) {
    header("Location: landing.php");
    exit();
}

$saved_lomba = false;
if (isset($_SESSION['status']) && $_SESSION['status'] === 'login') {
    $current_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? '';
    if ($current_user !== '') {
        $userStmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ? LIMIT 1");
        if ($userStmt) {
            mysqli_stmt_bind_param($userStmt, "s", $current_user);
            mysqli_stmt_execute($userStmt);
            $userResult = mysqli_stmt_get_result($userStmt);
            $userRow = $userResult ? mysqli_fetch_assoc($userResult) : null;
            if ($userRow) {
                $savedStmt = mysqli_prepare($koneksi, "SELECT id FROM saved_competitions WHERE user_id = ? AND competition_id = ? LIMIT 1");
                if ($savedStmt) {
                    mysqli_stmt_bind_param($savedStmt, "ii", $userRow['id'], $lomba['id']);
                    mysqli_stmt_execute($savedStmt);
                    $savedResult = mysqli_stmt_get_result($savedStmt);
                    $saved_lomba = $savedResult && mysqli_num_rows($savedResult) > 0;
                }
            }
        }
    }
}

function safeText($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$imagePath = '../Assets/images/' . ($lomba['image'] ? safeText($lomba['image']) : 'logo_putih.svg');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lomba - <?php echo safeText($lomba['title']); ?></title>
    <link rel="stylesheet" href="../Assets/css/landing.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/navbar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/floating_search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/detail_lomba.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'navbar.php'; ?>

<main class="detail-page">

    <!-- TOP: Poster + Info -->
    <div class="detail-header">

        <!-- KIRI: Poster -->
        <div class="poster-wrapper" onclick="openLightbox()">
            <img
                src="<?php echo $imagePath; ?>"
                alt="<?php echo safeText($lomba['title']); ?>"
                onerror="this.src='../Assets/images/logo_putih.svg'"
                id="posterImg"
            >
            <span class="poster-zoom-hint">🔍 Klik untuk perbesar</span>
        </div>

        <!-- KANAN: Info Card -->
        <div class="detail-info-card">

            <h1 class="detail-title">
                <?php echo safeText($lomba['title']); ?>
            </h1>

            <div class="meta-list">
                <div class="meta-row">
                    <div class="meta-icon">📍</div>
                    <div class="meta-text">
                        <span class="meta-label">Pelaksanaan</span>
                        <span class="meta-value"><?php echo safeText($lomba['pelaksanaan'] ?? $lomba['location'] ?? 'Online/Offline'); ?></span>
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-icon">📅</div>
                    <div class="meta-text">
                        <span class="meta-label">Tanggal</span>
                        <span class="meta-value"><?php echo safeText($lomba['date_range'] ?? '-'); ?></span>
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-icon">👥</div>
                    <div class="meta-text">
                        <span class="meta-label">Target Peserta</span>
                        <span class="meta-value"><?php echo safeText($lomba['target_peserta'] ?? $lomba['target'] ?? 'Umum'); ?></span>
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-icon">💰</div>
                    <div class="meta-text">
                        <span class="meta-label">Biaya Pendaftaran</span>
                        <span class="meta-value"><?php echo isset($lomba['biaya']) && is_numeric($lomba['biaya']) && $lomba['biaya'] > 0 ? 'Rp ' . number_format($lomba['biaya'], 0, ',', '.') : 'Gratis'; ?></span>
                    </div>
                </div>
            </div>

            <span class="detail-category-badge">
               <?php echo safeText($lomba['category'] ?? 'Umum'); ?>
            </span>

            <div class="detail-actions">
                <a
                   class="btn-register">
                    Daftar Sekarang
                </a>
                
                <button
                    type="button"
                    class="btn-bookmark"
                    title="Simpan lomba"
                    style="color: <?php echo $saved_lomba ? '#e53e3e' : '#333'; ?>;"
                    onclick="toggleBookmark(<?php echo intval($lomba['id']); ?>, this);"
                >
                    <?php echo $saved_lomba ? '♥' : '♡'; ?>
                </button>
            </div>

        </div>
    </div>

    <!-- BOTTOM: More Detail -->
    <div class="more-detail">
        <div class="more-detail-header">
            <span class="dot"></span>
            <h2>More Detail</h2>
        </div>
        <div class="more-detail-body">
            <p class="detail-description">
                <?php echo nl2br(safeText($lomba['description'] ?? 'Deskripsi belum tersedia.')); ?>
            </p>
        </div>
    </div>

</main>

<!-- LIGHTBOX MODAL -->
<div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">✕</button>
    <img
        src="<?php echo $imagePath; ?>"
        alt="Preview poster"
        onclick="event.stopPropagation()"
        onerror="this.src='../Assets/images/logo_putih.svg'"
    >
</div>

<script>
    function openLightbox() {
        document.getElementById('lightboxOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        document.getElementById('lightboxOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }
    // Tutup dengan tombol Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLightbox();
    });
</script>
<script src="../Assets/js/bookmark.js?v=<?php echo time(); ?>"></script>

</body>
</html>
