<?php
/**
 * components/my-submissions.php
 *
 * Included in pages/profile.php. Lists the user's submitted competitions,
 * their Xendit invoice links, and approval statuses.
 */

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    exit('Access denied.');
}

$user_id = (int)($_SESSION['user_id'] ?? 0);

// Fetch user's submissions
$subs_query = "SELECT * FROM competitions WHERE user_id = ? ORDER BY id DESC";
$subs_stmt = mysqli_prepare($koneksi, $subs_query);
$submissions = [];
if ($subs_stmt) {
    mysqli_stmt_bind_param($subs_stmt, "i", $user_id);
    mysqli_stmt_execute($subs_stmt);
    $subs_res = mysqli_stmt_get_result($subs_stmt);
    while ($row = mysqli_fetch_assoc($subs_res)) {
        $submissions[] = $row;
    }
    mysqli_stmt_close($subs_stmt);
}

// Active synchronization with Xendit for unpaid submissions
require_once __DIR__ . '/../helpers/xendit.php';
require_once __DIR__ . '/../helpers/telegram-notification.php';

foreach ($submissions as $index => $row) {
    $pay_status = strtolower($row['payment_status'] ?? 'unpaid');
    if ($pay_status === 'unpaid' && !empty($row['xendit_invoice_id'])) {
        $invoice = getXenditInvoice($row['xendit_invoice_id']);
        if ($invoice && isset($invoice['status'])) {
            $inv_status = strtoupper($invoice['status']);
            if ($inv_status === 'PAID' || $inv_status === 'SETTLED') {
                // Update DB status to Paid and Pending Review
                $up_sql = "UPDATE competitions SET payment_status = 'paid', submission_status = 'pending_review', paid_at = NOW() WHERE id = ?";
                $up_stmt = mysqli_prepare($koneksi, $up_sql);
                if ($up_stmt) {
                    mysqli_stmt_bind_param($up_stmt, "i", $row['id']);
                    mysqli_stmt_execute($up_stmt);
                    mysqli_stmt_close($up_stmt);
                }
                
                // Update local array variable
                $submissions[$index]['payment_status'] = 'paid';
                $submissions[$index]['submission_status'] = 'pending_review';
                $submissions[$index]['paid_at'] = date('Y-m-d H:i:s');
                
                // Send Telegram Notification
                $chat_id = $row['telegram_chat_id'] ?? '';
                if (empty($chat_id)) {
                    $u_stmt = mysqli_prepare($koneksi, "SELECT telegram_chat_id FROM users WHERE id = ? LIMIT 1");
                    if ($u_stmt) {
                        mysqli_stmt_bind_param($u_stmt, "i", $user_id);
                        mysqli_stmt_execute($u_stmt);
                        $u_res = mysqli_stmt_get_result($u_stmt);
                        if ($u_row = mysqli_fetch_assoc($u_res)) {
                            $chat_id = $u_row['telegram_chat_id'];
                        }
                        mysqli_stmt_close($u_stmt);
                    }
                }
                notifyPaymentSuccess($chat_id, $row['title'], $row['xendit_invoice_id']);
            } elseif ($inv_status === 'EXPIRED') {
                // Update DB status to Expired
                $up_sql = "UPDATE competitions SET payment_status = 'expired', submission_status = 'expired' WHERE id = ?";
                $up_stmt = mysqli_prepare($koneksi, $up_sql);
                if ($up_stmt) {
                    mysqli_stmt_bind_param($up_stmt, "i", $row['id']);
                    mysqli_stmt_execute($up_stmt);
                    mysqli_stmt_close($up_stmt);
                }
                
                // Update local array variable
                $submissions[$index]['payment_status'] = 'expired';
                $submissions[$index]['submission_status'] = 'expired';
            }
        }
    }
}
$subs_count = count($submissions);
?>
<style>
.subs-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 50px;
    font-size: 11.5px;
    font-weight: 700;
    white-space: nowrap;
    border: 1px solid transparent;
}
.subs-pill i {
    font-size: 13px;
}
.subs-pill-paid {
    background: #eff6ff;
    color: #1e40af;
    border-color: #bfdbfe;
}
.subs-pill-unpaid {
    background: #fffbeb;
    color: #92400e;
    border-color: #fde68a;
}
.subs-pill-refunded {
    background: #f1f5f9;
    color: #475569;
    border-color: #cbd5e1;
}
.subs-pill-failed {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fca5a5;
}
.subs-pill-published, .subs-pill-approved {
    background: #ecfdf5;
    color: #065f46;
    border-color: #a7f3d0;
}
.subs-pill-awaiting-payment {
    background: #fff7ed;
    color: #c2410c;
    border-color: #ffedd5;
}
.subs-pill-under-review {
    background: #f5f3ff;
    color: #6d28d9;
    border-color: #ddd6fe;
}
.subs-btn-pay {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    background: #185fa5;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s;
}
.subs-btn-pay:hover {
    background: #0f4a81;
    color: #ffffff;
}
.subs-btn-view {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #185fa5;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
}
.subs-btn-view:hover {
    text-decoration: underline;
}
</style>

