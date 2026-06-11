<?php
/**
 * admin/review-submissions.php
 *
 * Included in admin/dashboard.php. Displays pending user competition submissions
 * for admin approval or rejection.
 */

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($_SESSION['user_role'] ?? '') !== 'admin') {
    exit('Access denied.');
}

// Active synchronization with Xendit for recent unpaid submissions to handle hosting webhook blocks
require_once __DIR__ . '/../helpers/xendit.php';
require_once __DIR__ . '/../helpers/telegram-notification.php';

$sync_query = "
    SELECT * FROM competitions 
    WHERE payment_status = 'unpaid' 
      AND xendit_invoice_id IS NOT NULL 
      AND xendit_invoice_id <> '' 
      AND created_at >= NOW() - INTERVAL 2 DAY
";
$sync_res = mysqli_query($koneksi, $sync_query);
if ($sync_res) {
    while ($sync_row = mysqli_fetch_assoc($sync_res)) {
        $invoice = getXenditInvoice($sync_row['xendit_invoice_id']);
        if ($invoice && isset($invoice['status'])) {
            $inv_status = strtoupper($invoice['status']);
            if ($inv_status === 'PAID' || $inv_status === 'SETTLED') {
                // Update DB status to Paid and Pending Review
                $up_sql = "UPDATE competitions SET payment_status = 'paid', submission_status = 'pending_review', paid_at = NOW() WHERE id = ?";
                $up_stmt = mysqli_prepare($koneksi, $up_sql);
                if ($up_stmt) {
                    mysqli_stmt_bind_param($up_stmt, "i", $sync_row['id']);
                    mysqli_stmt_execute($up_stmt);
                    mysqli_stmt_close($up_stmt);
                }

                // Send Telegram Notification to Submitter
                $chat_id = '';
                $u_stmt = mysqli_prepare($koneksi, "SELECT telegram_chat_id FROM users WHERE id = ? LIMIT 1");
                if ($u_stmt) {
                    mysqli_stmt_bind_param($u_stmt, "i", $sync_row['user_id']);
                    mysqli_stmt_execute($u_stmt);
                    $u_res = mysqli_stmt_get_result($u_stmt);
                    if ($u_row = mysqli_fetch_assoc($u_res)) {
                        $chat_id = $u_row['telegram_chat_id'];
                    }
                    mysqli_stmt_close($u_stmt);
                }
                notifyPaymentSuccess($chat_id, $sync_row['title'], $sync_row['xendit_invoice_id']);
            } elseif ($inv_status === 'EXPIRED') {
                // Update DB status to Expired
                $up_sql = "UPDATE competitions SET payment_status = 'expired', submission_status = 'expired' WHERE id = ?";
                $up_stmt = mysqli_prepare($koneksi, $up_sql);
                if ($up_stmt) {
                    mysqli_stmt_bind_param($up_stmt, "i", $sync_row['id']);
                    mysqli_stmt_execute($up_stmt);
                    mysqli_stmt_close($up_stmt);
                }
            }
        }
    }
}

// Fetch competitions in 'pending_review' state (including categories joined from normalized tables)
$review_query = "
    SELECT c.*, u.username as submitter_name, u.email as submitter_email,
           (SELECT GROUP_CONCAT(cat.name SEPARATOR ', ') 
            FROM competition_categories cc 
            JOIN categories cat ON cc.category_id = cat.id 
            WHERE cc.competition_id = c.id) as category
    FROM competitions c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.submission_status = 'pending_review' 
    ORDER BY c.id ASC
";
$review_res = mysqli_query($koneksi, $review_query);
$review_list = [];
if ($review_res) {
    while ($row = mysqli_fetch_assoc($review_res)) {
        $review_list[] = $row;
    }
}
$review_count = count($review_list);
?>

