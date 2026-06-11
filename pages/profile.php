<?php
include "./session-check.php";
include "../config/database.php";

// Ensure user is logged in
if (!isset($_SESSION['status']) || $_SESSION['status'] !== "login") {
    header("Location: auth.php");
    exit();
}

$username = $_SESSION['username'] ?? $_SESSION['user_username'] ?? 'User';
$initial  = strtoupper(substr($username, 0, 1));

// Fetch user data securely
$email = 'Email not set';
$profile_picture = 'default_user.png';
$telegram_chat_id = '';
$institution = '';
$user_id = 0;

try {
    $sql = "SELECT id, username, email, profile_picture, telegram_chat_id, institution FROM users WHERE username = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            $email = $user_data['email'] ?? 'Email not set';
            $profile_picture = $user_data['profile_picture'] ?? 'default_user.png';
            $telegram_chat_id = $user_data['telegram_chat_id'] ?? '';
            $institution = $user_data['institution'] ?? '';
            $user_id = (int)$user_data['id'];
        }
    }
} catch (Exception $e) {
    // Gracefully handle database/column exceptions
}

$saved_competitions_data = [];
$saved_competitions = [];
if ($user_id > 0) {
    $savedSql = "SELECT c.* FROM saved_competitions sc JOIN competitions c ON sc.competition_id = c.id WHERE sc.user_id = ? ORDER BY sc.saved_at DESC";
    $savedStmt = mysqli_prepare($koneksi, $savedSql);
    if ($savedStmt) {
        mysqli_stmt_bind_param($savedStmt, "i", $user_id);
        mysqli_stmt_execute($savedStmt);
        $savedResult = mysqli_stmt_get_result($savedStmt);
        while ($savedRow = mysqli_fetch_assoc($savedResult)) {
            $saved_competitions_data[] = $savedRow;
            $saved_competitions[] = (int)$savedRow['id'];
        }
    }
}

// Fetch all available categories for subscription settings
$all_cats = [];
$catStmt = mysqli_prepare($koneksi, "SELECT id, name FROM categories ORDER BY name ASC");
if ($catStmt) {
    mysqli_stmt_execute($catStmt);
    $catRes = mysqli_stmt_get_result($catStmt);
    while ($catRow = mysqli_fetch_assoc($catRes)) {
        $all_cats[] = $catRow;
    }
    mysqli_stmt_close($catStmt);
}

// Fetch current user subscriptions
$subscribed_cats = [];
if ($user_id > 0) {
    $subStmt = mysqli_prepare($koneksi, "SELECT category_id FROM user_category_subscriptions WHERE user_id = ?");
    if ($subStmt) {
        mysqli_stmt_bind_param($subStmt, "i", $user_id);
        mysqli_stmt_execute($subStmt);
        $subRes = mysqli_stmt_get_result($subStmt);
        while ($subRow = mysqli_fetch_assoc($subRes)) {
            $subscribed_cats[] = (int)$subRow['category_id'];
        }
        mysqli_stmt_close($subStmt);
    }
}


