<div class="navbar-search-wrapper" id="navbarSearchWrapper">
    <form method="GET" action="landing.php">
        <div class="navbar-search" id="navbarSearch">
            <div class="search-box">
                <span class="search-box-icon">search</span>
                <input type="text" name="search" placeholder="Cari kompetisi..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            <div class="category-dropdown">
                <button type="button" class="btn-category-trigger" onclick="toggleCategoryPopup('categoryPopupFloating')">
                    Kategori ▾
                </button>
                <div id="categoryPopupFloating" class="category-popup-content">
                    <?php
                    $allowed_categories = ['Design', 'Programming', 'Hacking'];
                    foreach ($allowed_categories as $cat): ?>
                        <label class="category-item">
                            <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($cat) ?>"
                                <?= (isset($_GET['categories']) && in_array($cat, $_GET['categories'])) ? 'checked' : '' ?> 
                                onchange="updateSelectedBadges()"> <span><?= htmlspecialchars($cat) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn-search-submit">Cari</button>
        </div>
    </form>

    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
        <?php
            // Ambil data user
            $fs_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
            $fs_profile_link = ($fs_user === 'admin') ? '../admin/admin.php' : 'profile.php';
            $fs_initial = strtoupper(substr($fs_user, 0, 1));
        ?>
        <a href="<?= $fs_profile_link ?>" class="floating-profile-btn" title="Buka Profil <?= htmlspecialchars($fs_user) ?>">
            <?= $fs_initial ?>
        </a>
    <?php endif; ?>
</div>
