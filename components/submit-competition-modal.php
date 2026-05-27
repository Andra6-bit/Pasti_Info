<?php
// Ensure session is started and user is standard user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['status']) && $_SESSION['status'] === "login") {
    $session_user = $_SESSION['username'] ?? $_SESSION['user_username'] ?? '';
    $is_admin = (($_SESSION['user_role'] ?? '') === 'admin') || ($session_user === 'admin');
    if ($is_admin) {
        return; // Admins don't need this modal
    }
} else {
    return; // Guest users don't need this modal
}

// Dynamically check database connection
if (!isset($koneksi)) {
    $db_path = dirname(__DIR__) . '/config/database.php';
    if (file_exists($db_path)) {
        include_once $db_path;
    }
}

if (!isset($koneksi)) {
    error_log("[Submit Modal Error] Database connection variable not found.");
    return;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);

// Fetch user data to check Telegram Chat ID
$telegram_chat_id = '';
$stmt = mysqli_prepare($koneksi, "SELECT telegram_chat_id FROM users WHERE id = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $telegram_chat_id = $row['telegram_chat_id'];
    }
    mysqli_stmt_close($stmt);
}

// Fetch categories for selection
$all_cats = [];
$cat_res = mysqli_query($koneksi, "SELECT id, name FROM categories ORDER BY name ASC");
if ($cat_res) {
    while ($cat_row = mysqli_fetch_assoc($cat_res)) {
        $all_cats[] = $cat_row;
    }
}

// Get configured submission fee
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

