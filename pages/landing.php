<?php
session_start();
include "../config/koneksi.php";

$search   = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? 'All';

$allowed_categories = ['Design', 'Programming', 'Hacking'];

$sql    = "SELECT * FROM competitions WHERE 1=1";
$types  = "";
$params = [];

if (!empty($search)) {
    $sql    .= " AND title LIKE ?";
    $types  .= "s";
    $params[] = "%" . $search . "%";
}

if ($category !== 'All' && in_array($category, $allowed_categories)) {
    $sql    .= " AND category = ?";
    $types  .= "s";
    $params[] = $category;
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
    <link rel="stylesheet" href="../Assets/css/landing.css">
</head>
<body>

<header class="hero">
    <div class="navbar">
        <div class="logo">
            <img src="../Assets/images/logo.jpeg" alt="logo" class="logo-img"> P Info
        </div>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
                <span>Halo, <?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            <?php else: ?>
                <button class="login"  onclick="window.location.href='login.php'">Login</button>
                <button class="signup" onclick="window.location.href='register.php'">Sign Up</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="hero-content">
        <h1>Find competitions <br>that match your skills</h1>
        <form class="search-wrapper" method="GET" action="landing.php" id="filterForm">
            <div class="search-box">
                <input type="text" name="search" id="searchInput" placeholder="Search..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    oninput="debounceSubmit()">
            </div>
            <select name="category" id="categoryFilter" onchange="document.getElementById('filterForm').submit()">
                <option value="All"         <?php echo $category === 'All'         ? 'selected' : ''; ?>>Semua Kategori</option>
                <option value="Design"      <?php echo $category === 'Design'      ? 'selected' : ''; ?>>Design</option>
                <option value="Programming" <?php echo $category === 'Programming' ? 'selected' : ''; ?>>Programming</option>
                <option value="Hacking"     <?php echo $category === 'Hacking'     ? 'selected' : ''; ?>>Hacking</option>
            </select>
        </form>
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
                 onerror="this.src='../Assets/images/logo.jpeg'">
            <div class="card-body">
                <span class="badge badge-<?php echo strtolower(htmlspecialchars($row['category'])); ?>">
                    <?php echo htmlspecialchars($row['category']); ?>
                </span>
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <div class="card-meta">
                    <span>📍 <?php echo htmlspecialchars($row['location']); ?></span>
                    <span>📅 <?php echo htmlspecialchars($row['date_range']); ?></span>
                </div>
                <button class="btn-detail" onclick="openDetail(
                    '<?php echo addslashes(htmlspecialchars($row['title'])); ?>',
                    '../Assets/images/<?php echo addslashes(htmlspecialchars($row['image'])); ?>',
                    '<?php echo addslashes(htmlspecialchars($row['location'])); ?>',
                    '<?php echo addslashes(htmlspecialchars($row['date_range'])); ?>',
                    '<?php echo addslashes(htmlspecialchars($row['description'])); ?>'
                )">More Detail</button>
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

<!-- Modal -->
<div class="modal" id="detailModal">
    <div class="modal-content">
        <div class="close-btn" onclick="closeDetail()">✕</div>
        <div class="modal-top">
            <img id="modalImage" alt="Competition Image">
            <div class="modal-info">
                <h2 id="modalTitle"></h2>
                <p id="modalLocation"></p>
                <p id="modalDate"></p>
                <button class="register-btn">Register Now</button>
            </div>
        </div>
        <div class="modal-description" id="modalDescription"></div>
        <div class="organizer">
            <img src="../Assets/images/logo.jpeg" alt="Organizer Logo">
            <div>
                <h4>Diselenggarakan oleh EduNation</h4>
                <p>Organisasi pendidikan nasional yang fokus pada pengembangan siswa Indonesia.</p>
            </div>
        </div>
    </div>
</div>

<section class="bottom-cta">
    <h2>Unlock Your Potential</h2>
    <p>Temukan berbagai kompetisi terbaik untuk meningkatkan skill dan membangun portofolio.</p>
</section>

<script src="../Assets/js/landing.js"></script>
</body>
</html>