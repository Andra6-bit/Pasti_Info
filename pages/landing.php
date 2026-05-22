<?php
include "./session_check.php";
include "../config/koneksi.php";

$search     = trim($_GET['search'] ?? '');
$categories = $_GET['categories'] ?? []; // Mengambil array kategori

$allowed_categories = ['Design', 'Programming', 'Hacking'];

$sql = "SELECT * FROM competitions WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if (!empty($categories)) {
    // Membuat placeholder (?,?,?) sesuai jumlah kategori yang dipilih
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $sql .= " AND category IN (" . $placeholders . ")";
    
    foreach ($categories as $cat) {
        if (in_array($cat, $allowed_categories)) {
            $params[] = $cat;
            $types .= "s";
        }
    }
}

$stmt = mysqli_prepare($koneksi, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$saved_competitions = [];
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
                $user_id = $userRow['id'];
                $savedStmt = mysqli_prepare($koneksi, "SELECT competition_id FROM saved_competitions WHERE user_id = ?");
                if ($savedStmt) {
                    mysqli_stmt_bind_param($savedStmt, "i", $user_id);
                    mysqli_stmt_execute($savedStmt);
                    $savedResult = mysqli_stmt_get_result($savedStmt);
                    while ($savedRow = mysqli_fetch_assoc($savedResult)) {
                        $saved_competitions[] = (int)$savedRow['competition_id'];
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competition Info - P Info</title>
    <link rel="stylesheet" href="../Assets/css/landing.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/navbar.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/floating_search.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>

<?php include 'navbar.php'; ?>

<header class="hero">
    <div class="hero-content">
        <h1>Find competitions <br>that match your skills</h1>
        <form class="search-wrapper" method="GET" action="landing.php" id="filterForm">
            <div class="search-main-bar">
                <div class="search-box">
                    <span class="search-box-icon">search</span>
                    <input type="text" name="search" id="searchInput" placeholder="Cari kompetisi..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="category-dropdown">
                    <button type="button" class="btn-category-trigger" onclick="toggleCategoryPopup('categoryPopupHero')">
                        Kategori ▾
                    </button>
                    <div id="categoryPopupHero" class="category-popup-content">
                        <?php foreach ($allowed_categories as $cat): ?>
                            <label class="category-item">
                                <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat) ?>"
                                    <?= (isset($_GET['categories']) && in_array($cat, $_GET['categories'])) ? 'checked' : '' ?>
                                    onchange="updateSelectedBadges()">
                                <span><?= htmlspecialchars($cat) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn-search-submit">Cari</button>
            </div>
            <div id="selectedBadges" class="selected-badges-container"></div>
        </form>
    </div>
    <div class="hero-wave">
        <svg viewBox="0 0 1440 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0,60 C200,110 400,20 600,70 C800,120 1000,30 1200,65 C1300,82 1380,55 1440,60 L1440,120 L0,120 Z" fill="rgba(255,255,255,0.15)"/>
            <path d="M0,75 C180,30 350,100 550,60 C750,20 950,90 1150,55 C1300,30 1390,75 1440,70 L1440,120 L0,120 Z" fill="rgba(255,255,255,0.25)"/>
            <path d="M0,90 C150,60 300,110 500,80 C700,50 900,100 1100,75 C1280,55 1390,90 1440,85 L1440,120 L0,120 Z" fill="#f0f4f9"/>
        </svg>
    </div>
</header>


<section class="section">
    <div class="card-grid" id="cardGrid">
        <?php
        $count = 0;
        while ($row = mysqli_fetch_assoc($result)):
            $count++;

            // Cukup panggil nama filenya langsung karena berada di folder yang sama (pages/)
            include 'card_competition.php'; 

        endwhile; 
        ?>

        <?php if ($count === 0): ?>
        <p style="color:#666; grid-column:1/-1; text-align:center; padding:40px 0;">
            Tidak ada kompetisi yang ditemukan.
        </p>
        <?php endif; ?>
    </div> <div id="loadMoreContainer" style="display: none; text-align: center; margin-top: 36px;">
        <button id="loadMoreBtn" class="btn-search-submit" style="padding: 12px 28px; font-size: 15px; border-radius: 99px;">
            Muat Lebih Banyak ▾
        </button>
    </div>

</section>

<footer class="site-footer">
    <div class="footer-top">

        <div class="footer-brand-section">
            <div class="footer-brand-name">Keluar Zona Nyaman</div>
            <p class="footer-brand-tagline">Platform informasi lomba & kompetisi untuk mendorong generasi muda berani berkompetisi.</p>
        </div>

        <div class="footer-right-section">
            
            <div class="footer-links-col">
                <div class="footer-col-title">Lainnya</div>
                <ul class="footer-links">
                    <li><a href="#">Tentang Kami</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="#">Hubungi Kami</a></li>
                </ul>
            </div>

            <div class="footer-socials-col">
                <div class="footer-col-title">Ikuti Kami</div>
                <div class="footer-socials">
                    <a href="https://instagram.com/" target="_blank" class="footer-social-btn" title="Instagram">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.366.062 2.633.334 3.608 1.308.975.975 1.246 2.242 1.308 3.608.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.062 1.366-.334 2.633-1.308 3.608-.975.975-2.242 1.246-3.608 1.308-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.366-.062-2.633-.334-3.608-1.308-.975-.975-1.246-2.242-1.308-3.608C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.062-1.366.334-2.633 1.308-3.608C4.516 2.497 5.783 2.225 7.15 2.163 8.416 2.105 8.796 2.163 12 2.163zm0-2.163C8.741 0 8.333.014 7.053.072 5.197.157 3.355.673 2.014 2.014.673 3.355.157 5.197.072 7.053.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.085 1.856.601 3.698 1.942 5.039 1.341 1.341 3.183 1.857 5.039 1.942C8.333 23.986 8.741 24 12 24s3.667-.014 4.947-.072c1.856-.085 3.698-.601 5.039-1.942 1.341-1.341 1.857-3.183 1.942-5.039.058-1.28.072-1.689.072-4.948 0-3.259-.014-3.667-.072-4.947-.085-1.856-.601-3.698-1.942-5.039C20.645.673 18.803.157 16.947.072 15.667.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.162a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                    </a>
                    <a href="https://twitter.com/" target="_blank" class="footer-social-btn" title="Twitter / X">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="https://discord.com/" target="_blank" class="footer-social-btn" title="Discord">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.3698a19.7913 19.7913 0 00-4.8851-1.5152.0741.0741 0 00-.0785.0371c-.211.3753-.4447.8648-.6083 1.2495-1.8447-.2762-3.68-.2762-5.4868 0-.1636-.3933-.4058-.8742-.6177-1.2495a.077.077 0 00-.0785-.037 19.7363 19.7363 0 00-4.8852 1.515.0699.0699 0 00-.0321.0277C.5334 9.0458-.319 13.5799.0992 18.0578a.0824.0824 0 00.0312.0561c2.0528 1.5076 4.0413 2.4228 5.9929 3.0294a.0777.0777 0 00.0842-.0276c.4616-.6304.8731-1.2952 1.226-1.9942a.076.076 0 00-.0416-.1057c-.6528-.2476-1.2743-.5495-1.8722-.8923a.077.077 0 01-.0076-.1277c.1258-.0943.2517-.1923.3718-.2914a.0743.0743 0 01.0776-.0105c3.9278 1.7933 8.18 1.7933 12.0614 0a.0739.0739 0 01.0785.0095c.1202.099.246.1981.3728.2924a.077.077 0 01-.0066.1276 12.2986 12.2986 0 01-1.873.8914.0766.0766 0 00-.0407.1067c.3604.698.7719 1.3628 1.225 1.9932a.076.076 0 00.0842.0286c1.961-.6067 3.9495-1.5219 6.0023-3.0294a.077.077 0 00.0313-.0552c.5004-5.177-.8382-9.6739-3.5485-13.6604a.061.061 0 00-.0312-.0286zM8.02 15.3312c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9555-2.4189 2.157-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.9555 2.4189-2.1569 2.4189zm7.9748 0c-1.1825 0-2.1569-1.0857-2.1569-2.419 0-1.3332.9554-2.4189 2.1569-2.4189 1.2108 0 2.1757 1.0952 2.1568 2.419 0 1.3332-.946 2.4189-2.1568 2.4189Z"/></svg>
                    </a>
                    <a href="https://github.com/" target="_blank" class="footer-social-btn" title="GitHub">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57C20.565 21.795 24 17.31 24 12c0-6.63-5.37-12-12-12z"/></svg>
                    </a>
                </div>
            </div>
        </div>

    </div>
    
    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> PT Keluar Zona Nyaman. All rights reserved.</span>
        <span>Made with ♥ for the next generation of competitors</span>
    </div>
</footer>

<script src="../Assets/js/landing.js?v=<?= time(); ?>"></script>
<script src="../Assets/js/floating_search.js?v=<?= time(); ?>"></script>
<script src="../Assets/js/bookmark.js?v=<?= time(); ?>"></script>
<?php if (!isset($_SESSION['status']) || $_SESSION['status'] !== "login"): ?>
<script>
    setTimeout(function() {
        window.location.href = "auth.php?error=" + encodeURIComponent("Waktu habis! Ayo login sekarang.");
    }, 60000); // 60 detik
</script>
<?php endif; ?>
</body>
</html>