// Dynamic Action URL
$current_dir = dirname($_SERVER['SCRIPT_NAME']);
$action_url = (strpos($current_dir, '/admin') !== false || strpos($current_dir, '/pages') !== false) 
    ? '../controllers/submit-competition.php' 
    : 'controllers/submit-competition.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<div class="submit-overlay" id="submit-competition-modal" onclick="closeSubmitCompetitionModal(event)">
    <div class="submit-modal-box" onclick="event.stopPropagation()">
        <button type="button" class="submit-modal-close" onclick="toggleSubmitCompetitionModal(false)">
            <i class="ti ti-x"></i>
        </button>
        
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px; border-bottom: 1px solid var(--color-border-tertiary); padding-bottom: 15px;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: #E6F1FB; color: #185FA5; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="ti ti-upload"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--navy);">Submit New Competition</h3>
                <p style="margin: 3px 0 0; font-size: 12px; color: var(--color-text-secondary);">Register your competition to be published on LombaID</p>
            </div>
        </div>

        <div style="background: #FFF3CD; border: 1px solid #FFEBAA; border-radius: 8px; padding: 10px 14px; color: #856404; font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class="ti ti-info-circle" style="font-size: 16px; flex-shrink: 0;"></i>
            <span>Submission Fee: <strong>Rp <?= number_format($submission_fee, 0, ',', '.') ?></strong> per competition. Payment via Xendit invoice is required after submission.</span>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error" style="background: #fce8e6; border: 1px solid #f5c6c6; border-radius: 8px; padding: 10px 14px; color: #c0392b; font-size: 13px; font-weight: 600; margin-bottom: 20px;">
                <i class="ti ti-alert-triangle" style="font-size: 16px; margin-right: 4px; vertical-align: middle;"></i>
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" action="<?= $action_url ?>" id="modal-submit-form">
            <input type="hidden" name="referrer" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            
            <!-- TELEGRAM CHAT ID -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-brand-telegram"></i> Telegram Chat ID <span class="fg-req">*</span>
                </label>
                <input type="text" name="telegram_chat_id" id="modal-f-telegram"
                       placeholder="e.g., 123456789 (Use Bot @userinfobot to get your ID)" 
                       value="<?= htmlspecialchars($telegram_chat_id) ?>" required class="fg-input">
                <div class="fg-hint">Required for automated status notifications (payment success, review results).</div>
            </div>

            <!-- TITLE -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-tournament"></i> Competition Title <span class="fg-req">*</span>
                </label>
                <input type="text" name="title" id="modal-f-title"
                       placeholder="Full competition name..." required maxlength="255"
                       class="fg-input">
            </div>

            <!-- FORMAT + TARGET AUDIENCE -->
            <div class="fg-row">
                <div class="fg">
                    <label class="fg-label">
                        <i class="ti ti-map-pin"></i> Format <span class="fg-req">*</span>
                    </label>
                    <div class="fg-radio-group" id="modal-f-pelaksanaan-group">
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
                    <select name="target_peserta" id="modal-f-target-peserta" class="fg-select" required>
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
                    <input type="date" name="start_date" id="modal-f-start-date" class="fg-input" required>
                </div>
                <div class="fg">
                    <label class="fg-label">
                        <i class="ti ti-calendar-check"></i> End Date <span class="fg-req">*</span>
                    </label>
                    <input type="date" name="end_date" id="modal-f-end-date" class="fg-input" required>
                </div>
            </div>

            <!-- REGISTRATION FEE -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-currency-dollar"></i> Registration Fee <span class="fg-req">*</span>
                </label>
                <div class="fg-biaya-wrap">
                    <span class="fg-biaya-prefix">Rp</span>
                    <input type="number" name="biaya" id="modal-f-biaya"
                           min="0" placeholder="0" class="fg-input fg-biaya-input"
                           oninput="updateModalBiayaLabel(this)" required>
                    <span class="fg-biaya-badge" id="modal-biaya-badge">Free</span>
                </div>
                <div class="fg-hint">Enter 0 if the competition registration is free</div>
            </div>

            <!-- CATEGORY CHIPS -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-tag"></i> Category <span class="fg-req">*</span>
                    <span class="fg-hint-inline">Select one or more</span>
                </label>
                <div class="category-grid" id="modal-category-grid">
                    <?php foreach ($all_cats as $cat): ?>
                    <label class="cat-chip" data-id="<?= $cat['id'] ?>">
                        <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" class="cat-cb">
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- REGISTRATION LINK -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-link"></i> Registration Link <span class="fg-req">*</span>
                </label>
                <input type="url" name="link_pendaftaran" id="modal-f-link-pendaftaran"
                       placeholder="https://example.com/register" class="fg-input" required>
            </div>

            <!-- UPLOAD POSTER -->
            <div class="fg">
                <label class="fg-label">
                    <i class="ti ti-photo"></i> Poster / Competition Image <span class="fg-req">*</span>
                    <span class="fg-hint-inline">JPG, PNG, WebP — max 3MB</span>
                </label>
                <div class="file-drop-zone" id="modal-file-drop-zone" onclick="document.getElementById('modal-f-image').click()">
                    <input type="file" name="image" id="modal-f-image" accept="image/*"
                           onchange="handleModalImageSelect(this)" style="display:none;" required>
                    <div id="modal-drop-placeholder">
                        <i class="ti ti-cloud-upload" style="font-size:32px; color:#93c5fd; margin-bottom:8px; display:block;"></i>
                        <p class="drop-text">Click or drag image here</p>
                        <p class="drop-subtext">JPG, PNG, WebP, GIF — max 3MB</p>
                    </div>
                    <div id="modal-drop-preview" style="display:none;">
                        <img id="modal-previewImg" src="" alt="Preview" class="drop-preview-img">
                        <div class="drop-preview-info">
                            <span id="modal-preview-filename" class="drop-filename"></span>
                            <button type="button" class="drop-remove-btn" onclick="removeModalImage(event)">
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
                <textarea name="description" id="modal-f-desc"
                          placeholder="Explain this competition: theme, requirements, prizes, etc..."
                          class="fg-textarea" required rows="5" oninput="updateModalCharCount(this)"></textarea>
                <div class="fg-hint">
                    <span id="modal-desc-count">0</span> characters
                </div>
            </div>

            <div class="form-actions" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary" onclick="toggleSubmitCompetitionModal(false)" style="padding: 10px 24px;">Cancel</button>
                <button type="submit" class="btn btn-primary" id="modal-f-submit" style="padding: 10px 24px;">
                    <i class="ti ti-credit-card" style="font-size:15px"></i> Submit & Pay
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal open / close logic
function toggleSubmitCompetitionModal(open) {
    const modal = document.getElementById('submit-competition-modal');
    if (!modal) return;
    if (open) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    } else {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
}

function closeSubmitCompetitionModal(event) {
    if (event.target === document.getElementById('submit-competition-modal')) {
        toggleSubmitCompetitionModal(false);
    }
}

