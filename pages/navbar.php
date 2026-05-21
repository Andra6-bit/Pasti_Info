<header class="site-navbar">
    <nav class="navbar">
        <div class="logo">
            <img src="../Assets/images/logo_putih.svg?v=<?php echo time(); ?>" alt="logo" class="logo-img">
        </div>

        <?php include 'floating_search.php'; ?>

        <div class="auth-buttons">
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
                
                <?php
                    // Ambil username dengan aman (cek apakah pakai 'username' atau 'user_username')
                    $session_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
                    
                    // Logika penentuan link profile berdasarkan role
                    $profile_link = ($session_user === 'admin') ? '../admin/admin.php' : 'profile.php';

                    // Link beranda disesuaikan dengan lokasi halaman saat ini
                    $current_dir = dirname($_SERVER['SCRIPT_NAME']);
                    $home_link = (strpos($current_dir, '/admin') !== false) ? '../pages/landing.php' : 'landing.php';
                    
                    // Mengambil huruf pertama dari username untuk avatar
                    $initial = strtoupper(substr($session_user, 0, 1));
                ?>
                
                <a href="<?= $home_link ?>" class="nav-beranda">Beranda</a>

                <a href="<?= $profile_link ?>" class="nav-avatar-button" title="Halo, <?= htmlspecialchars($session_user) ?>">
                    <?= $initial ?>
                </a>

            <?php else: ?>
                <a href="auth.php" class="nav-login">Login</a>
                <a href="auth.php?tab=register" class="nav-signup">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
