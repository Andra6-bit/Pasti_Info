<?php
$card_format = htmlspecialchars(!empty($row['format']) ? $row['format'] : 'Online');
$card_target = htmlspecialchars(!empty($row['target_audience']) ? $row['target_audience'] : 'General');
$card_fee = isset($row['registration_fee']) && is_numeric($row['registration_fee']) && $row['registration_fee'] > 0
    ? 'Rp ' . number_format($row['registration_fee'], 0, ',', '.')
    : 'Free';
$is_saved = in_array((int)$row['id'], $saved_competitions ?? [], true);
?>
<div class="card" onclick="window.location.href='competition-details.php?id=<?php echo urlencode($row['id'] ?? $row['title']); ?>'">

    <img class="card-image"
         src="../assets/images/<?php echo htmlspecialchars(!empty($row['image']) ? $row['image'] : 'logo_putih.svg'); ?>"
         alt="<?php echo htmlspecialchars($row['title']); ?>"
         onerror="this.src='../assets/images/logo_putih.svg'">

    <div class="card-body">

        <h3 class="card-title">
            <?php echo htmlspecialchars($row['title']); ?>
        </h3>

        <div class="card-bottom">

            <div class="card-badges">
                <span class="badge badge-all"><?php echo $card_target; ?></span>
                <span class="badge badge-online"><?php echo $card_format; ?></span>
                <span class="badge badge-free"><?php echo $card_fee; ?></span>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-lighter); margin: 0;">

            <div class="card-footer">
                <span></span>
                <span class="card-date">
                    <?php
                    $dr_card = $row['date_range'] ?? '';
                    $tampil_tanggal = '';
                    if (str_contains($dr_card, ',')) {
                        [$dc1, $dc2] = explode(',', $dr_card, 2);
                        $ts1 = strtotime(trim($dc1));
                        $ts2 = strtotime(trim($dc2));
                        if ($ts1 && $ts2) {
                            $df1 = date('d M Y', $ts1);
                            $df2 = date('d M Y', $ts2);
                            $tampil_tanggal = ($df1 === $df2) ? $df1 : $df1 . ' — ' . $df2;
                        } else {
                            $tampil_tanggal = $dr_card;
                        }
                    } elseif (str_contains($dr_card, '/')) {
                        $dp = explode('-', $dr_card);
                        if (count($dp) === 2) {
                            $ts1 = strtotime(str_replace('/', '-', trim($dp[0])));
                            $ts2 = strtotime(str_replace('/', '-', trim($dp[1])));
                            if ($ts1 && $ts2) {
                                $df1 = date('d M Y', $ts1);
                                $df2 = date('d M Y', $ts2);
                                $tampil_tanggal = ($df1 === $df2) ? $df1 : $df1 . ' — ' . $df2;
                            } else {
                                $tampil_tanggal = $dr_card;
                            }
                        } else {
                            $tampil_tanggal = $dr_card;
                        }
                    } else {
                        $tampil_tanggal = $dr_card;
                    }
                    echo htmlspecialchars($tampil_tanggal);
                    ?>
                </span>
                <div class="card-footer-actions">
                    <button type="button" class="like-btn<?php echo $is_saved ? ' saved' : ''; ?>"
                            onclick="event.stopPropagation(); toggleBookmark(<?php echo intval($row['id']); ?>, this);">
                        <?php echo $is_saved ? '♥' : '♡'; ?>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