// Expose trigger globally
window.openSubmitCompetitionModal = function() {
    toggleSubmitCompetitionModal(true);
};

// Check for query parameter open-submit=1 or error to auto-open modal
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open-submit') === '1' || urlParams.get('error')) {
        // Clean URL query parameters without reloading the page (preserves other parameters like tab)
        const url = new URL(window.location.href);
        url.searchParams.delete('open-submit');
        url.searchParams.delete('error');
        window.history.replaceState({ path: url.href }, '', url.href);
        
        // Short timeout to let browser finish rendering
        setTimeout(function() {
            toggleSubmitCompetitionModal(true);
        }, 150);
    }
});

// Handle overlay close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        toggleSubmitCompetitionModal(false);
    }
});

// Setup Form Interactivity inside Modal
document.addEventListener('DOMContentLoaded', function() {
    // Radio buttons behavior
    document.querySelectorAll('#modal-f-pelaksanaan-group .fg-radio').forEach(label => {
        label.addEventListener('click', function() {
            document.querySelectorAll('#modal-f-pelaksanaan-group .fg-radio').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            this.querySelector('input[type="radio"]').checked = true;
        });
    });

    // Category chips behavior
    document.querySelectorAll('#modal-category-grid .cat-cb').forEach(cb => {
        cb.addEventListener('change', function() {
            const chip = this.closest('.cat-chip');
            if (this.checked) {
                chip.classList.add('selected');
            } else {
                chip.classList.remove('selected');
            }
        });
        // Initial state
        const chip = cb.closest('.cat-chip');
        if (cb.checked) {
            chip.classList.add('selected');
        }
    });

    // Drag and drop for modal upload area
    const dropZone = document.getElementById('modal-file-drop-zone');
    if (dropZone) {
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        dropZone.addEventListener('dragleave', function() {
            this.classList.remove('drag-over');
        });
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length) {
                const input = document.getElementById('modal-f-image');
                input.files = files;
                handleModalImageSelect(input);
            }
        });
    }

    // Modal Form Validation
    const form = document.getElementById('modal-submit-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Image validation
            const imgInput = document.getElementById('modal-f-image');
            if (!imgInput || !imgInput.files || imgInput.files.length === 0) {
                e.preventDefault();
                alert('Competition poster image is required.');
                return false;
            }

            // Categories validation
            const categories = document.querySelectorAll('#modal-category-grid .cat-cb:checked');
            if (categories.length === 0) {
                e.preventDefault();
                alert('Please select at least one category.');
                return false;
            }

            // Date validation
            const startVal = document.getElementById('modal-f-start-date').value;
            const endVal = document.getElementById('modal-f-end-date').value;
            if (startVal && endVal && startVal > endVal) {
                e.preventDefault();
                alert('End date cannot be before start date.');
                return false;
            }
        });
    }
});

function updateModalBiayaLabel(input) {
    const val = parseInt(input.value) || 0;
    const badge = document.getElementById('modal-biaya-badge');
    if (!badge) return;
    if (val === 0) {
        badge.textContent = 'Free';
        badge.style.background = '#e6f4ea';
        badge.style.color = '#1e7e34';
    } else {
        badge.textContent = 'Paid';
        badge.style.background = '#e8f0fe';
        badge.style.color = '#185fa5';
    }
}

function updateModalCharCount(textarea) {
    const countEl = document.getElementById('modal-desc-count');
    if (countEl) {
        countEl.textContent = textarea.value.length;
    }
}

// File drop zone preview functions
function handleModalImageSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 3 * 1024 * 1024) {
            alert('File size exceeds 3MB limit.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('modal-drop-placeholder').style.display = 'none';
            document.getElementById('modal-drop-preview').style.display = 'flex';
            document.getElementById('modal-previewImg').src = e.target.result;
            document.getElementById('modal-preview-filename').textContent = file.name;
        };
        reader.readAsDataURL(file);
    }
}

function removeModalImage(event) {
    event.stopPropagation();
    const input = document.getElementById('modal-f-image');
    input.value = '';
    document.getElementById('modal-drop-placeholder').style.display = 'block';
    document.getElementById('modal-drop-preview').style.display = 'none';
    document.getElementById('modal-previewImg').src = '';
    document.getElementById('modal-preview-filename').textContent = '';
}
</script>
