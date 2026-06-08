<?php
// Retrieve messages from manage-competitions controller
$msg      = $_GET['msg'] ?? '';
$msg_type = $_GET['msg_type'] ?? '';

// Fetch competition data
$all   = mysqli_query($koneksi, "SELECT * FROM competitions ORDER BY id DESC");
$total = mysqli_num_rows($all);

// Fetch categories for the form
$all_cats_res = mysqli_query($koneksi, "SELECT id, name FROM categories ORDER BY name ASC");
$all_cats = [];
while ($cat_row = mysqli_fetch_assoc($all_cats_res)) {
    $all_cats[] = $cat_row;
}
?>

<div class="tab-panel <?= $active_tab === 'kelola' ? 'active' : '' ?>" id="tab-kelola">

    <div class="section-head" style="border-bottom: none;">
        <div class="section-head-left">
            <div class="section-head-icon" style="background: #EAF3FC; color: #185FA5;">
                <i class="ti ti-trophy" style="font-size:18px"></i>
            </div>
            <div>
                <span class="section-title">Manage Competitions</span>
                <div class="section-sub">Add, edit, and delete registered competitions</div>
            </div>
        </div>
    </div>

    <?php if ($msg && $active_tab === 'kelola'): ?>
    <div class="alert alert-<?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="kelola-toolbar" style="border-top: 1px solid var(--color-border-tertiary);">
        <span><?= $total ?> competitions registered</span>
        <button class="btn btn-primary btn-sm" onclick="openAdd()">
            <i class="ti ti-plus" style="font-size:13px"></i> Add Competition
        </button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 130px;">ID</th>
                    <th>Title</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $c = 0;
            mysqli_data_seek($all, 0);
            while ($row = mysqli_fetch_assoc($all)): $c++;
            ?>
            <tr>
                <td style="font-family: monospace; font-weight:700; font-size:13px; color:var(--color-text-secondary); letter-spacing:0.5px;">
                    <?= htmlspecialchars($row['uid'] ?? $row['id']) ?>
                </td>
                <td style="font-weight:600;">
                    <?= htmlspecialchars($row['title']) ?>
                </td>
                <td>
                    <div class="actions">
                        <button class="btn btn-secondary btn-sm"
                            onclick='openEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)'>
                            <i class="ti ti-edit" style="font-size:12px"></i> Edit
                        </button>
                        <!-- UI-GAP fix: Use native modal instead of browser confirm() for delete action -->
                        <button class="btn btn-danger btn-sm"
                            onclick="openDeleteModal('../controllers/manage-competitions.php?delete=<?= $row['id'] ?>')">
                            <i class="ti ti-trash" style="font-size:12px"></i> Delete
                        </button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($c === 0): ?>
            <tr>
                <td colspan="3" class="empty-state">
                    <i class="ti ti-trophy-off" style="font-size:40px;opacity:0.4;display:block;margin-bottom:10px;"></i>
                    No competitions yet. Click <strong>"+ Add Competition"</strong> to start.
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: ADD / EDIT COMPETITION
     ══════════════════════════════════════════════════ -->
