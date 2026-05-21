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

// Tambahkan pengecekan status bookmark untuk user yang aktif
$is_bookmarked = false;
if (isset($_SESSION['status']) && $_SESSION['status'] === "login") {
    $current_username = $_SESSION['username'] ?? $_SESSION['user_username'] ?? '';
    $bookmark_check = mysqli_prepare($koneksi, "SELECT b.id FROM bookmarks b JOIN users u ON b.user_id = u.id WHERE u.username = ? AND b.competition_id = ?");
    mysqli_stmt_bind_param($bookmark_check, "si", $current_username, $lomba['id']);
    mysqli_stmt_execute($bookmark_check);
    $res_check = mysqli_stmt_get_result($bookmark_check);
    if (mysqli_num_rows($res_check) > 0) {
        $is_bookmarked = true;
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
                        <span class="meta-label">Lokasi</span>
                        <span class="meta-value"><?php echo safeText($lomba['location'] ?? 'Online/Offline'); ?></span>
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
                        <span class="meta-value"><?php echo safeText($lomba['target'] ?? 'Umum'); ?></span>
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-icon">💰</div>
                    <div class="meta-text">
                        <span class="meta-label">Biaya Pendaftaran</span>
                        <span class="meta-value"><?php echo safeText($lomba['price'] ?? 'Gratis'); ?></span>
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
                    style="<?php echo $is_bookmarked ? 'background: #fff7ed; border-color: #f6a623; color: #f6a623;' : ''; ?>"
                    onclick="toggleBookmark(this, <?php echo $lomba['id']; ?>, true)"
                    data-active="<?php echo $is_bookmarked ? 'true' : 'false'; ?>"
                >
                    <?php echo $is_bookmarked ? '★' : '☆'; ?>
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

    function toggleBookmark(button, competitionId, useHighlight = false) {
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
                if (useHighlight) {
                    button.style.background = isNowBookmarked ? '#fff7ed' : '';
                    button.style.borderColor = isNowBookmarked ? '#f6a623' : '';
                    button.style.color = isNowBookmarked ? '#f6a623' : '';
                }
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
