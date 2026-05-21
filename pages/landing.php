<?php
include "./session_check.php";
include "../config/koneksi.php";

$search     = trim($_GET['search'] ?? '');
$categories = $_GET['categories'] ?? []; // Mengambil array kategori

$allowed_categories = ['Design', 'Programming', 'Hacking'];

// Ambil username login saat ini
$current_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? '';

// 1. Susun Query Utama dengan LEFT JOIN ke tabel bookmarks & users secara aman
$sql = "SELECT c.*, IF(b.id IS NOT NULL, 1, 0) as is_bookmarked 
        FROM competitions c 
        LEFT JOIN bookmarks b ON c.id = b.competition_id 
        LEFT JOIN users u ON b.user_id = u.id AND u.username = ?
        WHERE 1=1";

$params = [$current_user];
$types = "s";

// 2. Filter Pencarian Nama Lomba
if (!empty($search)) {
    $sql .= " AND c.title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// 3. Filter Kategori Lomba
if (!empty($categories)) {
    $valid_cats = [];
    foreach ($categories as $cat) {
        if (in_array($cat, $allowed_categories)) {
            $valid_cats[] = $cat;
        }
    }
    
    if (!empty($valid_cats)) {
        $placeholders = implode(',', array_fill(0, count($valid_cats), '?'));
        $sql .= " AND c.category IN (" . $placeholders . ")";
        foreach ($valid_cats as $cat) {
            $params[] = $cat;
            $types .= "s";
        }
    }
}

// Group by id kompetisi agar data tidak double jika ada user lain yang membukmark lomba yang sama
$sql .= " GROUP BY c.id";

$stmt = mysqli_prepare($koneksi, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
                    <input type="text" name="search" id="searchInput" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="category-dropdown">
                    <button type="button" class="btn-category-trigger" onclick="toggleCategoryPopup('categoryPopupHero')">
                        Kategori ▾
                    </button>
                    <div id="categoryPopupHero" class="category-popup-content" style="display: none; position: absolute; background: white; color: #333; padding: 12px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 160px; z-index: 50; text-align: left; margin-top: 8px;">
                        <?php foreach ($allowed_categories as $cat): ?>
                            <label class="category-item" style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; cursor: pointer; color: #333;">
                                <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat) ?>"
                                    <?= (isset($_GET['categories']) && in_array($cat, $_GET['categories'])) ? 'checked' : '' ?>
                                    onchange="updateSelectedBadges(); this.form.submit();">
                                <span style="font-size: 14px;"><?= htmlspecialchars($cat) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn-search-submit">🔍 Cari</button>
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
            include 'card_competition.php'; 
        endwhile; 
        ?>

        <?php if ($count === 0): ?>
        <p style="color:#666; grid-column:1/-1; text-align:center; padding:40px 0;">
            Tidak ada kompetisi yang ditemukan.
        </p>
        <?php endif; ?>
    </div>
</section>

<section class="bottom-cta">
    <h2>Unlock Your Potential</h2>
    <p>Temukan berbagai kompetisi terbaik untuk meningkatkan skill dan membangun portofolio.</p>
</section>

<script src="../Assets/js/landing.js?v=<?= time(); ?>"></script>
<script src="../Assets/js/floating_search.js?v=<?= time(); ?>"></script>
</body>
</html>