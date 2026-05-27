<?php
include "./session-check.php";
include "../config/database.php";

$search     = trim($_GET['search'] ?? '');
$categories = $_GET['categories'] ?? []; // Retrieve categories array

// Fetch categories dynamically from database
$allowed_categories = [];
$cat_query = mysqli_query($koneksi, "SELECT name FROM categories ORDER BY name ASC");
if ($cat_query) {
    while ($cat_row = mysqli_fetch_assoc($cat_query)) {
        $allowed_categories[] = $cat_row['name'];
    }
}
if (empty($allowed_categories)) {
    $allowed_categories = ['Design', 'Programming', 'Hacking'];
}

$sql = "SELECT * FROM competitions WHERE submission_status = 'published'";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND title LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if (!empty($categories)) {
    $cat_clauses = [];
    foreach ($categories as $cat) {
        if (in_array($cat, $allowed_categories)) {
            $cat_clauses[] = "category LIKE ?";
            $params[] = "%" . $cat . "%";
            $types .= "s";
        }
    }
    if (!empty($cat_clauses)) {
        $sql .= " AND (" . implode(" OR ", $cat_clauses) . ")";
    }
}

$stmt = mysqli_prepare($koneksi, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$saved_competitions = [];
if (isset($_SESSION['status']) && $_SESSION['status'] === 'login' && isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Temukan lomba dan kompetisi yang sesuai dengan skill kamu di Pasti Info — platform informasi lomba terlengkap.">
    <title>Beranda — Pasti Info | Platform Informasi Lomba</title>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../assets/css/global.css') ?>">
    <link rel="stylesheet" href="../assets/css/home.css?v=<?= filemtime(__DIR__ . '/../assets/css/home.css') ?>">
    <link rel="stylesheet" href="../assets/css/navbar.css?v=<?= filemtime(__DIR__ . '/../assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../assets/css/floating-search.css?v=<?= filemtime(__DIR__ . '/../assets/css/floating-search.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
</head>
<body>

<header class="hero">
    <?php include '../components/navbar.php'; ?>
    <div class="hero-content">
        <h1>Find competitions <br>that match your skills</h1>
        <form class="search-wrapper" method="GET" action="home.php" id="filterForm">
            <div class="search-main-bar">
                <div class="search-box">
                    <span class="search-box-icon">search</span>
                    <input type="text" name="search" id="searchInput" placeholder="Search competitions..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="category-dropdown">
                    <button type="button" class="btn-category-trigger" onclick="toggleCategoryPopup('categoryPopupHero')">
                        Category ▾
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
                <button type="submit" class="btn-search-submit">Search</button>
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
            include '../components/competition-card.php'; 
        endwhile; 
        ?>

        <?php if ($count === 0): ?>
        <p style="color:#666; grid-column:1/-1; text-align:center; padding:40px 0;">
            No competitions found.
        </p>
        <?php endif; ?>
    </div>
    <div id="loadMoreContainer" style="display: none; text-align: center; margin-top: 36px;">
        <button id="loadMoreBtn" class="btn-search-submit" style="padding: 12px 28px; font-size: 15px; border-radius: 99px;">
            Load More ▾
        </button>
    </div>
</section>

<?php include '../components/footer.php'; ?>

<script src="../assets/js/home.js?v=<?= filemtime(__DIR__ . '/../assets/js/home.js') ?>"></script>
<script src="../assets/js/floating-search.js?v=<?= filemtime(__DIR__ . '/../assets/js/floating-search.js') ?>"></script>
<script src="../assets/js/bookmark.js?v=<?= filemtime(__DIR__ . '/../assets/js/bookmark.js') ?>"></script>
<?php if (!isset($_SESSION['status']) || $_SESSION['status'] !== "login"): ?>
<script>
    setTimeout(function() {
        window.location.href = "auth.php?error=" + encodeURIComponent("Session expired! Please login now.");
    }, 60000); // 60 seconds
</script>
<?php endif; ?>
</body>
</html>
