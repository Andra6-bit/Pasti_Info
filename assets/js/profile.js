// Mengatur perpindahan tab pada halaman profile
function switchTab(tab, el) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidenav-item').forEach(i => i.classList.remove('active'));
    var panel = document.getElementById('tab-' + tab);
    if (panel) panel.classList.add('active');
    if (el) el.classList.add('active');
    history.replaceState(null, '', '?tab=' + tab);
}

// Mengatur toggle edit mode pada tab Data Diri
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
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ti ti-eye-off';
    } else {
        input.type = 'password';
        icon.className = 'ti ti-eye';
    }
}

// ── TELEGRAM TOGGLE STATE ──────────────────────────────
function updateTelegramToggleState() {
    var chatIdValEl = document.getElementById('telegram-chat-id-val');
    var telegramChatId = chatIdValEl ? chatIdValEl.getAttribute('data-chat-id').trim() : '';
    
    // Count active pills
    var checkedCats = document.querySelectorAll('#subscribed-pills-row .subscription-pill').length;
    
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
            reasons.push('Set <strong>Telegram Chat ID</strong> in the <span class="notif-link" onclick="switchTab(\'profile-info\', document.querySelector(\'.sidenav-item\'))">Profile Info</span> tab first');
        }
        if (!hasCats) {
            reasons.push('subscribe to at least one category above');
        }
        
        statusNote.innerHTML = reasons.join(', and ');
        
        toggleBtn.className = 'notif-toggle notif-toggle-disabled';
        toggleBtn.style.background = '#e2e8f0';
        toggleBtn.style.cursor = 'not-allowed';
        toggleBtn.setAttribute('title', 'Set Chat ID and subscribe to at least one category');
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

// ── SYNC MODAL CHIPS WITH MAIN PILLS ───────────────────
function syncChipsFromPills() {
    var pills = document.querySelectorAll('#subscribed-pills-row .subscription-pill');
    var activeIds = Array.from(pills).map(p => p.getAttribute('data-category-id'));
    
    document.querySelectorAll('#modal-chip-grid .category-chip').forEach(chip => {
        var chipId = chip.getAttribute('data-category-id');
        if (activeIds.includes(chipId)) {
            chip.classList.add('selected');
        } else {
            chip.classList.remove('selected');
        }
    });
}

// ── REBUILD MAIN PILLS ROW BASED ON SELECTIONS ────────
function rebuildPillsRow() {
    var pillsRow = document.getElementById('subscribed-pills-row');
    if (!pillsRow) return;
    
    pillsRow.innerHTML = '';
    
    var selectedChips = document.querySelectorAll('#modal-chip-grid .category-chip.selected');
    if (selectedChips.length === 0) {
        pillsRow.innerHTML = '<span id="no-subscriptions-msg" class="field-empty" style="font-size: 12px; color: #b0bec5; font-style: italic;">No subscribed categories yet</span>';
    } else {
        selectedChips.forEach(function(chip) {
            var catId = chip.getAttribute('data-category-id');
            var catName = chip.getAttribute('data-name') || chip.textContent.trim();
            
            var pill = document.createElement('span');
            pill.className = 'subscription-pill';
            pill.setAttribute('data-category-id', catId);
            pill.style.cssText = 'display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #E6F1FB; color: #185FA5; border-radius: 16px; font-size: 12px; font-weight: 600; border: 1px solid #c8ddf2; transition: all 0.2s;';
            pill.innerHTML = escapeHTML(catName) + ' <span class="remove-pill-btn" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border-radius: 50%; font-size: 14px; line-height: 1; color: #94a3b8; font-weight: 700;" title="Remove subscription">&times;</span>';
            pillsRow.appendChild(pill);
        });
    }
    updateTelegramToggleState();
}

// ── PERSIST SUBSCRIPTIONS VIA AJAX ─────────────────────
function saveSubscriptions(onSuccess) {
    var selectedChips = document.querySelectorAll('#modal-chip-grid .category-chip.selected');
    var formData = new FormData();
    
    selectedChips.forEach(function(chip) {
        formData.append('categories[]', chip.getAttribute('data-category-id'));
    });
    formData.append('ajax', '1');
    
    fetch('../controllers/update-subscriptions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            if (onSuccess) onSuccess();
        } else {
            alert(data.msg || 'Failed to update subscriptions.');
        }
    })
    .catch(err => {
        console.error('Error saving subscriptions:', err);
        alert('Error saving subscriptions.');
    });
}