$active_tab = $_GET['tab'] ?? 'profile-info';
if (!in_array($active_tab, ['profile-info', 'saved-competitions', 'settings', 'my-submissions'])) {
    $active_tab = 'profile-info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kelola profil, lomba tersimpan, dan pengaturan akun kamu di Pasti Info.">
    <title>Profil Saya — Pasti Info</title>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../assets/css/global.css') ?>">
    <link rel="stylesheet" href="../assets/css/navbar.css?v=<?= filemtime(__DIR__ . '/../assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../assets/css/home.css?v=<?= filemtime(__DIR__ . '/../assets/css/home.css') ?>">
    <link rel="stylesheet" href="../assets/css/floating-search.css?v=<?= filemtime(__DIR__ . '/../assets/css/floating-search.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="../assets/css/profile.css?v=<?= filemtime(__DIR__ . '/../assets/css/profile.css') ?>">
    <script src="../assets/js/profile.js?v=<?= filemtime(__DIR__ . '/../assets/js/profile.js') ?>" defer></script>
</head>
<body>

<?php include '../components/navbar.php'; ?>

<div class="profile-container">
    <div class="layout">
        
        <div class="sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <?php if (!empty($profile_picture) && $profile_picture !== 'default_user.png'): ?>
                        <img src="../assets/images/<?= htmlspecialchars($profile_picture) ?>" alt="Profile Photo">
                    <?php else: ?>
                        <?= htmlspecialchars($initial) ?>
                    <?php endif; ?>
                </div>
                <div class="profile-name"><?= htmlspecialchars($username) ?></div>
                <div class="profile-email"><?= htmlspecialchars($email) ?></div>
            </div>

            <div class="sidenav">
                <div class="sidenav-item <?= $active_tab === 'profile-info' ? 'active' : '' ?>" onclick="switchTab('profile-info', this)">
                    <i class="ti ti-user" style="font-size:18px"></i> Profile Info
                </div>
                <div class="sidenav-item <?= $active_tab === 'saved-competitions' ? 'active' : '' ?>" onclick="switchTab('saved-competitions', this)">
                    <i class="ti ti-bookmark" style="font-size:18px"></i> Saved Competitions
                </div>
                <div class="sidenav-item <?= $active_tab === 'my-submissions' ? 'active' : '' ?>" onclick="switchTab('my-submissions', this)">
                    <i class="ti ti-trophy" style="font-size:18px"></i> My Submissions
                </div>
                <div class="sidenav-item <?= $active_tab === 'settings' ? 'active' : '' ?>" onclick="switchTab('settings', this)">
                    <i class="ti ti-settings" style="font-size:18px"></i> Settings
                </div>
                <a href="../controllers/logout.php" class="sidenav-item danger">
                    <i class="ti ti-logout" style="font-size:18px"></i> Logout
                </a>
            </div>
        </div>

        <div class="main-content">

            <!-- TAB: Profile Info -->
            <div class="tab-panel <?= $active_tab === 'profile-info' ? 'active' : '' ?>" id="tab-profile-info">
                <div id="profile-view-mode">
                    <div class="section-head">
                        <div class="section-head-left">
                            <div class="section-head-icon">
                                <i class="ti ti-user-circle" style="font-size:18px"></i>
                            </div>
                            <div>
                                <span class="section-title">Profile Information</span>
                                <div class="section-sub">Account details and contact information</div>
                            </div>
                        </div>
                        <span class="edit-btn" onclick="toggleEditProfile(true)">
                            <i class="ti ti-edit" style="font-size:14px"></i> Edit
                        </span>
                    </div>

                    <?php if (isset($_GET['success']) && $active_tab === 'profile-info'): ?>
                        <div class="alert alert-success" style="margin: 16px 24px;">
                            <?= htmlspecialchars($_GET['success']) ?>
                        </div>
                    <?php elseif (isset($_GET['error']) && $active_tab === 'profile-info'): ?>
                        <div class="alert alert-error" style="margin: 16px 24px;">
                            <?= htmlspecialchars($_GET['error']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="field-rows">
                        <div class="field-row">
                            <div class="field-icon-wrap">
                                <i class="ti ti-at"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Username</div>
                                <div class="field-value"><?= htmlspecialchars($username) ?></div>
                            </div>
                            <div class="field-status">
                                <span class="field-pill pill-blue">
                                    <i class="ti ti-user" style="font-size:11px"></i> Member
                                </span>
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-icon-wrap">
                                <i class="ti ti-mail"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Email</div>
                                <div class="field-value"><?= htmlspecialchars($email) ?></div>
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-icon-wrap">
                                <i class="ti ti-building"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Institution / School</div>
                                <?php if (!empty($institution)): ?>
                                    <div class="field-value"><?= htmlspecialchars($institution) ?></div>
                                <?php else: ?>
                                    <div class="field-empty">Not set</div>
                                <?php endif; ?>
                            </div>
                            <div class="field-status">
                                <span class="field-pill pill-gray">Optional</span>
                            </div>
                        </div>

                        <div class="field-row" style="border-bottom: none;">
                            <div class="field-icon-wrap">
                                <i class="ti ti-brand-telegram"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Telegram Chat ID</div>
                                <div id="telegram-chat-id-val" data-chat-id="<?= htmlspecialchars($telegram_chat_id) ?>" class="<?= !empty($telegram_chat_id) ? 'field-value' : 'field-empty' ?>">
                                    <?= !empty($telegram_chat_id) ? htmlspecialchars($telegram_chat_id) : 'Not set' ?>
                                </div>
                            </div>
                            <div class="field-status">
                                <span class="field-pill pill-gray">Optional</span>
                            </div>
                        </div>
                    </div>

                    <div class="diri-footer">
                        <span class="diri-footer-note">
                            <i class="ti ti-lock" style="font-size:14px"></i>
                            Data stored securely
                        </span>
                    </div>
                </div>

                <form id="profile-edit-mode" method="POST" action="../controllers/update-profile.php" enctype="multipart/form-data" style="display: none;">
                    <div class="section-head">
                        <div class="section-head-left">
                            <div class="section-head-icon">
                                <i class="ti ti-edit" style="font-size:18px"></i>
                            </div>
                            <div>
                                <span class="section-title">Edit Profile Info</span>
                                <div class="section-sub">Update your account information</div>
                            </div>
                        </div>
                    </div>

                    <div class="field-rows">
                        <div class="field-row">
                            <div class="field-icon-wrap">
                                <i class="ti ti-at"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Username</div>
                                <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required class="field-input">
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-icon-wrap">
                                <i class="ti ti-mail"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Email</div>
                                <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required class="field-input">
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-icon-wrap">
                                <i class="ti ti-building"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Institution / School</div>
                                <input type="text" name="instansi" value="<?= htmlspecialchars($institution) ?>" placeholder="e.g., Gadjah Mada University" class="field-input">
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-icon-wrap">
                                <i class="ti ti-brand-telegram"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Telegram Chat ID</div>
                                <input type="text" name="telegram_chat_id" value="<?= htmlspecialchars($telegram_chat_id) ?>" placeholder="e.g., 123456789" class="field-input">
                            </div>
                            <div class="field-status">
                                <span class="field-pill pill-gray">Optional</span>
                            </div>
                        </div>

                        <div class="field-row" style="border-bottom: none;">
                            <div class="field-icon-wrap">
                                <i class="ti ti-photo"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Profile Picture</div>
                                <input type="file" name="foto_profile" accept="image/*" class="field-input-file">
                                <?php if (!empty($profile_picture) && $profile_picture !== 'default_user.png'): ?>
                                    <div style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
                                        <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #e53e3e; cursor: pointer; font-weight: 600;">
                                            <input type="checkbox" name="hapus_foto" value="1" style="cursor: pointer;">
                                            Delete current profile photo
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="field-status">
                                <span class="field-pill pill-gray">Optional</span>
                            </div>
                        </div>
                    </div>

                    <div class="diri-footer">
                        <span class="diri-footer-note">
                            <i class="ti ti-lock" style="font-size:14px"></i>
                            Data stored securely
                        </span>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEditProfile(false)">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="ti ti-device-floppy" style="font-size:13px"></i> Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TAB: Saved Competitions -->
            <div class="tab-panel <?= $active_tab === 'saved-competitions' ? 'active' : '' ?>" id="tab-saved-competitions">
                <div class="section-head">
                    <div class="section-head-left">
                        <div class="section-head-icon">
                            <i class="ti ti-bookmark" style="font-size:18px"></i>
                        </div>
                        <div>
                            <span class="section-title">Saved Competitions</span>
                            <div class="section-sub">List of competitions you have saved</div>
                        </div>
                    </div>
                </div>
                <?php if (count($saved_competitions_data) === 0): ?>
                    <div style="padding: 48px 20px; text-align: center;">
                        <i class="ti ti-bookmark-off empty-state-icon"></i>
                        <p class="empty-state-text">No competitions saved yet</p>
                        <p class="empty-state-sub">Find interesting competitions and click the bookmark icon to save them</p>
                    </div>
                <?php else: ?>
                    <div class="profile-card-grid">
                        <?php
                        foreach ($saved_competitions_data as $row) {
                            include '../components/competition-card.php';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                <div class="diri-footer">
                    <span class="diri-footer-note">
                        <i class="ti ti-info-circle" style="font-size:14px"></i>
                        Click on a competition to view more details
                    </span>
                </div>
            </div>

            <!-- TAB: My Submissions -->
            <?php include '../components/my-submissions.php'; ?>

            <!-- TAB: Settings -->
            <div class="tab-panel <?= $active_tab === 'settings' ? 'active' : '' ?>" id="tab-settings">
                <div class="section-head">
                    <div class="section-head-left">
                        <div class="section-head-icon">
                            <i class="ti ti-settings" style="font-size:18px"></i>
                        </div>
                        <div>
                            <span class="section-title">Account Settings</span>
                            <div class="section-sub">Manage security and notifications</div>
                        </div>
                    </div>
                </div>

                <?php if (isset($_GET['success']) && $active_tab === 'settings'): ?>
                    <div class="alert alert-success" style="margin: 16px 24px;">
                        <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                <?php elseif (isset($_GET['error']) && $active_tab === 'settings'): ?>
                    <div class="alert alert-error" style="margin: 16px 24px;">
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>

                <!-- Category Subscriptions Form -->
                <?php include '../components/category-subscriptions-form.php'; ?>

                <!-- Telegram Notifications -->
                <div class="field-rows">
                    <div class="field-row" style="border-bottom: none;">
                        <div class="field-icon-wrap" style="background: #E6F1FB; color: #185FA5;">
                            <i class="ti ti-brand-telegram"></i>
                        </div>
                        <div class="field-body">
                            <div class="field-label">Telegram Notifications</div>
                            <div id="telegram-status-value" class="field-value" style="color: #94a3b8; font-weight: 500; font-size: 13px;">Cannot be activated yet</div>
                            <div id="telegram-status-note" class="field-sub-note">
                                Set <strong>Telegram Chat ID</strong> in the 
                                <span class="notif-link" onclick="switchTab('profile-info', document.querySelector('.sidenav-item'))">
                                    Profile Info
                                </span> tab first
                            </div>
                        </div>
                        <div class="field-status">
                            <span id="telegram-toggle-button" class="notif-toggle notif-toggle-disabled" title="Set Telegram Chat ID first">
                                <span class="notif-toggle-knob"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Change Password - Form & View Unified -->
                <!-- 1-line reason: Rely on change-password-form.php as the single source of truth for the change password section. -->
                <?php include '../components/change-password-form.php'; ?>


                <div class="diri-footer">
                    <span class="diri-footer-note">
                        <i class="ti ti-bell" style="font-size:14px"></i>
                        Notifications sent via Telegram bot
                    </span>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="../assets/js/bookmark.js?v=<?= filemtime(__DIR__ . '/../assets/js/bookmark.js') ?>"></script>
<script>
// Switch tabs via query parameter compatibility
const tabMap = {
    'data-diri': 'profile-info',
    'lomba-tersimpan': 'saved-competitions',
    'pengaturan': 'settings',
    'my-submissions': 'my-submissions',
    'riwayat-lomba': 'my-submissions'
};
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const rawTab = urlParams.get('tab');
    if (rawTab && tabMap[rawTab]) {
        const mapped = tabMap[rawTab];
        const tabEl = Array.from(document.querySelectorAll('.sidenav-item')).find(item => item.getAttribute('onclick').includes(rawTab) || item.getAttribute('onclick').includes(mapped));
        switchTab(mapped, tabEl);
    }
});
</script>
</body>
</html>