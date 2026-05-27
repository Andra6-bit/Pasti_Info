// ── TAB SWITCHING ─────────────────────────────────────────────
function switchTab(tab, el) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidenav-item').forEach(i => i.classList.remove('active'));
    var panel = document.getElementById('tab-' + tab);
    if (panel) panel.classList.add('active');
    if (el) el.classList.add('active');
    history.replaceState(null, '', '?tab=' + tab);
}

// ── MODAL HELPERS ─────────────────────────────────────────────
function closeOverlay() {
    document.getElementById('overlay').classList.remove('open');
    document.body.style.overflow = '';
}

function closeModal(e) {
    if (e.target === document.getElementById('overlay')) closeOverlay();
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeOverlay();
});

// ── REGISTRATION FEE LABEL ────────────────────────────────────
function updateBiayaLabel(input) {
    var badge = document.getElementById('biaya-badge');
    if (!badge) return;
    var val = parseInt(input.value) || 0;
    if (val === 0) {
        badge.textContent = 'Free';
        badge.style.background = '#dcfce7';
        badge.style.color = '#15803d';
    } else {
        badge.textContent = 'Rp ' + val.toLocaleString('id-ID');
        badge.style.background = '#fef9c3';
        badge.style.color = '#854d0e';
    }
}

// ── FORMAT RADIO ──────────────────────────────────────────────
function initPelaksanaanRadio() {
    var labels = document.querySelectorAll('.fg-radio');
    labels.forEach(function(lbl) {
        var input = lbl.querySelector('input[type="radio"]');
        if (!input) return;
        input.addEventListener('change', function() {
            labels.forEach(l => l.classList.remove('active'));
            if (input.checked) lbl.classList.add('active');
        });
        if (input.checked) lbl.classList.add('active');
    });
}

function setPelaksanaan(value) {
    var labels = document.querySelectorAll('.fg-radio');
    labels.forEach(function(lbl) {
        var input = lbl.querySelector('input[type="radio"]');
        if (!input) return;
        input.checked = (input.value === value);
        lbl.classList.toggle('active', input.checked);
    });
}

// ── CATEGORY CHIPS ────────────────────────────────────────────
function clearCategorySelection() {
    document.querySelectorAll('.cat-cb').forEach(function(cb) {
        cb.checked = false;
        cb.closest('.cat-chip').classList.remove('selected');
    });
}

function initCategoryChips() {
    document.querySelectorAll('.cat-chip').forEach(function(chip) {
        var cb = chip.querySelector('.cat-cb');
        if (!cb) return;
        chip.classList.toggle('selected', cb.checked);
        chip.addEventListener('click', function(e) {
            setTimeout(function() {
                chip.classList.toggle('selected', cb.checked);
            }, 0);
        });
    });
}

function setSelectedCategories(categoryStr) {
    clearCategorySelection();
    if (!categoryStr) return;
    var names = categoryStr.split(',').map(s => s.trim().toLowerCase());
    document.querySelectorAll('.cat-chip').forEach(function(chip) {
        var chipText = chip.textContent.trim().toLowerCase();
        if (names.includes(chipText)) {
            var cb = chip.querySelector('.cat-cb');
            if (cb) { cb.checked = true; chip.classList.add('selected'); }
        }
    });
}

