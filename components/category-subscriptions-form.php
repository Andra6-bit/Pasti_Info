<!-- Category Subscriptions Form -->
<div class="field-rows">
    <div class="field-row">
        <div class="field-icon-wrap" style="background: #E8F5E9; color: #2E7D32;">
            <i class="ti ti-tags"></i>
        </div>
        <div class="field-body">
            <div class="field-label">Category Subscriptions</div>
            
            <!-- Active Subscribed Pills Row -->
            <div id="subscribed-pills-row" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; margin-bottom: 6px; min-height: 28px; align-items: center;">
                <?php 
                $has_subs = false;
                foreach ($all_cats as $cat): 
                    $is_subscribed = in_array((int)$cat['id'], $subscribed_cats);
                    if ($is_subscribed):
                        $has_subs = true;
                ?>
                    <span class="subscription-pill" data-category-id="<?= (int)$cat['id'] ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #E6F1FB; color: #185FA5; border-radius: 16px; font-size: 12px; font-weight: 600; border: 1px solid #c8ddf2; transition: all 0.2s;">
                        <?= htmlspecialchars($cat['name']) ?>
                        <span class="remove-pill-btn" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border-radius: 50%; font-size: 14px; line-height: 1; color: #94a3b8; font-weight: 700;" title="Remove subscription">&times;</span>
                    </span>
                <?php 
                    endif;
                endforeach; 
                if (!$has_subs):
                ?>
                    <span id="no-subscriptions-msg" class="field-empty" style="font-size: 12px; color: #b0bec5; font-style: italic;">No subscribed categories yet</span>
                <?php endif; ?>
            </div>
            
            <div class="field-sub-note">Receive notifications for competitions under these categories</div>
        </div>
        <div class="field-status">
            <button type="button" id="open-subs-modal-btn" class="edit-btn">
                <i class="ti ti-edit" style="font-size: 13px"></i> Manage
            </button>
        </div>
    </div>
</div>

<!-- Category Selection Modal -->
<div id="categories-modal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 100000; justify-content: center; align-items: center; transition: opacity 0.25s ease;">
    <div class="modal-box" style="background: white; border-radius: 16px; width: 90%; max-width: 480px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; transform: scale(0.95); transition: transform 0.25s ease; display: flex; flex-direction: column; max-height: 90vh;">
        
        <!-- Modal Header -->
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1a2a3a;">Select Categories</h3>
            <span id="close-subs-modal-x" style="cursor: pointer; font-size: 20px; color: #94a3b8; line-height: 1; transition: color 0.2s; font-weight: 700;">&times;</span>
        </div>

        <!-- Modal Body -->
        <div style="padding: 20px; overflow-y: auto; flex: 1;">
            <p style="margin: 0 0 16px 0; font-size: 13px; color: #64748b; font-weight: 500;">Choose the competition categories you want to subscribe to:</p>
            
            <!-- 3-Column Chip Grid -->
            <div id="modal-chip-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 24px;">
                <?php foreach ($all_cats as $cat): 
                    $is_subscribed = in_array((int)$cat['id'], $subscribed_cats);
                ?>
                    <div class="category-chip <?= $is_subscribed ? 'selected' : '' ?>" data-category-id="<?= (int)$cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>" style="padding: 8px 6px; text-align: center; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.2s; user-select: none; color: #4a6070; background: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($cat['name']) ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Custom Category Input Section -->
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 16px;">
                <label class="field-label" style="margin-bottom: 8px; display: block; font-weight: 700; font-size: 11px; color: #94a3b8;">Add Custom Category</label>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="text" id="modal-custom-cat-input" placeholder="e.g., UI/UX Design" maxlength="30" style="flex: 1; padding: 8px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; background: #f8fafc; transition: all 0.2s;">
                    <button type="button" id="modal-add-cat-btn" class="btn btn-secondary btn-sm" style="flex-shrink: 0; padding: 8px 14px; background: #64748b; color: white; border: none; border-radius: 8px; cursor: pointer; height: 38px; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <i class="ti ti-plus" style="font-size: 12px;"></i> Add
                    </button>
                </div>
                <div style="margin-top: 6px; font-size: 11px; color: #94a3b8; display: flex; justify-content: space-between;">
                    <span>Max 30 characters. Letters, numbers, spaces only.</span>
                    <span id="char-counter">0/30</span>
                </div>
                <div id="modal-add-cat-msg" style="display: none; font-size: 12px; margin-top: 8px; font-weight: 500;"></div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding: 16px 20px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" id="close-subs-modal-btn" class="btn btn-secondary btn-sm" style="padding: 8px 16px; border-radius: 8px;">Cancel</button>
            <button type="button" id="save-subs-modal-btn" class="btn btn-primary btn-sm" style="padding: 8px 16px; border-radius: 8px; display: flex; align-items: center; gap: 6px;">
                <i class="ti ti-device-floppy" style="font-size: 14px;"></i> Save Changes
            </button>
        </div>
    </div>
</div>
