<?php
session_start();
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../pages/login.php");
    exit();
}
include "../config/koneksi.php";

$msg      = '';
$msg_type = '';

// ── DELETE ──────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Ambil nama gambar sebelum hapus
    $stmt = mysqli_prepare($koneksi, "SELECT image FROM competitions WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $del_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $stmt2 = mysqli_prepare($koneksi, "DELETE FROM competitions WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);

    if (mysqli_stmt_execute($stmt2)) {
        // Hapus file gambar jika bukan default
        if ($del_row && $del_row['image'] !== 'default.jpg') {
            $img_path = "../Assets/images/" . $del_row['image'];
            if (file_exists($img_path)) unlink($img_path);
        }
        $msg      = "Kompetisi berhasil dihapus.";
        $msg_type = "success";
    } else {
        $msg      = "Gagal menghapus kompetisi.";
        $msg_type = "error";
    }
}

// ── EDIT (GET data for modal) ────────────────────────────────
$edit_row = null;
if (isset($_GET['edit'])) {
    $id   = (int)$_GET['edit'];
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM competitions WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $edit_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// ── TAMBAH / UPDATE ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? 'add';
    $post_id     = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $date_range  = trim($_POST['date_range'] ?? '');
    $category    = $_POST['category'] ?? 'All';
    $description = trim($_POST['description'] ?? '');

    $allowed_categories = ['Design','Programming','Hacking','All'];
    if (!in_array($category, $allowed_categories)) $category = 'All';

    // Upload gambar
    $image_name = $_POST['old_image'] ?? 'default.jpg';

    if (!empty($_FILES['image']['name'])) {
        $allowed_ext = ['jpg','jpeg','png','gif','webp'];
        $ext         = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            $msg = "Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.";
            $msg_type = "error";
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $msg = "Ukuran gambar maksimal 3MB.";
            $msg_type = "error";
        } else {
            $image_name = uniqid('comp_') . '.' . $ext;
            $dest       = "../Assets/images/" . $image_name;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $msg      = "Gagal mengupload gambar.";
                $msg_type = "error";
                $image_name = $_POST['old_image'] ?? 'default.jpg';
            }
        }
    }

    if (empty($msg)) {
        if ($action === 'add') {
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO competitions (title, image, location, date_range, category, description)
                 VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssss",
                $title, $image_name, $location, $date_range, $category, $description);

            if (mysqli_stmt_execute($stmt)) {
                $msg      = "Kompetisi berhasil ditambahkan!";
                $msg_type = "success";
            } else {
                $msg = "Gagal menyimpan data."; $msg_type = "error";
            }
        } else {
            // Hapus gambar lama jika diganti
            $old_img = $_POST['old_image'] ?? 'default.jpg';
            if ($image_name !== $old_img && $old_img !== 'default.jpg') {
                $old_path = "../Assets/images/" . $old_img;
                if (file_exists($old_path)) unlink($old_path);
            }

            $stmt = mysqli_prepare($koneksi,
                "UPDATE competitions SET title=?, image=?, location=?, date_range=?, category=?, description=?
                 WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssssi",
                $title, $image_name, $location, $date_range, $category, $description, $post_id);

            if (mysqli_stmt_execute($stmt)) {
                $msg      = "Kompetisi berhasil diperbarui!";
                $msg_type = "success";
            } else {
                $msg = "Gagal memperbarui data."; $msg_type = "error";
            }
        }
    }
}

// ── Ambil semua kompetisi ─────────────────────────────────────
$all = mysqli_query($koneksi, "SELECT * FROM competitions ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kompetisi - P Info</title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="main">
    <div class="topbar">
        <div>
            <h1>Kelola Kompetisi</h1>
            <p>Tambah, edit, dan hapus data kompetisi</p>
        </div>
        <button class="btn btn-primary" onclick="openAdd()">+ Tambah Baru</button>
    </div>

    <div class="content">
        <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- TABEL -->
        <div class="panel">
            <div class="panel-header">
                <h2>Daftar Kompetisi</h2>
                <span style="font-size:13px;color:var(--muted);"><?php echo mysqli_num_rows($all); ?> kompetisi</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $c = 0;
                    while ($row = mysqli_fetch_assoc($all)):
                        $c++;
                    ?>
                    <tr>
                        <td>
                            <img src="../Assets/images/<?php echo htmlspecialchars($row['image']); ?>"
                                 class="td-img"
                                 onerror="this.src='../Assets/images/logo.jpeg'"
                                 alt="">
                        </td>
                        <td style="font-weight:600;max-width:200px;"><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><span class="badge badge-<?php echo strtolower(htmlspecialchars($row['category'])); ?>"><?php echo htmlspecialchars($row['category']); ?></span></td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <td style="white-space:nowrap;font-size:13px;"><?php echo htmlspecialchars($row['date_range']); ?></td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-secondary btn-sm"
                                    onclick="openEdit(
                                        <?php echo $row['id']; ?>,
                                        '<?php echo addslashes(htmlspecialchars($row['title'])); ?>',
                                        '<?php echo addslashes(htmlspecialchars($row['image'])); ?>',
                                        '<?php echo addslashes(htmlspecialchars($row['location'])); ?>',
                                        '<?php echo addslashes(htmlspecialchars($row['date_range'])); ?>',
                                        '<?php echo addslashes(htmlspecialchars($row['category'])); ?>',
                                        '<?php echo addslashes(htmlspecialchars($row['description'])); ?>'
                                    )">Edit</button>
                                <a href="competitions.php?delete=<?php echo $row['id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Yakin hapus kompetisi ini?')">Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($c === 0): ?>
                    <tr><td colspan="6" class="empty-state">Belum ada kompetisi. Klik "+ Tambah Baru" untuk mulai.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH / EDIT -->
