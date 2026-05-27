<!-- Change Password - Form -->
<form id="ganti-password-form" method="POST" action="../controllers/update-password.php" style="display:none;">
    <div class="field-rows">
        <div class="field-row">
            <div class="field-icon-wrap" style="background: #fce8e6; color: #c0392b;">
                <i class="ti ti-lock"></i>
            </div>
            <div class="field-body">
                <div class="field-label">Old Password</div>
                <div style="position: relative; margin-top: 4px;">
                    <input type="password" name="old_password" id="old_password" placeholder="Enter old password" required class="field-input" style="padding-right: 40px;">
                    <button type="button" class="toggle-pw" onclick="togglePw('old_password', this)">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="field-row">
            <div class="field-icon-wrap" style="background: #fce8e6; color: #c0392b;">
                <i class="ti ti-lock-plus"></i>
            </div>
            <div class="field-body">
                <div class="field-label">New Password</div>
                <div style="position: relative; margin-top: 4px;">
                    <input type="password" name="new_password" id="new_password" placeholder="Minimum 8 characters" required class="field-input" style="padding-right: 40px;">
                    <button type="button" class="toggle-pw" onclick="togglePw('new_password', this)">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="field-row" style="border-bottom: none;">
            <div class="field-icon-wrap" style="background: #fce8e6; color: #c0392b;">
                <i class="ti ti-lock-check"></i>
            </div>
            <div class="field-body">
                <div class="field-label">Confirm New Password</div>
                <div style="position: relative; margin-top: 4px;">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat new password" required class="field-input" style="padding-right: 40px;">
                    <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="diri-footer" style="border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; margin-bottom: 0;">
        <span class="diri-footer-note">
            <i class="ti ti-shield-lock" style="font-size:14px"></i>
            Use a combination of letters, numbers, and symbols
        </span>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleGantiPassword(false)">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="ti ti-lock" style="font-size:13px"></i> Save Password
            </button>
        </div>
    </div>
</form>