<div class="tab-panel <?= $active_tab === 'my-submissions' ? 'active' : '' ?>" id="tab-my-submissions">
    
    <div class="section-head" style="border-bottom: none;">
        <div class="section-head-left">
            <div class="section-head-icon" style="background: #EAF3FC; color: #185FA5;">
                <i class="ti ti-trophy" style="font-size:18px"></i>
            </div>
            <div>
                <span class="section-title">My Submissions</span>
                <div class="section-sub">Track and pay for your submitted competitions</div>
            </div>
        </div>
    </div>

    <div class="subs-toolbar">
        <span class="toolbar-info"><?= $subs_count ?> competitions submitted</span>
        <a href="javascript:void(0)" onclick="openSubmitCompetitionModal()" class="btn btn-primary btn-sm" style="border-radius: 20px; font-weight: 700;">
            <i class="ti ti-plus" style="font-size:12px"></i> Submit Competition
        </a>
    </div>

    <?php if ($subs_count === 0): ?>
        <div style="padding: 48px 20px; text-align: center;">
            <i class="ti ti-trophy-off empty-state-icon"></i>
            <p class="empty-state-text">No submissions yet</p>
            <p class="empty-state-sub">Click <strong>"Submit Competition"</strong> to start listing your competition.</p>
        </div>
    <?php else: ?>
        <div class="subs-table-wrap">
            <table class="subs-table">
                <thead>
                    <tr>
                        <th>Competition</th>
                        <th>Payment Status</th>
                        <th>Approval Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $row): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--navy); font-size: 13.5px;"><?= htmlspecialchars($row['title']) ?></div>
                                <div style="font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 3px;">
                                    UID: <?= htmlspecialchars($row['uid']) ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $pay_status = strtolower($row['payment_status'] ?? 'unpaid');
                                if ($pay_status === 'paid'):
                                ?>
                                    <span class="subs-pill subs-pill-paid"><i class="ti ti-circle-check"></i> Paid</span>
                                <?php elseif ($pay_status === 'unpaid'): ?>
                                    <span class="subs-pill subs-pill-unpaid"><i class="ti ti-credit-card"></i> Unpaid</span>
                                    <?php if (!empty($row['xendit_invoice_url'])): ?>
                                        <div>
                                            <a href="<?= htmlspecialchars($row['xendit_invoice_url']) ?>" target="_blank" class="subs-btn-pay">
                                                <i class="ti ti-wallet"></i> Pay Now
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($pay_status === 'refunded'): ?>
                                    <span class="subs-pill subs-pill-refunded"><i class="ti ti-arrow-back-up"></i> Refunded</span>
                                <?php else: ?>
                                    <span class="subs-pill subs-pill-failed"><i class="ti ti-circle-x"></i> <?= ucfirst($pay_status) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $app_status = strtolower($row['approval_status'] ?? 'pending');
                                $sub_status = strtolower($row['submission_status'] ?? 'unpaid');
                                
                                if ($app_status === 'approved' && $sub_status === 'published'):
                                ?>
                                    <span class="subs-pill subs-pill-published"><i class="ti ti-world"></i> Published</span>
                                <?php elseif ($app_status === 'approved'): ?>
                                    <span class="subs-pill subs-pill-approved"><i class="ti ti-circle-check"></i> Approved</span>
                                <?php elseif ($app_status === 'rejected'): ?>
                                    <span class="subs-pill subs-pill-failed"><i class="ti ti-circle-x"></i> Rejected</span>
                                    <?php if (!empty($row['review_note'])): ?>
                                        <div style="font-size: 11px; color: #c0392b; margin-top: 4px; font-style: italic; max-width: 200px; word-break: break-word;">
                                            Note: <?= htmlspecialchars($row['review_note']) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($pay_status === 'unpaid'): ?>
                                        <span class="subs-pill subs-pill-awaiting-payment"><i class="ti ti-hourglass-empty"></i> Awaiting Payment</span>
                                    <?php else: ?>
                                        <span class="subs-pill subs-pill-under-review"><i class="ti ti-search"></i> Under Review</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="javascript:void(0)" onclick="viewUserSubmissionDetails(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)" class="subs-btn-view">
                                    <i class="ti ti-eye"></i> View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="diri-footer" style="padding: 14px 24px; background: #fafcff; border-top: 1px solid var(--color-border-tertiary);">
        <span class="diri-footer-note">
            <i class="ti ti-info-circle" style="font-size:14px"></i>
            Approval typically takes 1-2 business days after payment.
        </span>
    </div>