// ── ADD NEW CATEGORY (AJAX) ──────────────────────────────────
function addNewCategory() {
    var input = document.getElementById('new-cat-input');
    var msgEl = document.getElementById('add-cat-msg');
    var name  = input.value.trim();

    if (!name) { showCatMsg('Please type a category name first.', 'error'); return; }
    if (name.length > 40) { showCatMsg('Category name is too long (max 40 characters).', 'error'); return; }

    var btn = document.getElementById('add-cat-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 0.8s linear infinite"></i>';

    var fd = new FormData();
    fd.append('action', 'add_category');
    fd.append('name', name);

    fetch('../controllers/manage-competitions.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-plus"></i> Add';
            if (data.ok) {
                if (data.already_exists) {
                    var existChip = document.querySelector('.cat-chip[data-id="' + data.id + '"]');
                    if (existChip) {
                        var cb = existChip.querySelector('.cat-cb');
                        cb.checked = true;
                        existChip.classList.add('selected');
                        existChip.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                    showCatMsg('Category already exists and has been selected.', 'info');
                } else {
                    appendCategoryChip(data.id, data.name, true);
                    showCatMsg('Category "' + data.name + '" successfully added!', 'success');
                }
                input.value = '';
            } else {
                showCatMsg(data.msg || 'Failed to add category.', 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-plus"></i> Add';
            showCatMsg('Connection failed, please try again.', 'error');
        });
}

function appendCategoryChip(id, name, selected) {
    var grid = document.getElementById('category-grid');
    if (!grid) return;
    var chip = document.createElement('label');
    chip.className = 'cat-chip' + (selected ? ' selected' : '');
    chip.setAttribute('data-id', id);
    chip.innerHTML = '<input type="checkbox" name="categories[]" value="' + id + '" class="cat-cb"' + (selected ? ' checked' : '') + '>' + escHtml(name);
    chip.addEventListener('click', function() {
        setTimeout(function() {
            var cb = chip.querySelector('.cat-cb');
            chip.classList.toggle('selected', cb.checked);
        }, 0);
    });
    grid.appendChild(chip);
    if (typeof ALL_CATS !== 'undefined') ALL_CATS.push({ id: id, name: name });
}

function showCatMsg(msg, type) {
    var el = document.getElementById('add-cat-msg');
    if (!el) return;
    el.textContent = msg;
    el.className   = 'add-cat-msg add-cat-msg-' + type;
    el.style.display = 'block';
    setTimeout(function() { el.style.display = 'none'; }, 3500);
}

function escHtml(str) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

// ── IMAGE UPLOAD ──────────────────────────────────────────────
function handleImageSelect(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    var allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowed.includes(file.type)) {
        alert('Format not supported. Use JPG, PNG, WebP, or GIF.');
        input.value = '';
        return;
    }
    if (file.size > 3 * 1024 * 1024) {
        alert('File size too large. Maximum 3MB.');
        input.value = '';
        return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('preview-filename').textContent = file.name;
        document.getElementById('drop-placeholder').style.display = 'none';
        document.getElementById('drop-preview').style.display     = 'flex';
    };
    reader.readAsDataURL(file);
}

// ── REMOVE IMAGE ──────────────────────────────────────────────
function removeImage(e) {
    e.stopPropagation();
    document.getElementById('f-image').value = '';
    document.getElementById('previewImg').src = '';
    document.getElementById('drop-placeholder').style.display = 'block';
    document.getElementById('drop-preview').style.display     = 'none';
}

// ── DESCRIPTION CHARACTER COUNTER ─────────────────────────────
function initDescCounter() {
    var desc = document.getElementById('f-desc');
    var cnt  = document.getElementById('desc-count');
    if (!desc || !cnt) return;
    function update() { cnt.textContent = desc.value.length; }
    desc.addEventListener('input', update);
    update();
}

// ── OPEN ADD ──────────────────────────────────────────────────
function openAdd() {
    document.getElementById('modal-title').textContent    = 'Add Competition';
    document.getElementById('modal-subtitle').textContent = 'Fill in all required fields';
    document.getElementById('f-action').value             = 'add';
    document.getElementById('f-id').value                 = '';
    document.getElementById('f-old-img').value            = 'default.jpg';
    document.getElementById('f-title').value              = '';
    document.getElementById('f-target-peserta').value     = 'Umum';
    document.getElementById('f-biaya').value              = '0';
    document.getElementById('f-start-date').value         = '';
    document.getElementById('f-end-date').value           = '';
    document.getElementById('f-link-pendaftaran').value   = '';
    document.getElementById('f-desc').value               = '';
    document.getElementById('f-submit').innerHTML         = '<i class="ti ti-plus" style="font-size:14px"></i> Add Competition';
    document.getElementById('img-req-label').style.display = 'inline';

    setPelaksanaan('Online');
    clearCategorySelection();
    removeImage({ stopPropagation: function(){} });
    updateBiayaLabel({ value: '0' });
    initDescCounter();

    document.getElementById('overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(function() { document.getElementById('f-title').focus(); }, 200);
}

// ── OPEN EDIT ─────────────────────────────────────────────────
function openEdit(data) {
    if (typeof data !== 'object' || data === null) return;

    document.getElementById('modal-title').textContent    = 'Edit Competition';
    document.getElementById('modal-subtitle').textContent = 'Update competition details';
    document.getElementById('f-action').value             = 'edit';
    document.getElementById('f-id').value                 = data.id;
    document.getElementById('f-old-img').value            = data.image || 'default.jpg';
    document.getElementById('f-title').value              = data.title || '';
    document.getElementById('f-target-peserta').value     = data.target_audience || 'Umum'; // Map database target_audience
    document.getElementById('f-biaya').value              = data.registration_fee || '0'; // Map database registration_fee
    document.getElementById('f-link-pendaftaran').value   = data.registration_link || ''; // Map database registration_link
    document.getElementById('f-desc').value               = data.description || '';
    document.getElementById('f-submit').innerHTML         = '<i class="ti ti-device-floppy" style="font-size:14px"></i> Save Changes';
    document.getElementById('img-req-label').style.display = 'none';

    setPelaksanaan(data.format || 'Online'); // Map database format
    setSelectedCategories(data.category || '');
    updateBiayaLabel({ value: data.registration_fee || '0' });
    initDescCounter();

    var dr = data.date_range || '';
    var d1 = '', d2 = '';
    if (dr.includes(',')) {
        var parts = dr.split(',');
        d1 = parts[0].trim();
        d2 = parts[1].trim();
    } else if (dr.includes('/')) {
        var parts = dr.split('-');
        if (parts.length === 2) {
            d1 = parts[0].replace(/\//g, '-');
            d2 = parts[1].replace(/\//g, '-');
        }
    }
    document.getElementById('f-start-date').value = d1;
    document.getElementById('f-end-date').value   = d2;

    if (data.image && data.image !== 'default.jpg') {
        document.getElementById('previewImg').src = '../assets/images/' + data.image;
        document.getElementById('preview-filename').textContent = data.image;
        document.getElementById('drop-placeholder').style.display = 'none';
        document.getElementById('drop-preview').style.display     = 'flex';
    } else {
        document.getElementById('drop-placeholder').style.display = 'block';
        document.getElementById('drop-preview').style.display     = 'none';
    }

    document.getElementById('overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

// ── DRAG & DROP ───────────────────────────────────────────────
function initDragDrop() {
    var zone = document.getElementById('file-drop-zone');
    if (!zone) return;

    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        zone.classList.add('drag-over');
    });
    zone.addEventListener('dragleave', function() {
        zone.classList.remove('drag-over');
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.classList.remove('drag-over');
        var dt = e.dataTransfer;
        if (dt && dt.files.length) {
            var input = document.getElementById('f-image');
            input.files = dt.files;
            handleImageSelect(input);
        }
    });
}

// ── FORM VALIDATION ───────────────────────────────────────────
function initFormValidation() {
    var form = document.getElementById('competition-form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        var action = document.getElementById('f-action').value;

        // Image validation for new entries
        if (action === 'add') {
            var imgInput = document.getElementById('f-image');
            if (!imgInput || !imgInput.files || imgInput.files.length === 0) {
                e.preventDefault();
                alert('Competition poster/image is required.');
                document.getElementById('file-drop-zone').scrollIntoView({ behavior: 'smooth' });
                return false;
            }
        }

        // Category validation
        var checked = document.querySelectorAll('.cat-cb:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('Select at least one category.');
            document.getElementById('category-grid').scrollIntoView({ behavior: 'smooth' });
            return false;
        }

        // Registration link validation
        var linkInfo = document.getElementById('f-link-pendaftaran').value.trim();
        if (!linkInfo) {
            e.preventDefault();
            alert('Registration link is required.');
            document.getElementById('f-link-pendaftaran').focus();
            document.getElementById('f-link-pendaftaran').scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        if (!/^https?:\/\/.+/.test(linkInfo)) {
            e.preventDefault();
            alert('Invalid registration link format. Use: https://...');
            document.getElementById('f-link-pendaftaran').focus();
            return false;
        }

        // Date range validation
        var startDate = document.getElementById('f-start-date').value;
        var endDate   = document.getElementById('f-end-date').value;
        if (startDate && endDate && startDate > endDate) {
            e.preventDefault();
            alert('End date cannot be before start date.');
            return false;
        }
    });
}

// ── INIT ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    initPelaksanaanRadio();
    initCategoryChips();
    initDragDrop();
    initFormValidation();
    initDescCounter();
});

// ── DATA DIRI PROFILE TOGGLE ──────────────────────────────────
function toggleEditProfile(isEditing) {
    var viewMode = document.getElementById('profile-view-mode');
    var editMode = document.getElementById('profile-edit-mode');
    if (isEditing) {
        if (viewMode) viewMode.style.display = 'none';
        if (editMode) editMode.style.display = 'block';
    } else {
        if (viewMode) viewMode.style.display = 'block';
        if (editMode) editMode.style.display = 'none';
    }
}

function toggleGantiPassword(isEditing) {
    var view = document.getElementById('ganti-password-view');
    var form = document.getElementById('ganti-password-form');
    if (isEditing) {
        view.style.display = 'none';
        form.style.display = 'block';
    } else {
        view.style.display = 'block';
        form.style.display = 'none';
        form.reset();
    }
}

function togglePw(fieldId, btn) {
    var input = document.getElementById(fieldId);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ti ti-eye-off';
    } else {
        input.type = 'password';
        icon.className = 'ti ti-eye';
    }
}

// ── SPINNER KEYFRAME ──────────────────────────────────────────
var spinStyle = document.createElement('style');
spinStyle.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(spinStyle);

// ── TELEGRAM TOGGLE & CATEGORY AJAX FOR SETTINGS TAB ──────────
function updateTelegramToggleState() {
    var chatIdValEl = document.getElementById('telegram-chat-id-val');
    var telegramChatId = chatIdValEl ? chatIdValEl.getAttribute('data-chat-id').trim() : '';
    
    var checkedCats = document.querySelectorAll('#subscription-grid input[type="checkbox"]:checked').length;
    
    var statusVal = document.getElementById('telegram-status-value');
    var statusNote = document.getElementById('telegram-status-note');
    var toggleBtn = document.getElementById('telegram-toggle-button');
    var toggleKnob = toggleBtn ? toggleBtn.querySelector('.notif-toggle-knob') : null;
    
    if (!statusVal || !statusNote || !toggleBtn) return;
    
    var hasChatId = telegramChatId !== '';
    var hasCats = checkedCats > 0;
    
    if (hasChatId && hasCats) {
        statusVal.textContent = 'Active';
        statusVal.style.color = '#1e7e34';
        statusVal.style.fontWeight = '600';
        statusNote.innerHTML = 'Chat ID: ' + escapeHTML(telegramChatId);
        
        toggleBtn.className = 'notif-toggle';
        toggleBtn.style.background = '#185FA5';
        toggleBtn.style.cursor = 'default';
        toggleBtn.removeAttribute('title');
        if (toggleKnob) toggleKnob.style.transform = 'translateX(20px)';
    } else {
        statusVal.textContent = 'Cannot be activated yet';
        statusVal.style.color = '#94a3b8';
        statusVal.style.fontWeight = '500';
        
        var reasons = [];
        if (!hasChatId) {
            reasons.push('Set <strong>Telegram Chat ID</strong> in the <span class="notif-link" onclick="switchTab(\'data-diri\', document.querySelector(\'.sidenav-item\'))">Profile Info</span> tab first');
        }
        if (!hasCats) {
            reasons.push('select at least one category below');
        }
        
        statusNote.innerHTML = reasons.join(', and ');
        
        toggleBtn.className = 'notif-toggle notif-toggle-disabled';
        toggleBtn.style.background = '#e2e8f0';
        toggleBtn.style.cursor = 'not-allowed';
        toggleBtn.setAttribute('title', 'Set Chat ID and select at least one category');
        if (toggleKnob) toggleKnob.style.transform = 'translateX(0)';
    }
}

function escapeHTML(str) {
    return str.replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}

// Bind event listeners for Settings tab (subscription form)
document.addEventListener('DOMContentLoaded', function() {
    updateTelegramToggleState();
    
    var grid = document.getElementById('subscription-grid');
    if (grid) {
        grid.addEventListener('change', function(e) {
            if (e.target && e.target.type === 'checkbox') {
                updateTelegramToggleState();
            }
        });
    }
    
    var addBtn = document.getElementById('add-category-btn');
    var addInput = document.getElementById('new-category-input');
    var addMsg = document.getElementById('add-category-msg');
    
    if (addBtn && addInput) {
        addInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addBtn.click();
            }
        });

        addBtn.addEventListener('click', function() {
            var name = addInput.value.trim();
            if (!name) {
                showMsg('Category name cannot be empty.', 'red');
                return;
            }
            if (name.length > 30) {
                showMsg('Category name must be maximum 30 characters.', 'red');
                return;
            }
            if (!/^[a-zA-Z0-9\s]+$/.test(name)) {
                showMsg('Category name must only contain letters, numbers, and spaces.', 'red');
                return;
            }
            
            addBtn.disabled = true;
            addBtn.innerHTML = 'Adding...';
            
            var formData = new FormData();
            formData.append('name', name);
            
            fetch('../controllers/add-category.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                addBtn.disabled = false;
                addBtn.innerHTML = '<i class="ti ti-plus" style="font-size: 12px;"></i> Add';
                
                if (data.ok) {
                    showMsg('Category added and subscribed successfully!', 'green');
                    addInput.value = '';
                    
                    var existingCheckbox = document.querySelector(`#subscription-grid input[value="${data.id}"]`);
                    if (existingCheckbox) {
                        existingCheckbox.checked = true;
                    } else {
                        var label = document.createElement('label');
                        label.style.cssText = 'display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; cursor: pointer; user-select: none; color: var(--color-text-secondary); margin: 0;';
                        label.innerHTML = `
                            <input type="checkbox" name="categories[]" value="${data.id}" checked style="width: 16px; height: 16px; cursor: pointer; accent-color: #185FA5; margin: 0;">
                            <span class="category-name" style="margin-left: 6px;">${escapeHTML(data.name)}</span>
                        `;
                        grid.appendChild(label);
                    }
                    updateTelegramToggleState();
                } else {
                    showMsg(data.msg || 'Failed to add category.', 'red');
                }
            })
            .catch(err => {
                addBtn.disabled = false;
                addBtn.innerHTML = '<i class="ti ti-plus" style="font-size: 12px;"></i> Add';
                showMsg('Error sending request.', 'red');
                console.error(err);
            });
        });
    }
    
    function showMsg(text, color) {
        if (!addMsg) return;
        addMsg.textContent = text;
        addMsg.style.color = color === 'red' ? '#e53e3e' : '#2f855a';
        addMsg.style.display = 'block';
        setTimeout(function() {
            addMsg.style.display = 'none';
        }, 5000);
    }
});