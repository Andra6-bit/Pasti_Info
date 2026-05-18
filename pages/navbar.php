<header class="site-navbar">
    <nav class="navbar">
        <div class="logo">
            <img src="../Assets/images/logo_putih.svg?v=<?php echo time(); ?>" alt="logo" class="logo-img">
        </div>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
                <span><?php echo htmlspecialchars($_SESSION['user_username']); ?></span>
                <?php if (isset($_SESSION['user_username']) && $_SESSION['user_username'] === 'admin'): ?>
                    <a href="../admin/index.php" class="nav-admin">Dashboard Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="nav-logout">Logout</a>
            <?php else: ?>
                <a href="auth.php" class="nav-login">Login</a>
                <a href="auth.php?tab=register" class="nav-signup">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>