</div>

<!-- MODAL: VIEW DETAILS -->
<div class="submit-overlay" id="user-details-modal" onclick="closeUserDetailsModal(event)">
    <div class="submit-modal-box" onclick="event.stopPropagation()">
        <button type="button" class="submit-modal-close" onclick="toggleUserDetailsModal(false)">
            <i class="ti ti-x"></i>
        </button>
        
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px; border-bottom: 1px solid var(--color-border-tertiary); padding-bottom: 15px;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: #E6F1FB; color: #185FA5; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                <i class="ti ti-trophy"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--navy);">Competition Details</h3>
                <p style="margin: 3px 0 0; font-size: 12px; color: var(--color-text-secondary);">Registered competition specifications</p>
            </div>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13.5px;">
            <div style="text-align: center; margin-bottom: 10px;">
                <img id="user-det-img" src="" alt="Poster" style="max-height: 230px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: contain; max-width: 100%;">
            </div>
            <div>
                <strong>UID:</strong> <span id="user-det-uid"></span>
            </div>
            <div>
                <strong>Title:</strong> <span id="user-det-title"></span>
            </div>
            <div>
                <strong>Format:</strong> <span id="user-det-format"></span>
            </div>
            <div>
                <strong>Target Audience:</strong> <span id="user-det-target"></span>
            </div>
            <div>
                <strong>Dates:</strong> <span id="user-det-dates"></span>
            </div>
            <div>
                <strong>Registration Fee:</strong> <span id="user-det-fee"></span>
            </div>
            <div>
                <strong>Category:</strong> <span id="user-det-category"></span>
            </div>
            <div>
                <strong>Registration Link:</strong> <a id="user-det-link" href="" target="_blank" style="color: #185FA5; text-decoration: underline; font-weight: 600;">Open Link</a>
            </div>
            <div id="user-det-public-link-row" style="display: none;">
                <strong>Public Listing:</strong> <a id="user-det-public-link" href="" target="_blank" style="color: #185FA5; text-decoration: underline; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i class="ti ti-world"></i> View Published Page</a>
            </div>
            <div>
                <strong>Description:</strong>
                <p id="user-det-desc" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; white-space: pre-wrap; margin-top: 4px; color: #4a6070; line-height: 1.6; font-size: 13px;"></p>
            </div>
        </div>
        
        <div class="form-actions" style="margin-top: 24px; display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="toggleUserDetailsModal(false)" style="padding: 8px 24px;">Close</button>
        </div>
    </div>
</div>

<script>
function toggleUserDetailsModal(open) {
    const modal = document.getElementById('user-details-modal');
    if (!modal) return;
    if (open) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    } else {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
}

function closeUserDetailsModal(event) {
    if (event.target === document.getElementById('user-details-modal')) {
        toggleUserDetailsModal(false);
    }
}

function viewUserSubmissionDetails(data) {
    document.getElementById('user-det-img').src = "../assets/images/" + (data.image || "logo_putih.svg");
    document.getElementById('user-det-uid').textContent = data.uid || '';
    document.getElementById('user-det-title').textContent = data.title;
    document.getElementById('user-det-format').textContent = data.format;
    document.getElementById('user-det-target').textContent = data.target_audience;
    
    // Format date_range (e.g., "2026-04-26,2026-05-21")
    let dates = data.date_range;
    if (dates && dates.includes(',')) {
        let parts = dates.split(',');
        dates = parts[0] + ' to ' + parts[1];
    }
    document.getElementById('user-det-dates').textContent = dates;
    
    document.getElementById('user-det-fee').textContent = data.registration_fee == 0 ? 'Free' : 'Rp ' + parseInt(data.registration_fee).toLocaleString('id-ID');
    document.getElementById('user-det-category').textContent = data.category || 'Tidak dikategorikan';
    document.getElementById('user-det-link').href = data.registration_link;
    document.getElementById('user-det-desc').textContent = data.description;
    
    // Public listing link row visibility
    const publicLinkRow = document.getElementById('user-det-public-link-row');
    const publicLink = document.getElementById('user-det-public-link');
    const subStatus = (data.submission_status || '').toLowerCase();
    if (subStatus === 'published') {
        publicLink.href = "competition-details.php?id=" + encodeURIComponent(data.id);
        publicLinkRow.style.display = 'block';
    } else {
        publicLinkRow.style.display = 'none';
    }
    
    toggleUserDetailsModal(true);
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('user-details-modal');
    if (modal) {
        document.body.appendChild(modal);
    }
});
</script>