// ── DOM CONTENT LOADED INITIALIZATIONS ─────────────────
document.addEventListener('DOMContentLoaded', function() {
    updateTelegramToggleState();
    
    // ── MODAL VISIBILITY HANDLERS ──
    var modal = document.getElementById('categories-modal');
    var openModalBtn = document.getElementById('open-subs-modal-btn');
    var closeModalX = document.getElementById('close-subs-modal-x');
    var closeModalBtn = document.getElementById('close-subs-modal-btn');
    
    function openModal() {
        if (!modal) return;
        syncChipsFromPills();
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('active'), 10);
    }
    
    function closeModal() {
        if (!modal) return;
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 250);
    }
    
    if (openModalBtn) openModalBtn.addEventListener('click', openModal);
    if (closeModalX) closeModalX.addEventListener('click', closeModal);
    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside modal box
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // ── CHIP GRID INTERACTION ──
    var chipGrid = document.getElementById('modal-chip-grid');
    if (chipGrid) {
        chipGrid.addEventListener('click', function(e) {
            var chip = e.target.closest('.category-chip');
            if (chip) {
                chip.classList.toggle('selected');
            }
        });
    }
    
    // ── SAVE CHANGES FROM MODAL ──
    var saveModalBtn = document.getElementById('save-subs-modal-btn');
    if (saveModalBtn) {
        saveModalBtn.addEventListener('click', function() {
            saveModalBtn.disabled = true;
            var originalHtml = saveModalBtn.innerHTML;
            saveModalBtn.innerHTML = 'Saving...';
            
            saveSubscriptions(function() {
                saveModalBtn.disabled = false;
                saveModalBtn.innerHTML = originalHtml;
                rebuildPillsRow();
                closeModal();
            });
        });
    }
    
    // ── DIRECT PILL DELETION (NO POPUP) ──
    var pillsRow = document.getElementById('subscribed-pills-row');
    if (pillsRow) {
        pillsRow.addEventListener('click', function(e) {
            var removeBtn = e.target.closest('.remove-pill-btn');
            if (removeBtn) {
                var pill = removeBtn.closest('.subscription-pill');
                if (pill) {
                    var catId = pill.getAttribute('data-category-id');
                    
                    // Add fade-out transition
                    pill.style.opacity = '0';
                    pill.style.transform = 'scale(0.8)';
                    
                    setTimeout(function() {
                        pill.remove();
                        
                        // Sync modal chips in the background
                        var matchingChip = document.querySelector(`#modal-chip-grid .category-chip[data-category-id="${catId}"]`);
                        if (matchingChip) {
                            matchingChip.classList.remove('selected');
                        }
                        
                        // Save the updated subscription list immediately via AJAX
                        saveSubscriptions(function() {
                            // If zero pills remain, display empty state message
                            if (document.querySelectorAll('#subscribed-pills-row .subscription-pill').length === 0) {
                                pillsRow.innerHTML = '<span id="no-subscriptions-msg" class="field-empty" style="font-size: 12px; color: #b0bec5; font-style: italic;">No subscribed categories yet</span>';
                            }
                            updateTelegramToggleState();
                        });
                    }, 200);
                }
            }
        });
    }
    
    // ── CUSTOM CATEGORY INPUT & COUNTER ──
    var addBtn = document.getElementById('modal-add-cat-btn');
    var addInput = document.getElementById('modal-custom-cat-input');
    var charCounter = document.getElementById('char-counter');
    var addMsg = document.getElementById('modal-add-cat-msg');
    
    if (addInput && charCounter) {
        addInput.addEventListener('input', function() {
            var len = addInput.value.length;
            charCounter.textContent = len + '/30';
            if (len >= 30) {
                charCounter.style.color = '#e53e3e';
            } else {
                charCounter.style.color = '#94a3b8';
            }
        });
    }
    
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
                    showMsg('Category added successfully!', 'green');
                    addInput.value = '';
                    if (charCounter) charCounter.textContent = '0/30';
                    
                    // Check if the chip already exists in the grid
                    var existingChip = document.querySelector(`#modal-chip-grid .category-chip[data-category-id="${data.id}"]`);
                    if (existingChip) {
                        existingChip.classList.add('selected');
                    } else {
                        // Create a new chip and append it to grid
                        var newChip = document.createElement('div');
                        newChip.className = 'category-chip selected';
                        newChip.setAttribute('data-category-id', data.id);
                        newChip.setAttribute('data-name', data.name);
                        newChip.style.cssText = 'padding: 8px 6px; text-align: center; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.2s; user-select: none; color: #4a6070; background: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;';
                        newChip.title = data.name;
                        newChip.textContent = data.name;
                        
                        var grid = document.getElementById('modal-chip-grid');
                        if (grid) {
                            grid.appendChild(newChip);
                        }
                    }
                    
                    // Rebuild pills row automatically so it reflects current subscription state
                    rebuildPillsRow();
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