<div class="overlay" id="overlay" onclick="closeModal(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="closeOverlay()"><i class="ti ti-x" style="font-size:13px"></i></button>
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="ti ti-trophy" style="font-size:20px"></i>
            </div>
            <div>
                <h3 id="modal-title">Add Competition</h3>
                <p class="modal-subtitle" id="modal-subtitle">Fill in all required fields</p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data" action="../controllers/manage-competitions.php" id="competition-form" novalidate>
            <input type="hidden" name="action"    id="f-action"  value="add">
            <input type="hidden" name="id"        id="f-id"      value="">
            <input type="hidden" name="old_image" id="f-old-img" value="default.jpg">

            <!-- TITLE -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-tournament"></i> Competition Title <span class="fg-req">*</span>
                </label>
                <input type="text" name="title" id="f-title"
                       placeholder="Full competition name..." required maxlength="255"
                       class="fg-input">
            </div>

            <!-- FORMAT + TARGET AUDIENCE -->
            <div class="fg-row">
                <div class="fg">
                    <label class="fg-label">
                        <i class="ti ti-map-pin"></i> Format <span class="fg-req">*</span>
                    </label>
                    <div class="fg-radio-group" id="f-pelaksanaan-group">
                        <label class="fg-radio active" data-val="Online">
                            <input type="radio" name="pelaksanaan" value="Online" checked>
                            <i class="ti ti-wifi"></i> Online
                        </label>
                        <label class="fg-radio" data-val="Offline">
                            <input type="radio" name="pelaksanaan" value="Offline">
                            <i class="ti ti-building"></i> Offline
                        </label>
                        <label class="fg-radio" data-val="Hybrid">
                            <input type="radio" name="pelaksanaan" value="Hybrid">
                            <i class="ti ti-circle-half-2"></i> Hybrid
                        </label>
                    </div>
                </div>

                <div class="fg">
                    <label class="fg-label">
                        <i class="ti ti-users"></i> Target Audience <span class="fg-req">*</span>
                    </label>
                    <select name="target_peserta" id="f-target-peserta" class="fg-select" required>
                        <option value="SD">Elementary School</option>
                        <option value="SMP">Middle School</option>
                        <option value="SMA">High School</option>
                        <option value="Mahasiswa">College Student</option>
                        <option value="Umum" selected>General</option>
                    </select>
                </div>
            </div>

            <!-- DATES -->
            <div class="fg-row">
                <div class="fg">
                    <label class="fg-label">
                        <i class="ti ti-calendar-event"></i> Start Date <span class="fg-req">*</span>
                    </label>
                    <input type="date" name="start_date" id="f-start-date" class="fg-input" required>
                </div>
                <div class="fg">
                    <label class="fg-label">
                        <i class="ti ti-calendar-check"></i> End Date <span class="fg-req">*</span>
                    </label>
                    <input type="date" name="end_date" id="f-end-date" class="fg-input" required>
                </div>
            </div>

            <!-- REGISTRATION FEE -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-currency-dollar"></i> Registration Fee <span class="fg-req">*</span>
                </label>
                <div class="fg-biaya-wrap">
                    <span class="fg-biaya-prefix">Rp</span>
                    <input type="number" name="biaya" id="f-biaya"
                           min="0" placeholder="0" class="fg-input fg-biaya-input"
                           oninput="updateBiayaLabel(this)" required>
                    <span class="fg-biaya-badge" id="biaya-badge">Free</span>
                </div>
                <div class="fg-hint">Enter 0 if the competition is free</div>
            </div>

            <!-- CATEGORY CHIPS -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-tag"></i> Category <span class="fg-req">*</span>
                    <span class="fg-hint-inline">Select one or more</span>
                </label>
                <div class="category-grid" id="category-grid">
                    <?php foreach ($all_cats as $cat): ?>
                    <label class="cat-chip" data-id="<?= $cat['id'] ?>">
                        <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" class="cat-cb">
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="add-cat-row">
                    <input type="text" id="new-cat-input" placeholder="Add new category..."
                           maxlength="40" class="fg-input" style="flex:1;">
                    <button type="button" class="btn btn-secondary btn-sm" id="add-cat-btn" onclick="addNewCategory()">
                        <i class="ti ti-plus"></i> Add
                    </button>
                </div>
                <div class="add-cat-msg" id="add-cat-msg" style="display:none;"></div>
            </div>

            <!-- REGISTRATION LINK -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-link"></i> Registration Link <span class="fg-req">*</span>
                </label>
                <input type="url" name="link_pendaftaran" id="f-link-pendaftaran"
                       placeholder="https://example.com/register" class="fg-input" required>
            </div>

            <!-- UPLOAD POSTER -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-photo"></i> Poster / Competition Image
                    <span class="fg-req" id="img-req-label">*</span>
                    <span class="fg-hint-inline">JPG, PNG, WebP — max 3MB</span>
                </label>
                <div class="file-drop-zone" id="file-drop-zone" onclick="document.getElementById('f-image').click()">
                    <input type="file" name="image" id="f-image" accept="image/*"
                           onchange="handleImageSelect(this)" style="display:none;">
                    <div id="drop-placeholder">
                        <i class="ti ti-cloud-upload" style="font-size:32px; color:#93c5fd; margin-bottom:8px; display:block;"></i>
                        <p class="drop-text">Click or drag image here</p>
                        <p class="drop-subtext">JPG, PNG, WebP, GIF — max 3MB</p>
                    </div>
                    <div id="drop-preview" style="display:none;">
                        <img id="previewImg" src="" alt="Preview" class="drop-preview-img">
                        <div class="drop-preview-info">
                            <span id="preview-filename" class="drop-filename"></span>
                            <button type="button" class="drop-remove-btn" onclick="removeImage(event)">
                                <i class="ti ti-x"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DESCRIPTION -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-align-left"></i> Description <span class="fg-req">*</span>
                </label>
                <textarea name="description" id="f-desc"
                          placeholder="Explain this competition: theme, requirements, prizes, etc..."
                          class="fg-textarea" required rows="5"></textarea>
                <div class="fg-hint">
                    <span id="desc-count">0</span> characters
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="f-submit">
                    <i class="ti ti-plus" style="font-size:14px"></i> Add Competition
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeOverlay()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
var ALL_CATS = <?= json_encode($all_cats, JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- UI-GAP fix: Native delete confirmation modal (replaces browser confirm()) -->
<div id="deleteConfirmModal" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.55); align-items:center; justify-content:center;">
    <div style="
        background:#fff; border-radius:14px; padding:28px 28px 24px;
        width:90%; max-width:400px; box-shadow:0 8px 40px rgba(0,0,0,0.22);
        font-family:inherit; animation:delModalIn 0.18s ease;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
            <div style="width:40px;height:40px;border-radius:50%;background:#FEE2E2;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="ti ti-trash" style="font-size:20px;color:#DC2626;"></i>
            </div>
            <div>
                <div style="font-size:15px;font-weight:700;color:#1e293b;">Hapus Kompetisi</div>
                <div style="font-size:13px;color:#64748b;margin-top:2px;">Tindakan ini tidak dapat dibatalkan.</div>
            </div>
        </div>
        <p style="font-size:13.5px;color:#374151;margin:0 0 22px;">Apakah kamu yakin ingin menghapus kompetisi ini?</p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button id="delModalCancelBtn" onclick="closeDeleteModal()"
                style="padding:8px 18px;border-radius:8px;border:1.5px solid #cbd5e1;
                    background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">
                Batal
            </button>
            <button id="delModalConfirmBtn" onclick="confirmDelete()"
                style="padding:8px 18px;border-radius:8px;border:none;
                    background:#DC2626;color:#fff;font-size:13px;font-weight:600;
                    cursor:pointer;display:flex;align-items:center;gap:7px;">
                <span id="delModalSpinner" style="display:none;width:14px;height:14px;
                    border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;
                    border-radius:50%;animation:delSpin 0.7s linear infinite;"></span>
                <span id="delModalBtnText">Ya, Hapus</span>
            </button>
        </div>
    </div>
</div>
<style>
@keyframes delModalIn{from{opacity:0;transform:scale(0.93)}to{opacity:1;transform:scale(1)}}
@keyframes delSpin{to{transform:rotate(360deg)}}
</style>
<script>
var _deleteTargetUrl = '';
function openDeleteModal(url) {
    _deleteTargetUrl = url;
    var modal = document.getElementById('deleteConfirmModal');
    modal.style.display = 'flex';
    document.getElementById('delModalConfirmBtn').disabled = false;
    document.getElementById('delModalSpinner').style.display = 'none';
    document.getElementById('delModalBtnText').textContent = 'Ya, Hapus';
}
function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
}
function confirmDelete() {
    // UI-GAP fix: Show spinner while navigating to delete URL
    document.getElementById('delModalConfirmBtn').disabled = true;
    document.getElementById('delModalSpinner').style.display = 'inline-block';
    document.getElementById('delModalBtnText').textContent = 'Menghapus...';
    window.location.href = _deleteTargetUrl;
}
document.getElementById('deleteConfirmModal').addEventListener('click', function(e){
    if (e.target === this) closeDeleteModal();
});
</script>