<div class="tab-panel <?= $active_tab === 'review-lomba' ? 'active' : '' ?>" id="tab-review-lomba">
    
    <div class="section-head" style="border-bottom: none;">
        <div class="section-head-left">
            <div class="section-head-icon" style="background: #FFF8E1; color: #FF8F00;">
                <i class="ti ti-checklist" style="font-size:18px"></i>
            </div>
            <div>
                <span class="section-title">Review Submissions</span>
                <div class="section-sub">Approve or reject paid user competition listings</div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $active_tab === 'review-lomba'): ?>
        <div class="alert alert-<?= htmlspecialchars($_GET['msg_type'] ?? 'info') ?>" style="margin: 16px 24px;">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="kelola-toolbar" style="border-top: 1px solid var(--color-border-tertiary); padding: 16px 24px; background: #fafcff;">
        <span style="font-size: 13.5px; font-weight: 500; color: #64748b;"><?= $review_count ?> submissions pending review</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Competition</th>
                    <th>Submitter</th>
                    <th>Paid At</th>
                    <th style="width: 220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($review_list as $row): ?>
                    <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--navy); font-size: 13.5px;"><?= htmlspecialchars($row['title']) ?></div>
                                <div style="font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 3px;">
                                    UID: <?= htmlspecialchars($row['uid']) ?>
                                </div>
                            </td>
                        <td>
                            <div style="font-weight: 600; color: #334155; font-size: 13px;"><?= htmlspecialchars($row['submitter_name']) ?></div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($row['submitter_email']) ?></div>
                        </td>
                        <td style="font-size: 13px; color: #475569; font-weight: 500;">
                            <?= !empty($row['paid_at']) ? date('d M Y H:i', strtotime($row['paid_at'])) : '-' ?>
                        </td>
                        <td>
                            <div class="actions" style="display: flex; gap: 8px;">
                                <!-- Details Trigger -->
                                <button type="button" class="btn btn-secondary btn-sm" 
                                        onclick="viewSubmissionDetails(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"
                                        style="font-size: 11.5px; padding: 6px 12px; border-radius: 12px;">
                                    <i class="ti ti-eye" style="font-size: 12px;"></i> View
                                </button>
                                
                                <!-- Approve form -->
                                <form method="POST" action="../controllers/admin-review.php" style="display: inline;" onsubmit="return confirm('Approve this competition and publish to live website?')">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit" class="btn btn-primary btn-sm" style="font-size: 11.5px; padding: 6px 12px; border-radius: 12px; background: #2E7D32;">
                                        <i class="ti ti-check" style="font-size: 12px;"></i> Approve
                                    </button>
                                </form>

                                <!-- Reject trigger -->
                                <button type="button" class="btn btn-danger btn-sm" 
                                        onclick="openRejectModal(<?= (int)$row['id'] ?>, '<?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?>')"
                                        style="font-size: 11.5px; padding: 6px 12px; border-radius: 12px;">
                                    <i class="ti ti-x" style="font-size: 12px;"></i> Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($review_count === 0): ?>
                    <tr>
                        <td colspan="4" class="empty-state">
                            <i class="ti ti-checklist-off" style="font-size:40px;opacity:0.4;display:block;margin-bottom:10px;"></i>
                            No submissions pending review.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="diri-footer" style="padding: 14px 24px; background: #fafcff; border-top: 1px solid var(--color-border-tertiary);">
        <span class="diri-footer-note">
            <i class="ti ti-info-circle" style="font-size:14px"></i>
            Approved listings are published instantly. Rejected listings trigger automated refunds on Xendit.
        </span>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: REJECT SUBMISSION
     ══════════════════════════════════════════════════ -->
<div class="overlay" id="reject-modal" style="z-index: 100001;">
    <div class="modal-box" style="max-width: 480px; padding: 24px;">
        <button type="button" class="modal-close" onclick="closeRejectModal()"><i class="ti ti-x"></i></button>
        <h3 style="font-size: 16px; font-weight: 800; color: #c0392b; margin-bottom: 12px;">Reject Submission</h3>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 18px;" id="reject-lomba-title"></p>
        
        <form method="POST" action="../controllers/admin-review.php">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="id" id="reject-comp-id" value="">
            
            <div class="fg" style="margin-bottom: 18px;">
                <label class="fg-label" style="font-weight: 700;">Rejection Reason / Note <span class="fg-req">*</span></label>
                <textarea name="note" required placeholder="Explain why the competition was rejected (this will be sent to the user via Telegram)..." class="fg-textarea" rows="4"></textarea>
            </div>
            
            <div class="form-actions" style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-danger" style="background: #d32f2f;">Confirm Reject & Refund</button>
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: VIEW DETAILS
     ══════════════════════════════════════════════════ -->
