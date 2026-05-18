<header class="site-navbar">
    <nav class="navbar">
        <div class="logo">
            <img src="../Assets/images/logo_putih.svg?v=<?php echo time(); ?>" alt="logo" class="logo-img">
        </div>

        <div class="navbar-search" id="navbarSearch">
            <form method="GET" action="landing.php" style="display: flex; align-items: center; gap: 8px;">
                <div class="search-box" style="margin: 0;">
                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="category-dropdown">
                    <button type="button" class="btn-category-trigger btn-category-trigger--dark" onclick="toggleCategoryPopup()">
                        Kategori ▾
                    </button>
                    <div id="categoryPopup" class="category-popup-content">
                        <?php
                        $allowed_categories = ['Design', 'Programming', 'Hacking'];
                        foreach ($allowed_categories as $cat): ?>
                            <label class="category-item">
                                <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat) ?>"
                                    <?= (isset($_GET['categories']) && in_array($cat, $_GET['categories'])) ? 'checked' : '' ?> >
                                <span><?= htmlspecialchars($cat) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn-search-submit btn-search-submit--dark">🔍</button>
            </form>
        </div>

        <div class="auth-buttons">
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
                
                <?php
                    // Ambil username dengan aman (cek apakah pakai 'username' atau 'user_username')
                    $session_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
                    
                    // Logika penentuan link profile berdasarkan role
                    $profile_link = ($session_user === 'admin') ? '../admin/index.php' : 'profile.php';
                    
                    // Mengambil huruf pertama dari username untuk avatar
                    $initial = strtoupper(substr($session_user, 0, 1));
                ?>
                
                <a href="landing.php" class="nav-beranda">Beranda</a>

                <a href="<?= $profile_link ?>" class="nav-profile-btn" title="Halo, <?= htmlspecialchars($session_user) ?>">
                    <div class="nav-avatar-circle"><?= $initial ?></div>
                    <span class="nav-profile-text">Profil</span>
                </a>

            <?php else: ?>
                <a href="auth.php" class="nav-login">Login</a>
                <a href="auth.php?tab=register" class="nav-signup">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
