<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../Assets/images/logo.jpeg" alt="Logo">
    </div>
    <nav>
        <a href="index.php" class="<?php echo $current === 'index.php' ? 'active' : ''; ?>">
            🏆 &nbsp;Kompetisi
        </a>
        <a href="../pages/landing.php">
            🌐 &nbsp;Lihat Website
        </a>
    </nav>
    <div class="sidebar-bottom">
        <div class="sidebar-user"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></div>
        <a href="../pages/logout.php" class="btn-logout">Logout</a>
    </div>
</aside>