<div class="overlay" id="details-modal" style="z-index: 100001;">
    <div class="modal-box" style="max-width: 600px; padding: 24px; overflow-y: auto; max-height: 85vh;">
        <button type="button" class="modal-close" onclick="closeDetailsModal()"><i class="ti ti-x"></i></button>
        <h3 style="font-size: 16px; font-weight: 800; color: var(--navy); margin-bottom: 16px;">Submission Details</h3>
        
        <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13.5px;">
            <div style="text-align: center; margin-bottom: 10px;">
                <img id="det-img" src="" alt="Poster" style="max-height: 250px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: contain;">
            </div>
            <div>
                <strong>UID:</strong> <span id="det-uid"></span>
            </div>
            <div>
                <strong>Title:</strong> <span id="det-title"></span>
            </div>
            <div>
                <strong>Format:</strong> <span id="det-format"></span>
            </div>
            <div>
                <strong>Target Audience:</strong> <span id="det-target"></span>
            </div>
            <div>
                <strong>Dates:</strong> <span id="det-dates"></span>
            </div>
            <div>
                <strong>Registration Fee:</strong> <span id="det-fee"></span>
            </div>
            <div>
                <strong>Category:</strong> <span id="det-category"></span>
            </div>
            <div>
                <strong>Registration Link:</strong> <a id="det-link" href="" target="_blank" style="color: #185FA5; text-decoration: underline; font-weight: 600;">Open Link</a>
            </div>
            <div>
                <strong>Description:</strong>
                <p id="det-desc" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; white-space: pre-wrap; margin-top: 4px; color: #4a6070; line-height: 1.6; font-size: 13px;"></p>
            </div>
        </div>
        
        <div class="form-actions" style="margin-top: 24px; display: flex; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeDetailsModal()">Close</button>
        </div>
    </div>
</div>

<script>
function openRejectModal(id, title) {
    document.getElementById('reject-comp-id').value = id;
    document.getElementById('reject-lomba-title').innerHTML = "Competition: <strong>" + title + "</strong>";
    document.getElementById('reject-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeRejectModal() {
    document.getElementById('reject-modal').classList.remove('open');
    if (!document.querySelector('.overlay.open')) {
        document.body.style.overflow = '';
    }
}

function viewSubmissionDetails(data) {
    document.getElementById('det-img').src = "../assets/images/" + (data.image || "logo_putih.svg");
    document.getElementById('det-uid').textContent = data.uid || '';
    document.getElementById('det-title').textContent = data.title;
    document.getElementById('det-format').textContent = data.format;
    document.getElementById('det-target').textContent = data.target_audience;
    
    // Format date_range (e.g., "2026-04-26,2026-05-21")
    let dates = data.date_range;
    if (dates && dates.includes(',')) {
        let parts = dates.split(',');
        dates = parts[0] + ' to ' + parts[1];
    }
    document.getElementById('det-dates').textContent = dates;
    
    document.getElementById('det-fee').textContent = data.registration_fee == 0 ? 'Free' : 'Rp ' + parseInt(data.registration_fee).toLocaleString('id-ID');
    document.getElementById('det-category').textContent = data.category || 'Tidak dikategorikan';
    document.getElementById('det-link').href = data.registration_link;
    document.getElementById('det-desc').textContent = data.description;
    
    document.getElementById('details-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDetailsModal() {
    document.getElementById('details-modal').classList.remove('open');
    if (!document.querySelector('.overlay.open')) {
        document.body.style.overflow = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const detailModal = document.getElementById('details-modal');
    if (detailModal) {
        document.body.appendChild(detailModal);
    }
    const rejectModal = document.getElementById('reject-modal');
    if (rejectModal) {
        document.body.appendChild(rejectModal);
    }
});
</script>
