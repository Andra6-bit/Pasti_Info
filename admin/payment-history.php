<?php
/**
 * admin/payment-history.php
 *
 * Included in admin/dashboard.php. Displays user listing transaction history,
 * including invoices, payment settling dates, and refund processing status.
 */

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($_SESSION['user_role'] ?? '') !== 'admin') {
    // 1-line reason: Replace username-based admin check with session role verification for improved security.
    exit('Access denied.');
}

// Fetch payment transactions (only for regular user submissions where payment occurred)
$history_query = "
    SELECT c.*, u.username as submitter_name, u.email as submitter_email 
    FROM competitions c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.user_id IS NOT NULL 
      AND (c.payment_status = 'paid' OR c.payment_status = 'refunded') 
    ORDER BY c.id DESC
";
$history_res = mysqli_query($koneksi, $history_query);
$history_list = [];
if ($history_res) {
    while ($row = mysqli_fetch_assoc($history_res)) {
        $history_list[] = $row;
    }
}
$history_count = count($history_list);

// Get configured submission fee for display
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

<div class="tab-panel <?= $active_tab === 'riwayat-keuangan' ? 'active' : '' ?>" id="tab-riwayat-keuangan">
    
    <div class="section-head" style="border-bottom: none;">
        <div class="section-head-left">
            <div class="section-head-icon" style="background: #E8F5E9; color: #2E7D32;">
                <i class="ti ti-history" style="font-size:18px"></i>
            </div>
            <div>
                <span class="section-title">Payment & Refund History</span>
                <div class="section-sub">Track financial transactions, settled invoices, and refunds</div>
            </div>
        </div>
    </div>

    <div class="kelola-toolbar" style="border-top: 1px solid var(--color-border-tertiary); padding: 16px 24px; background: #fafcff;">
        <span style="font-size: 13.5px; font-weight: 500; color: #64748b;"><?= $history_count ?> transactions recorded</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Competition</th>
                    <th>Submitter</th>
                    <th>Amount</th>
                    <th>Paid At</th>
                    <th>Refund Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history_list as $row): ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 12px; font-weight: 600; color: #1e293b;">
                            <?= htmlspecialchars($row['xendit_invoice_id'] ?? '-') ?>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #1a2a3a; font-size: 13.5px;"><?= htmlspecialchars($row['title']) ?></div>
                            <div style="font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 2px;">
                                UID: <?= htmlspecialchars($row['uid']) ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #334155; font-size: 13px;"><?= htmlspecialchars($row['submitter_name']) ?></div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($row['submitter_email']) ?></div>
                        </td>
                        <td style="font-size: 13px; color: #1e293b; font-weight: 700;">
                            Rp <?= number_format($submission_fee, 0, ',', '.') ?>
                        </td>
                        <td style="font-size: 12.5px; color: #475569; font-weight: 500;">
                            <?= !empty($row['paid_at']) ? date('d M Y H:i', strtotime($row['paid_at'])) : '-' ?>
                        </td>
                        <td>
                            <?php 
                            $pay_status = strtolower($row['payment_status'] ?? '');
                            $ref_status = strtolower($row['refund_status'] ?? '');
                            
                            if ($pay_status === 'refunded' || $ref_status === 'succeeded'):
                            ?>
                                <span class="field-pill" style="background: #ECEFF1; color: #37474F; font-size: 11px; font-weight: 700;">Refunded</span>
                                <?php if (!empty($row['refunded_at'])): ?>
                                    <div style="font-size: 10px; color: #78909c; margin-top: 4px;">
                                        <?= date('d M Y H:i', strtotime($row['refunded_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($ref_status === 'pending'): ?>
                                <span class="field-pill pill-yellow" style="background: #FFF8E1; color: #F57F17; font-size: 11px;">Refund Pending</span>
                            <?php elseif ($ref_status === 'failed'): ?>
                                <span class="field-pill pill-gray" style="background: #FFEBEE; color: #C62828; font-size: 11px;">Refund Failed</span>
                            <?php else: ?>
                                <span class="field-pill pill-blue" style="background: #E8F5E9; color: #2E7D32; font-size: 11px;">Settle / No Refund</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($history_count === 0): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="ti ti-history-toggle" style="font-size:40px;opacity:0.4;display:block;margin-bottom:10px;"></i>
                            No transaction history available.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="diri-footer" style="padding: 14px 24px; background: #fafcff; border-top: 1px solid var(--color-border-tertiary);">
        <span class="diri-footer-note">
            <i class="ti ti-shield" style="font-size:14px"></i>
            Transactions are processed securely through Xendit payment links.
        </span>
    </div>
</div>
