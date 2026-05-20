<div class="navbar-search" id="navbarSearch" style="gap: 6px;">
    <form method="GET" action="landing.php" style="display: flex; align-items: center; gap: 8px;">
        <div class="search-box" style="margin: 0;">
            <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        </div>
        <div class="category-dropdown">
            <button type="button" class="btn-category-trigger btn-category-trigger--dark" onclick="toggleCategoryPopup('categoryPopupFloating')">
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
        <button type="submit" class="btn-search-submit btn-search-submit--dark" title="Cari">➜</button>
    </form>

    <?php if (isset($_SESSION['status']) && $_SESSION['status'] === "login"): ?>
        <?php
            // Ambil data user
            $fs_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
            $fs_profile_link = ($fs_user === 'admin') ? '../admin/index.php' : 'profile.php';
            $fs_initial = strtoupper(substr($fs_user, 0, 1));
        ?>
        <div style="width: 1px; height: 28px; background: rgba(255,255,255,0.25); margin: 0 4px;"></div>
        
        <a href="<?= $fs_profile_link ?>" class="floating-profile-btn" title="Buka Profil <?= htmlspecialchars($fs_user) ?>">
            <?= $fs_initial ?>
        </a>
    <?php endif; ?>
</div>