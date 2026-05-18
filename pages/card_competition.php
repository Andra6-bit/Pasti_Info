<div class="card" style="padding: 12px; display: flex; flex-direction: column; gap: 10px;">

    <img src="../Assets/images/<?php echo htmlspecialchars($row['image']); ?>"
         alt="<?php echo htmlspecialchars($row['title']); ?>"
         onerror="this.src='../Assets/images/logo_putih.svg'"
         style="width: 100%; aspect-ratio: 3/4; object-fit: cover; border-radius: 12px; display: block;">

    <div style="display: flex; flex-direction: column; gap: 8px; padding: 0 4px;">

        <h3 style="font-size: 16px; font-weight: 800; color: #0d1f35; text-transform: uppercase; margin: 0; text-align: center;">
            <?php echo htmlspecialchars($row['title']); ?>
        </h3>

        <div style="display: flex; flex-wrap: wrap; gap: 6px; justify-content: center;">
            <span style="font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; background: #e8f0fe; color: #185fa5;">
                <?php echo htmlspecialchars($row['target'] ?? 'Umum'); ?>
            </span>
            <span style="font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; background: #e6f4ea; color: #1e7e34;">
                <?php echo htmlspecialchars($row['location']); ?>
            </span>
            <span style="font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; background: #e0f0ff; color: #0d4a7a;">
                <?php echo htmlspecialchars($row['price'] ?? 'Gratis'); ?>
            </span>
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0;">

        <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center;">
            <span></span>
            <span style="font-size: 12px; color: #4a6070;">
                <?php echo htmlspecialchars($row['date_range']); ?>
            </span>
            <div style="display: flex; justify-content: flex-end;">
                <button onclick="openDetail(
                    '<?php echo addslashes(htmlspecialchars($row['title'])); ?>',
                    '../Assets/images/<?php echo addslashes(htmlspecialchars($row['image'])); ?>',
                    '<?php echo addslashes(htmlspecialchars($row['location'])); ?>',
                    '<?php echo addslashes(htmlspecialchars($row['date_range'])); ?>',
                    '<?php echo addslashes(htmlspecialchars($row['description'])); ?>'
                )" style="background: none; border: none; cursor: pointer; font-size: 20px; color: #aaa; padding: 4px; transition: color 0.2s; line-height: 1;"
                   onmouseover="this.style.color='#e53e3e'"
                   onmouseout="this.style.color='#aaa'">
                    ♡
                </button>
            </div>
        </div>

    </div>
</div>