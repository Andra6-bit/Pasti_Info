<?php
$fs_current_dir = dirname($_SERVER['SCRIPT_NAME']);
$search_action = (strpos($fs_current_dir, '/admin') !== false) ? '../pages/home.php' : 'home.php';
?>
<div class="navbar-search-wrapper" id="navbarSearchWrapper">
    <form method="GET" action="<?= $search_action ?>">
        <div class="navbar-search" id="navbarSearch">
            <div class="search-box">
                <span class="search-box-icon">search</span>
                <input type="text" name="search" placeholder="Search competitions..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="category-dropdown">
                <button type="button" class="btn-category-trigger" onclick="toggleCategoryPopup('categoryPopupFloating')">
                    Category ▾
                </button>
                <div id="categoryPopupFloating" class="category-popup-content">
                    <?php
                    $allowed_categories = [];
                    if (isset($koneksi)) {
                        $cat_query = mysqli_query($koneksi, "SELECT name FROM categories ORDER BY name ASC");
                        if ($cat_query) {
                            while ($cat_row = mysqli_fetch_assoc($cat_query)) {
                                $allowed_categories[] = $cat_row['name'];
                            }
                        }
                    }
                    if (empty($allowed_categories)) {
                        $allowed_categories = ['Design', 'Programming', 'Hacking'];
                    }
                    foreach ($allowed_categories as $cat): ?>
                        <label class="category-item">
                            <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat) ?>"
                                <?= (isset($_GET['categories']) && in_array($cat, $_GET['categories'])) ? 'checked' : '' ?> 
                                onchange="updateSelectedBadges()"> <span><?= htmlspecialchars($cat) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn-search-submit">Search</button>
        </div>
    </form>

    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
        <?php
            // Get user info
            $fs_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
            $is_fs_admin = (($_SESSION['user_role'] ?? '') === 'admin') || ($fs_user === 'admin');
            $fs_profile_link = $is_fs_admin 
                ? ((strpos($fs_current_dir, '/admin') !== false) ? 'dashboard.php' : '../admin/dashboard.php')
                : ((strpos($fs_current_dir, '/admin') !== false) ? '../pages/profile.php' : 'profile.php');
            $fs_initial = strtoupper(substr($fs_user, 0, 1));
        ?>
        <a href="<?= $fs_profile_link ?>" class="floating-profile-btn" title="Open Profile of <?= htmlspecialchars($fs_user) ?>">
            <?= $fs_initial ?>
        </a>
    <?php endif; ?>
</div>
