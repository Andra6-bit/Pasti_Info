<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($_SESSION['user_role'] ?? '') !== 'admin') {
    // 1-line reason: Replace username-based admin check with session role verification for improved security.
    header("Location: ../pages/auth.php?error=" . urlencode("Access Denied! Admin only."));
    exit();
}
include "../config/database.php";

// 1-line reason: Use user_username session key exclusively as the standard username key for session consistency.
$username = $_SESSION['user_username'] ?? 'admin';
$initial  = strtoupper(substr($username, 0, 1));
$email = 'Email not set';
$profile_picture = 'default_user.png';
$role = 'admin';
$telegram_chat_id = '';
$user_id = 0;

try {
    $sql  = "SELECT id, username, email, profile_picture, role, telegram_chat_id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $user_data = mysqli_fetch_assoc($result);
            $email = $user_data['email'] ?? 'Email not set';
            $profile_picture = $user_data['profile_picture'] ?? 'default_user.png';
            $role = $user_data['role'] ?? 'admin';
            $telegram_chat_id = $user_data['telegram_chat_id'] ?? '';
            $user_id = (int)$user_data['id'];
        }
    }
} catch (Exception $e) {}
$active_tab = $_GET['tab'] ?? 'data-diri';
if (!in_array($active_tab, ['data-diri', 'kelola', 'pengaturan', 'review-lomba', 'riwayat-keuangan'])) {
    $active_tab = 'data-diri';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin Panel Pasti Info — Kelola kompetisi, review submissions, dan pengaturan sistem.">
    <title>Admin Panel — Pasti Info</title>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../assets/css/global.css') ?>">
    <link rel="stylesheet" href="../assets/css/navbar.css?v=<?= filemtime(__DIR__ . '/../assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../assets/css/home.css?v=<?= filemtime(__DIR__ . '/../assets/css/home.css') ?>">
    <link rel="stylesheet" href="../assets/css/floating-search.css?v=<?= filemtime(__DIR__ . '/../assets/css/floating-search.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../assets/css/admin.css') ?>">
    <script src="../assets/js/admin.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin.js') ?>" defer></script>
</head>
<body>

<?php include '../components/navbar.php'; ?>

<div class="profile-container">
    <div class="layout">

        <div class="sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <?php if (!empty($profile_picture) && $profile_picture !== 'default_user.png' && $profile_picture !== 'default-avatar.jpg'): ?>
                        <img src="../assets/images/<?= htmlspecialchars($profile_picture) ?>" alt="Profile Photo">
                    <?php else: ?>
                        <?= htmlspecialchars($initial) ?>
                    <?php endif; ?>
                </div>
                <div class="profile-name"><?= htmlspecialchars($username) ?></div>
                <div class="profile-email"><?= htmlspecialchars($email) ?></div>
                <span class="admin-badge">⭐ Administrator</span>
            </div>

            <div class="sidenav">
                <div class="sidenav-item <?= $active_tab === 'data-diri' ? 'active' : '' ?>" onclick="switchTab('data-diri', this)">
                    <i class="ti ti-user" style="font-size:18px"></i> Profile Info
                </div>
                <div class="sidenav-item <?= $active_tab === 'kelola' ? 'active' : '' ?>" onclick="switchTab('kelola', this)">
                    <i class="ti ti-trophy" style="font-size:18px"></i> Manage Competitions
                </div>
                <div class="sidenav-item <?= $active_tab === 'review-lomba' ? 'active' : '' ?>" onclick="switchTab('review-lomba', this)">
                    <i class="ti ti-checklist" style="font-size:18px"></i> Review Submissions
                </div>
                <div class="sidenav-item <?= $active_tab === 'riwayat-keuangan' ? 'active' : '' ?>" onclick="switchTab('riwayat-keuangan', this)">
                    <i class="ti ti-history" style="font-size:18px"></i> Payment History
                </div>
                <div class="sidenav-item <?= $active_tab === 'pengaturan' ? 'active' : '' ?>" onclick="switchTab('pengaturan', this)">
                    <i class="ti ti-settings" style="font-size:18px"></i> Settings
                </div>
                <a href="../controllers/logout.php" class="sidenav-item danger">
                    <i class="ti ti-logout" style="font-size:18px"></i> Logout
                </a>
            </div>
        </div>

        <div class="main-content">

            <div class="tab-panel <?= $active_tab === 'data-diri' ? 'active' : '' ?>" id="tab-data-diri">
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

                    <?php if (isset($_GET['success']) && $active_tab === 'data-diri'): ?>
                        <div class="alert alert-success" style="margin: 16px 24px;">
                            <?= htmlspecialchars($_GET['success']) ?>
                        </div>
                    <?php elseif (isset($_GET['error']) && $active_tab === 'data-diri'): ?>
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
                                <span class="field-pill pill-yellow">
                                    <i class="ti ti-star" style="font-size:11px"></i> Administrator
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
                            <div class="field-status">
                                <span class="field-pill pill-blue">
                                    <i class="ti ti-check" style="font-size:11px"></i> Verified
                                </span>
                            </div>
                        </div>

                        <div class="field-row" style="border-bottom: none;">
                            <div class="field-icon-wrap">
                                <i class="ti ti-shield-check"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Role</div>
                                <div class="field-value">Administrator</div>
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
                                <div class="section-sub">Update admin account information</div>
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
                                <i class="ti ti-shield-check"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">Role</div>
                                <input type="text" value="Administrator" disabled class="field-input field-input-disabled">
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
 
            <?php include 'manage-competitions.php'; ?>

            <?php include 'review-submissions.php'; ?>

            <?php include 'payment-history.php'; ?>

            <div class="tab-panel <?= $active_tab === 'pengaturan' ? 'active' : '' ?>" id="tab-pengaturan">
                <div class="section-head">
                    <div class="section-head-left">
                        <div class="section-head-icon">
                            <i class="ti ti-settings" style="font-size:18px"></i>
                        </div>
                        <div>
                            <span class="section-title">Account Settings</span>
                            <div class="section-sub">Manage admin account security</div>
                        </div>
                    </div>
                </div>

                <?php if (isset($_GET['success']) && $active_tab === 'pengaturan'): ?>
                    <div class="alert alert-success" style="margin: 16px 24px;">
                        <?= htmlspecialchars($_GET['success']) ?>
                    </div>
                <?php elseif (isset($_GET['error']) && $active_tab === 'pengaturan'): ?>
                    <div class="alert alert-error" style="margin: 16px 24px;">
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php endif; ?>

                <!-- Change Password - Form & View Unified -->
                <!-- 1-line reason: Rely on change-password-form.php as the single source of truth for the change password section. -->
                <?php include '../components/change-password-form.php'; ?>

                <!-- Submission Fee Config Form -->
                <?php
                // Fetch current submission fee setting
                $submission_fee = 20000;
                $fee_stmt = mysqli_prepare($koneksi, "SELECT value FROM settings WHERE `key` = 'submission_fee' LIMIT 1");
                if ($fee_stmt) {
                    mysqli_stmt_execute($fee_stmt);
                    $fee_res = mysqli_stmt_get_result($fee_stmt);
                    if ($fee_row = mysqli_fetch_assoc($fee_res)) {
                        $submission_fee = (int)$fee_row['value'];
                    }
                    mysqli_stmt_close($fee_stmt);
                }
                ?>
                <div class="field-rows" style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                    <form method="POST" action="../controllers/update-settings.php">
                        <div class="field-row" style="border-bottom: none;">
                            <div class="field-icon-wrap" style="background: #E8F5E9; color: #2E7D32;">
                                <i class="ti ti-coin"></i>
                            </div>
                            <div class="field-body">
                                <div class="field-label">User Submission Fee</div>
                                <div style="display: flex; gap: 10px; align-items: center; margin-top: 4px;">
                                    <span style="font-weight: 700; color: #64748b;">Rp</span>
                                    <input type="number" name="submission_fee" value="<?= $submission_fee ?>" min="0" required class="field-input" style="max-width: 150px; margin-top: 0;">
                                    <button type="submit" class="btn btn-primary btn-sm" style="font-size:12px; padding: 6px 14px;">Save Fee</button>
                                </div>
                                <div class="field-sub-note">Configure the amount charged to regular users for listing a competition.</div>
                            </div>
                        </div>
                    </form>
                </div>





            </div>

        </div>
    </div>
</div>

<script>
// Switch tabs via query parameter compatibility
const tabMap = {
    'data-diri': 'data-diri',
    'kelola': 'kelola',
    'review-lomba': 'review-lomba',
    'riwayat-keuangan': 'riwayat-keuangan',
    'pengaturan': 'pengaturan'
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
