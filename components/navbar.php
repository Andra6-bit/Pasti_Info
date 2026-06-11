<?php
$current_script = basename($_SERVER['SCRIPT_NAME']);
$is_admin_dir = (strpos($current_dir ?? dirname($_SERVER['SCRIPT_NAME']), '/admin') !== false);
$hide_top_nav_on_mobile = ($current_script === 'debate-arena.php' || $current_script === 'profile.php' || $is_admin_dir);

$profile_picture = 'default_user.png';
if (isset($_SESSION['status']) && $_SESSION['status'] === "login") {
    if (!isset($koneksi)) {
        include_once __DIR__ . '/../config/database.php';
    }
    if (isset($koneksi)) {
        $user_id = $_SESSION['user_id'] ?? 0;
        if ($user_id > 0) {
            $pic_stmt = mysqli_prepare($koneksi, "SELECT profile_picture FROM users WHERE id = ?");
            if ($pic_stmt) {
                mysqli_stmt_bind_param($pic_stmt, "i", $user_id);
                mysqli_stmt_execute($pic_stmt);
                $pic_result = mysqli_stmt_get_result($pic_stmt);
                if ($pic_row = mysqli_fetch_assoc($pic_result)) {
                    $profile_picture = $pic_row['profile_picture'] ?? 'default_user.png';
                }
                mysqli_stmt_close($pic_stmt);
            }
        }
    }
}
?>
<header class="site-navbar <?= $hide_top_nav_on_mobile ? 'hide-on-mobile' : '' ?>">
    <nav class="navbar">
        <div class="logo">
            <img src="../assets/images/logo_putih.svg?v=<?= filemtime(__DIR__ . '/../assets/images/logo_putih.svg') ?>" alt="logo" class="logo-img">
        </div>

        <?php 
            $current_script = basename($_SERVER['SCRIPT_NAME']);
            if ($current_script !== 'debate-arena.php') {
                include 'floating-search.php';
            }
        ?>

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
                <!-- 1-line reason: Rename the button to Debate AI and replace the robot icon with debate swords. -->
                <a href="<?= (strpos($current_dir, '/admin') !== false) ? '../pages/debate-arena.php' : 'debate-arena.php' ?>" class="nav-chat-ai">
                    <i class="ti ti-swords" style="font-size:16px;vertical-align:middle;margin-right:4px;"></i> Debate AI
                </a>
                <?php if (!$is_admin): ?>
                    <a href="javascript:void(0)" onclick="openSubmitCompetitionModal()" class="nav-beranda">Submit Lomba</a>
                <?php endif; ?>

                <a href="<?= $profile_link ?>" class="nav-avatar-button" title="Hello, <?= htmlspecialchars($session_user) ?>" style="padding:0; overflow:hidden;">
                    <?php if (!empty($profile_picture) && $profile_picture !== 'default_user.png'): ?>
                        <img src="../assets/images/<?= htmlspecialchars($profile_picture) ?>" alt="Avatar" class="nav-avatar-img" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                    <?php else: ?>
                        <?= $initial ?>
                    <?php endif; ?>
                </a>

            <?php else: ?>
                <a href="<?= $auth_link ?>" class="nav-login">Login</a>
                <a href="<?= $auth_link ?>?tab=register" class="nav-signup">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
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
    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
        <!-- 1-line reason: Rename mobile button title to Debate AI and replace robot image with the swords icon. -->
        <a href="<?= (strpos($current_dir, '/admin') !== false) ? '../pages/debate-arena.php' : 'debate-arena.php' ?>" class="mobile-nav-item mobile-chat-ai" title="Debate AI">
            <i class="ti ti-swords" style="font-size:22px;"></i>
        </a>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login" && !$is_admin): ?>
        <a href="javascript:void(0)" onclick="openSubmitCompetitionModal()" class="mobile-nav-item" title="Submit Lomba">
            <span class="material-symbols-outlined">add_circle</span>
        </a>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
        <a href="<?= $profile_link ?>" class="mobile-nav-item" title="Profile" style="padding:0; overflow:hidden;">
            <?php if (!empty($profile_picture) && $profile_picture !== 'default_user.png'): ?>
                <img src="../assets/images/<?= htmlspecialchars($profile_picture) ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
            <?php else: ?>
                <span class="material-symbols-outlined">person</span>
            <?php endif; ?>
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
