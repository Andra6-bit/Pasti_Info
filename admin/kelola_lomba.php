<?php
// Tangkap pesan dari redirect lomba_action.php
$msg      = $_GET['msg'] ?? '';
$msg_type = $_GET['msg_type'] ?? '';

// Ambil data kompetisi
$all   = mysqli_query($koneksi, "SELECT * FROM competitions ORDER BY id DESC");
$total = mysqli_num_rows($all);
?>

<div class="tab-panel <?= $active_tab === 'kelola' ? 'active' : '' ?>" id="tab-kelola">

    <?php if ($msg && $active_tab === 'kelola'): ?>
    <div class="alert alert-<?= htmlspecialchars($msg_type) ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="kelola-toolbar">
        <span><?= $total ?> kompetisi terdaftar</span>
        <button class="btn btn-primary btn-sm" onclick="openAdd()">+ Tambah Kompetisi</button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Pelaksanaan</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $c = 0;
            while ($row = mysqli_fetch_assoc($all)): $c++;
            ?>
            <tr>
                <td>
                    <img src="../Assets/images/<?= htmlspecialchars($row['foto'] ?? $row['image'] ?? 'default.jpg') ?>" class="td-img" onerror="this.src='../Assets/images/logo.jpeg'" alt="">
                </td>
                <td style="font-weight:600;"><?= htmlspecialchars($row['title']) ?></td>
                <td>
                    <span class="badge badge-<?= strtolower(str_replace(' ', '-', $row['category'])) ?>" style="background:var(--border); color:var(--navy);">
                        <?= htmlspecialchars($row['category']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($row['pelaksanaan']) ?></td>
                
                <?php
                // Trik menampilkan format tanggal
                $dates = explode(',', $row['date_range']);
                if (count($dates) === 2) { // Jika formatnya pakai koma (Format Baru)
                    $start_fmt = $dates[0] ? date('d M Y', strtotime($dates[0])) : '?';
                    $end_fmt   = $dates[1] ? date('d M Y', strtotime($dates[1])) : '?';
                    $tampil_tanggal = $start_fmt . ' - ' . $end_fmt;
                } else { // Jika masih menggunakan data format lama
                    $tampil_tanggal = htmlspecialchars($row['date_range']);
                }
                ?>
                <td style="font-size:13px;white-space:nowrap;"><?= $tampil_tanggal ?></td>
                
                <td>
                    <div class="actions">
                        <button class="btn btn-secondary btn-sm" onclick='openEdit(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)'>Edit</button>
                        <a href="lomba_action.php?delete=<?= $row['id'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Yakin hapus kompetisi ini?')">Hapus</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($c === 0): ?>
            <tr>
                <td colspan="6" class="empty-state">
                    <i class="ti ti-trophy-off" style="font-size:40px;opacity:0.4;display:block;margin-bottom:10px;"></i>
                    Belum ada kompetisi. Klik "+ Tambah Kompetisi" untuk mulai.
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="overlay" id="overlay" onclick="closeModal(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="closeOverlay()">✕</button>
        <h3 id="modal-title">Tambah Kompetisi</h3>

        <form method="POST" enctype="multipart/form-data" action="lomba_action.php">
            <input type="hidden" name="action"    id="f-action"  value="add">
            <input type="hidden" name="id"        id="f-id"      value="">
            <input type="hidden" name="old_image" id="f-old-img" value="default.jpg">

            <div class="form-grid">
                <div class="form-group full">
                    <label>Judul Kompetisi *</label>
                    <input type="text" name="title" id="f-title" placeholder="Nama kompetisi..." required>
                </div>
                
                <div class="form-group">
                    <label>Pelaksanaan *</label>
                    <select name="pelaksanaan" id="f-pelaksanaan" required>
                        <option value="Online">Online</option>
                        <option value="Offline">Offline</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Target Peserta *</label>
                    <select name="target_peserta" id="f-target-peserta" required>
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="Mahasiswa">Mahasiswa</option>
                        <option value="Umum">Umum</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Biaya *</label>
                    <input type="number" name="biaya" id="f-biaya" min="0" placeholder="0" required>
                </div>

                <div class="form-group">
                    <label>Kategori *</label>
                    <input type="text" name="category" id="f-category" placeholder="Cth: UI/UX Design, Hacking..." required>
                </div>

                <div class="form-group">
                    <label>Tanggal Mulai *</label>
                    <input type="date" name="start_date" id="f-start-date" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal Selesai *</label>
                    <input type="date" name="end_date" id="f-end-date" required>
                </div>

                <div class="form-group full">
                    <label>Gambar <span style="font-weight:400;color:#5a7a99;">(opsional)</span></label>
                    <div class="file-upload-area">
                        <input type="file" name="image" id="f-image" accept="image/*" onchange="previewImg(this)">
                        <div class="upload-icon">🖼</div>
                        <p><strong>Klik untuk pilih gambar</strong></p>
                        <p style="font-size:12px;margin-top:3px;">JPG, PNG, WebP — maks 3MB</p>
                        <div class="file-preview" id="previewBox">
                            <img id="previewImg" src="" alt="Preview">
                        </div>
                    </div>
                </div>
                
                <div class="form-group full">
                    <label>Deskripsi *</label>
                    <textarea name="description" id="f-desc" placeholder="Deskripsi singkat kompetisi..." required></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="f-submit">Tambah</button>
                <button type="button" class="btn btn-secondary" onclick="closeOverlay()">Batal</button>
            </div>
        </form>
    </div>
</div>