<div class="overlay" id="overlay" onclick="closeModal(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="closeOverlay()">✕</button>
        <h3 id="modal-title">Tambah Kompetisi</h3>

        <form method="POST" enctype="multipart/form-data" id="compForm">
            <input type="hidden" name="action"    id="f-action"   value="add">
            <input type="hidden" name="id"        id="f-id"       value="">
            <input type="hidden" name="old_image" id="f-old-img"  value="default.jpg">

            <div class="form-grid">
                <div class="form-group full">
                    <label>Judul Kompetisi *</label>
                    <input type="text" name="title" id="f-title" placeholder="Nama kompetisi..." required>
                </div>
                <div class="form-group">
                    <label>Lokasi *</label>
                    <input type="text" name="location" id="f-location" placeholder="Online / Jakarta / ..." required>
                </div>
                <div class="form-group">
                    <label>Tanggal (Rentang) *</label>
                    <input type="text" name="date_range" id="f-date" placeholder="1 Jan - 28 Feb 2026" required>
                </div>
                <div class="form-group">
                    <label>Kategori *</label>
                    <select name="category" id="f-category" required>
                        <option value="Design">Design</option>
                        <option value="Programming">Programming</option>
                        <option value="Hacking">Hacking</option>
                        <option value="All">All</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gambar</label>
                    <div class="file-upload-area" id="uploadArea">
                        <input type="file" name="image" id="f-image" accept="image/*" onchange="previewImage(this)">
                        <div class="upload-icon">🖼</div>
                        <p><strong>Klik untuk pilih</strong> atau drag & drop</p>
                        <p style="font-size:12px;margin-top:4px;">JPG, PNG, WebP — maks 3MB</p>
                        <div class="file-preview" id="previewBox">
                            <img id="previewImg" src="" alt="Preview">
                        </div>
                    </div>
                </div>
                <div class="form-group full">
                    <label>Deskripsi *</label>
                    <textarea name="description" id="f-description" placeholder="Deskripsi kompetisi..." required></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submit-btn">Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closeOverlay()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAdd() {
    document.getElementById('modal-title').textContent = 'Tambah Kompetisi';
    document.getElementById('f-action').value   = 'add';
    document.getElementById('f-id').value       = '';
    document.getElementById('f-old-img').value  = 'default.jpg';
    document.getElementById('f-title').value    = '';
    document.getElementById('f-location').value = '';
    document.getElementById('f-date').value     = '';
    document.getElementById('f-category').value = 'Design';
    document.getElementById('f-description').value = '';
    document.getElementById('previewBox').style.display = 'none';
    document.getElementById('submit-btn').textContent = 'Tambah';
    document.getElementById('overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function openEdit(id, title, image, location, date, category, description) {
    document.getElementById('modal-title').textContent  = 'Edit Kompetisi';
    document.getElementById('f-action').value           = 'edit';
    document.getElementById('f-id').value               = id;
    document.getElementById('f-old-img').value          = image;
    document.getElementById('f-title').value            = title;
    document.getElementById('f-location').value         = location;
    document.getElementById('f-date').value             = date;
    document.getElementById('f-category').value         = category;
    document.getElementById('f-description').value      = description;
    document.getElementById('submit-btn').textContent   = 'Simpan Perubahan';

    if (image && image !== 'default.jpg') {
        document.getElementById('previewImg').src = '../Assets/images/' + image;
        document.getElementById('previewBox').style.display = 'block';
    } else {
        document.getElementById('previewBox').style.display = 'none';
    }

    document.getElementById('overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeOverlay() {
    document.getElementById('overlay').classList.remove('open');
    document.body.style.overflow = '';
}

function closeModal(e) {
    if (e.target === document.getElementById('overlay')) closeOverlay();
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('previewImg').src     = e.target.result;
            document.getElementById('previewBox').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeOverlay();
});

<?php if ($edit_row): ?>
openEdit(
    <?php echo $edit_row['id']; ?>,
    '<?php echo addslashes(htmlspecialchars($edit_row['title'])); ?>',
    '<?php echo addslashes(htmlspecialchars($edit_row['image'])); ?>',
    '<?php echo addslashes(htmlspecialchars($edit_row['location'])); ?>',
    '<?php echo addslashes(htmlspecialchars($edit_row['date_range'])); ?>',
    '<?php echo addslashes(htmlspecialchars($edit_row['category'])); ?>',
    '<?php echo addslashes(htmlspecialchars($edit_row['description'])); ?>'
);
<?php endif; ?>
</script>

</body>
</html>