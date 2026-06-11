<?php
include "./session-check.php";
include "../config/database.php";

$id = trim($_GET['id'] ?? '');
if ($id === '') {
    header("Location: home.php");
    exit();
}

if (ctype_digit($id)) {
    // 1-line reason: Fetch category values dynamically from normalized tables to replace the redundant category column.
    $sql = "SELECT *, (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') FROM competition_categories cc JOIN categories c ON cc.category_id = c.id WHERE cc.competition_id = competitions.id) as category FROM competitions WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
} else {
    // 1-line reason: Fetch category values dynamically from normalized tables to replace the redundant category column.
    $sql = "SELECT *, (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') FROM competition_categories cc JOIN categories c ON cc.category_id = c.id WHERE cc.competition_id = competitions.id) as category FROM competitions WHERE title = ? AND submission_status = 'published' LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "s", $id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$lomba = mysqli_fetch_assoc($result);
if (!$lomba) {
    header("Location: home.php");
    exit();
}

$saved_lomba = false;
if (isset($_SESSION['status']) && $_SESSION['status'] === 'login' && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $savedStmt = mysqli_prepare($koneksi, "SELECT id FROM saved_competitions WHERE user_id = ? AND competition_id = ? LIMIT 1");
    if ($savedStmt) {
        mysqli_stmt_bind_param($savedStmt, "ii", $user_id, $lomba['id']);
        mysqli_stmt_execute($savedStmt);
        $savedResult = mysqli_stmt_get_result($savedStmt);
        $saved_lomba = $savedResult && mysqli_num_rows($savedResult) > 0;
    }
}

function safeText($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$imagePath = '../assets/images/' . ($lomba['image'] ? safeText($lomba['image']) : 'logo_putih.svg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Detail informasi lomba <?php echo safeText($lomba['title']); ?> — Pasti Info.">
    <title><?php echo safeText($lomba['title']); ?> — Pasti Info</title>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../assets/css/global.css') ?>">
    <link rel="stylesheet" href="../assets/css/home.css?v=<?= filemtime(__DIR__ . '/../assets/css/home.css') ?>">
    <link rel="stylesheet" href="../assets/css/navbar.css?v=<?= filemtime(__DIR__ . '/../assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../assets/css/floating-search.css?v=<?= filemtime(__DIR__ . '/../assets/css/floating-search.css') ?>">
    <link rel="stylesheet" href="../assets/css/competition-details.css?v=<?= filemtime(__DIR__ . '/../assets/css/competition-details.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>

<?php include '../components/navbar.php'; ?>

<main class="detail-page">

    <!-- TOP: Poster + Info -->
    <div class="detail-header">

        <!-- LEFT: Poster -->
        <div class="poster-wrapper" onclick="openLightbox()">
            <img
                src="<?php echo $imagePath; ?>"
                alt="<?php echo safeText($lomba['title']); ?>"
                onerror="this.src='../assets/images/logo_putih.svg'"
                id="posterImg"
            >
            <span class="poster-zoom-hint">🔍 Click to enlarge</span>
        </div>

        <!-- RIGHT: Info Card -->
        <div class="detail-info-card">

            <h1 class="detail-title">
                <?php echo safeText($lomba['title']); ?>
            </h1>

            <div class="meta-list">
                <div class="meta-row">
                    <div class="meta-icon">📍</div>
                    <div class="meta-text">
                        <span class="meta-label">Format</span>
                        <span class="meta-value"><?php echo safeText(!empty($lomba['format']) ? $lomba['format'] : 'Online'); ?></span>
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-icon">📅</div>
                    <div class="meta-text">
                        <span class="meta-label">Date</span>
                        <span class="meta-value"><?php
                            $dr_detail = $lomba['date_range'] ?? '';
                            $tampil_tanggal = '';
                            if (str_contains($dr_detail, ',')) {
                                [$dd1, $dd2] = explode(',', $dr_detail, 2);
                                $ts1 = strtotime(trim($dd1));
                                $ts2 = strtotime(trim($dd2));
                                if ($ts1 && $ts2) {
                                    $df1 = date('d M Y', $ts1);
                                    $df2 = date('d M Y', $ts2);
                                    $tampil_tanggal = ($df1 === $df2) ? $df1 : $df1 . ' — ' . $df2;
                                } else {
                                    $tampil_tanggal = $dr_detail;
                                }
                            } elseif (str_contains($dr_detail, '/')) {
                                $dp = explode('-', $dr_detail);
                                if (count($dp) === 2) {
                                    $ts1 = strtotime(str_replace('/', '-', trim($dp[0])));
                                    $ts2 = strtotime(str_replace('/', '-', trim($dp[1])));
                                    if ($ts1 && $ts2) {
                                        $df1 = date('d M Y', $ts1);
                                        $df2 = date('d M Y', $ts2);
                                        $tampil_tanggal = ($df1 === $df2) ? $df1 : $df1 . ' — ' . $df2;
                                    } else {
                                        $tampil_tanggal = $dr_detail;
                                    }
                                } else {
                                    $tampil_tanggal = $dr_detail;
                                }
                            } else {
                                $tampil_tanggal = $dr_detail;
                            }
                            echo htmlspecialchars($tampil_tanggal);
                        ?></span>
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-icon">👥</div>
                    <div class="meta-text">
                        <span class="meta-label">Target Audience</span>
                        <span class="meta-value"><?php echo safeText(!empty($lomba['target_audience']) ? $lomba['target_audience'] : 'General'); ?></span>
                    </div>
                </div>
                <div class="meta-row">
                    <div class="meta-icon">💰</div>
                    <div class="meta-text">
                        <span class="meta-label">Registration Fee</span>
                        <span class="meta-value"><?php echo isset($lomba['registration_fee']) && is_numeric($lomba['registration_fee']) && $lomba['registration_fee'] > 0 ? 'Rp ' . number_format($lomba['registration_fee'], 0, ',', '.') : 'Free'; ?></span>
                    </div>
                </div>
            </div>

            <div class="detail-categories-wrap">
                <?php
                $cats_detail = explode(',', $lomba['category'] ?? '');
                $has_cat = false;
                foreach ($cats_detail as $cn):
                    $cn = trim($cn);
                    if (empty($cn)) continue;
                    $has_cat = true;
                ?>
                <span class="detail-category-badge">
                    <?php echo safeText($cn); ?>
                </span>
                <?php endforeach; ?>
                <?php if (!$has_cat): ?>
                <span class="detail-category-badge">General</span>
                <?php endif; ?>
            </div>

            <div class="detail-actions">
                <?php if (!empty($lomba['registration_link'])): ?>
                <a href="<?php echo safeText($lomba['registration_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn-register">
                    <i class="ti ti-external-link" style="font-size:15px"></i> Register Now
                </a>
                <?php else: ?>
                <a href="#" class="btn-register" style="opacity: 0.5; cursor: not-allowed;"
                   onclick="event.preventDefault(); alert('Registration link is not available.');">
                    Register Now
                </a>
                <?php endif; ?>

                <!-- 1-line reason: Rename the button to Debate AI and replace the robot icon with debate swords. -->
                <a href="debate-arena.php?competition_id=<?php echo urlencode($lomba['id']); ?>" class="btn-chat-ai">
                    <i class="ti ti-swords" style="font-size:18px;vertical-align:middle;margin-right:6px;"></i> Debate AI
                </a>
                
                <button
                    type="button"
                    class="btn-bookmark<?php echo $saved_lomba ? ' saved' : ''; ?>"
                    title="Save competition"
                    onclick="toggleBookmark(<?php echo intval($lomba['id']); ?>, this);"
                >
                    <?php echo $saved_lomba ? '♥' : '♡'; ?>
                </button>
            </div>

        </div>
    </div>

    <div class="more-detail">
        <div class="more-detail-header">
            <span class="dot"></span>
            <h2>More Details</h2>
        </div>
        <div class="more-detail-body">
            <p class="detail-description"><?php echo safeText($lomba['description'] ?? 'Description is not available.'); ?></p>
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
        onerror="this.src='../assets/images/logo_putih.svg'"
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
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLightbox();
    });
</script>
<script src="../assets/js/bookmark.js?v=<?= filemtime(__DIR__ . '/../assets/js/bookmark.js') ?>"></script>

</body>
</html>
