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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Competition Info - P Info</title>
    <link rel="stylesheet" href="../Assets/css/landing.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../Assets/css/modal_detail.css?v=<?php echo time(); ?>"> <!-- tambahkan ini -->
</head>
<body>

<header class="hero">
    <div class="navbar">
        <div class="logo">
            <img src="../Assets/images/logo_putih.svg?v=<?php echo time(); ?>" alt="logo" class="logo-img"> 
        </div>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
                <span>Halo, <?php echo htmlspecialchars($_SESSION['user_username']); ?></span>
                <?php if (isset($_SESSION['user_username']) && $_SESSION['user_username'] === 'admin'): ?>
                    <a href="../admin/index.php" class="login" style="background: var(--accent); color: white !important;">Dashboard Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="logout-btn">Logout</a>
            <?php else: ?>
                <button class="login"  onclick="window.location.href='auth.php'">Login</button>
                <button class="signup" onclick="window.location.href='auth.php?tab=register'">Sign Up</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="hero-content">
        <h1>Find competitions <br>that match your skills</h1>
        <form class="search-wrapper" method="GET" action="landing.php" id="filterForm">
            <!-- Baris Utama: Search + Tombol Kategori + Tombol Cari -->
            <div class="search-main-bar">
                <div class="search-box">
                    <input type="text" name="search" id="searchInput" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <!-- Tombol Pemicu Pop-up Kategori -->
                <div class="category-dropdown">
                    <button type="button" class="btn-category-trigger" onclick="toggleCategoryPopup()">
                        Kategori ▾
                    </button>
                    
                    <!-- Isi Pop-up Kategori -->
                    <div id="categoryPopup" class="category-popup-content">
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

                <button type="submit" class="btn-search-submit">🔍 Cari</button>
            </div>

            <!-- Baris Bawah: Menampilkan Kategori yang sedang terpilih -->
            <div id="selectedBadges" class="selected-badges-container">
                <!-- Akan diisi otomatis oleh JS -->
            </div>
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
        ?>
        <div class="card">
            <img src="../Assets/images/<?php echo htmlspecialchars($row['image']); ?>"
                 alt="<?php echo htmlspecialchars($row['title']); ?>"
                 onerror="this.src='../Assets/images/logo_putih.svg'">
            <div class="card-body">
                <button class="btn-detail" onclick="openDetail(
                    '<?php echo addslashes(htmlspecialchars($row['title'])); ?>',
                    '../Assets/images/<?php echo addslashes(htmlspecialchars($row['image'])); ?>',
                    '<?php echo addslashes(htmlspecialchars($row['location'])); ?>',
                    '<?php echo addslashes(htmlspecialchars($row['date_range'])); ?>',
                    '<?php echo addslashes(htmlspecialchars($row['description'])); ?>'
                )">More Detail</button>

                <div class="card-info">
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p>
                        <?php echo htmlspecialchars($row['target'] ?? 'Umum'); ?><br>
                        <?php echo htmlspecialchars($row['price'] ?? 'Gratis'); ?><br>
                        <?php echo htmlspecialchars($row['location']); ?><br>
                        <?php echo htmlspecialchars($row['date_range']); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

        <?php if ($count === 0): ?>
        <p style="color:#666; grid-column:1/-1; text-align:center; padding:40px 0;">
            Tidak ada kompetisi yang ditemukan.
        </p>
        <?php endif; ?>
    </div>
</section>

<!-- Panggil file detail info di sini -->
<?php include 'modal_detail.php'; ?>

<section class="bottom-cta">
    <h2>Unlock Your Potential</h2>
    <p>Temukan berbagai kompetisi terbaik untuk meningkatkan skill dan membangun portofolio.</p>
</section>

<script src="../Assets/js/landing.js?v=<?= time(); ?>"></script>
<?php if (!isset($_SESSION['status']) || $_SESSION['status'] !== "login"): ?>
<script>
    setTimeout(function() {
        window.location.href = "auth.php?error=" + encodeURIComponent("Waktu habis! Ayo login sekarang.");
    }, 60000); // 60 detik
</script>
<?php endif; ?>
</body>
</html>