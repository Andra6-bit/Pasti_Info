<header class="site-navbar">
    <nav class="navbar">
        <div class="logo">
            <img src="../assets/images/logo_putih.svg?v=<?= filemtime(__DIR__ . '/../assets/images/logo_putih.svg') ?>" alt="logo" class="logo-img">
        </div>

        <?php include 'floating-search.php'; ?>

        <div class="auth-buttons">
            <?php
                // Adjust home links relative to page directory depth
                $current_dir = dirname($_SERVER['SCRIPT_NAME']);
                $home_link = (strpos($current_dir, '/admin') !== false) ? '../pages/home.php' : 'home.php';
                $auth_link = (strpos($current_dir, '/admin') !== false) ? '../pages/auth.php' : 'auth.php';
            ?>
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
                
                <?php
                    // Safely get username from session
                    $session_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
                    $is_admin = (($_SESSION['user_role'] ?? '') === 'admin') || ($session_user === 'admin');
                    
                    // Determine profile link based on role
                    $profile_link = $is_admin
                        ? ((strpos($current_dir, '/admin') !== false) ? 'dashboard.php' : '../admin/dashboard.php')
                        : ((strpos($current_dir, '/admin') !== false) ? '../pages/profile.php' : 'profile.php');

                    // First letter for fallback avatar
                    $initial = strtoupper(substr($session_user, 0, 1));
                ?>
                
                <a href="<?= $home_link ?>" class="nav-beranda">Home</a>
                <?php if (!$is_admin): ?>
                    <a href="javascript:void(0)" onclick="openSubmitCompetitionModal()" class="nav-beranda">Submit Lomba</a>
                <?php endif; ?>

                <a href="<?= $profile_link ?>" class="nav-avatar-button" title="Hello, <?= htmlspecialchars($session_user) ?>">
                    <?= $initial ?>
                </a>

            <?php else: ?>
                <a href="<?= $auth_link ?>" class="nav-login">Login</a>
                <a href="<?= $auth_link ?>?tab=register" class="nav-signup">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<nav class="mobile-bottom-nav">
    <?php
        $session_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
        $current_dir = dirname($_SERVER['SCRIPT_NAME']);
        $home_link = (strpos($current_dir, '/admin') !== false) ? '../pages/home.php' : 'home.php';
        $auth_link = (strpos($current_dir, '/admin') !== false) ? '../pages/auth.php' : 'auth.php';
        $is_admin = (($_SESSION['user_role'] ?? '') === 'admin') || ($session_user === 'admin');
        $profile_link = $is_admin 
            ? ((strpos($current_dir, '/admin') !== false) ? 'dashboard.php' : '../admin/dashboard.php')
            : ((strpos($current_dir, '/admin') !== false) ? '../pages/profile.php' : 'profile.php');
    ?>
    
    <a href="<?= $home_link ?>" class="mobile-nav-item" title="Home">
        <span class="material-symbols-outlined">home</span>
    </a>
    
    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login" && !$is_admin): ?>
        <a href="javascript:void(0)" onclick="openSubmitCompetitionModal()" class="mobile-nav-item" title="Submit Lomba">
            <span class="material-symbols-outlined">add_circle</span>
        </a>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
        <a href="<?= $profile_link ?>" class="mobile-nav-item" title="Profile">
            <span class="material-symbols-outlined">person</span>
        </a>
    <?php else: ?>
        <a href="<?= $auth_link ?>" class="mobile-nav-item" title="Login">
            <span class="material-symbols-outlined">login</span>
        </a>
    <?php endif; ?>
</nav>

<?php
// Include the modal popup layout for standard users
if (isset($_SESSION['status']) && $_SESSION['status'] === "login") {
    $session_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
    $is_admin = (($_SESSION['user_role'] ?? '') === 'admin') || ($session_user === 'admin');
    if (!$is_admin) {
        include_once __DIR__ . '/submit-competition-modal.php';
    }
}
?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const currentFilename = window.location.pathname.split('/').pop() || 'home.php';
    const navItems = document.querySelectorAll(".mobile-bottom-nav .mobile-nav-item");
    navItems.forEach(item => {
        const href = item.getAttribute("href");
        if (href) {
            const cleanHref = href.split('/').pop();
            if (cleanHref === currentFilename) {
                item.classList.add("active");
            }
        }
    });
});
</script>
