<?php
session_start();
// Perketat akses: harus login dan username harus admin
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || ($_SESSION['user_username'] ?? '') !== 'admin') {
    header("Location: ../pages/auth.php?error=" . urlencode("Akses Ditolak! Khusus Admin."));
    exit();
}
include "../config/koneksi.php";

$msg      = '';
$msg_type = '';

// ── HAPUS ───────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $stmt = mysqli_prepare($koneksi, "SELECT image FROM competitions WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $del_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $stmt2 = mysqli_prepare($koneksi, "DELETE FROM competitions WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id);

    if (mysqli_stmt_execute($stmt2)) {
        if ($del_row && $del_row['image'] !== 'default.jpg') {
            $img_path = "../Assets/images/" . $del_row['image'];
            if (file_exists($img_path)) unlink($img_path);
        }
        $msg = "Kompetisi berhasil dihapus.";
        $msg_type = "success";
    } else {
        $msg = "Gagal menghapus kompetisi.";
        $msg_type = "error";
    }
}

// ── TAMBAH / EDIT (POST) ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? 'add';
    $post_id     = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $date_range  = trim($_POST['date_range'] ?? '');
    $category    = $_POST['category'] ?? 'All';
    $description = trim($_POST['description'] ?? '');

    $allowed = ['Design','Programming','Hacking','All'];
    if (!in_array($category, $allowed)) $category = 'All';

    // Upload gambar
    $image_name = $_POST['old_image'] ?? 'default.jpg';

    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $msg = "Format gambar tidak didukung."; $msg_type = "error";
        } elseif ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            $msg = "Ukuran gambar maksimal 3MB."; $msg_type = "error";
        } else {
            $image_name = uniqid('comp_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], "../Assets/images/" . $image_name)) {
                $msg = "Gagal upload gambar."; $msg_type = "error";
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
            $ok = mysqli_stmt_execute($stmt);
            $msg = $ok ? "Kompetisi berhasil ditambahkan!" : "Gagal menyimpan data.";
            $msg_type = $ok ? "success" : "error";
        } else {
            // Hapus gambar lama jika diganti
            $old_img = $_POST['old_image'] ?? 'default.jpg';
            if ($image_name !== $old_img && $old_img !== 'default.jpg') {
                $p = "../Assets/images/" . $old_img;
                if (file_exists($p)) unlink($p);
            }
            $stmt = mysqli_prepare($koneksi,
                "UPDATE competitions SET title=?, image=?, location=?, date_range=?, category=?, description=?
                 WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssssssi",
                $title, $image_name, $location, $date_range, $category, $description, $post_id);
            $ok = mysqli_stmt_execute($stmt);
            $msg = $ok ? "Kompetisi berhasil diperbarui!" : "Gagal memperbarui data.";
            $msg_type = $ok ? "success" : "error";
        }
    }
}

// Ambil semua data
$all = mysqli_query($koneksi, "SELECT * FROM competitions ORDER BY id DESC");
$total = mysqli_num_rows($all);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Kompetisi</title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<div class="main">
    <div class="topbar">
        <div>
            <h1>Kelola Kompetisi</h1>
            <p><?php echo $total; ?> kompetisi terdaftar</p>
        </div>
        <button class="btn btn-primary" onclick="openAdd()">+ Tambah Kompetisi</button>
    </div>

    <div class="content">
        <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <div class="panel">
            <div class="panel-header">
                <h2>Daftar Kompetisi</h2>
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
                    <?php $c = 0; while ($row = mysqli_fetch_assoc($all)): $c++; ?>
                    <tr>
                        <td>
                            <img src="../Assets/images/<?php echo htmlspecialchars($row['image']); ?>"
                                 class="td-img"
                                 onerror="this.src='../Assets/images/logo.jpeg'" alt="">
                        </td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($row['title']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower(htmlspecialchars($row['category'])); ?>">
                                <?php echo htmlspecialchars($row['category']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <td style="font-size:13px;white-space:nowrap;"><?php echo htmlspecialchars($row['date_range']); ?></td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-secondary btn-sm" onclick="openEdit(
                                    <?php echo $row['id']; ?>,
                                    '<?php echo addslashes(htmlspecialchars($row['title'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($row['image'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($row['location'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($row['date_range'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($row['category'])); ?>',
                                    '<?php echo addslashes(htmlspecialchars($row['description'])); ?>'
                                )">Edit</button>
                                <a href="?delete=<?php echo $row['id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Yakin hapus kompetisi ini?')">Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($c === 0): ?>
                    <tr><td colspan="6" class="empty-state">Belum ada kompetisi. Klik "+ Tambah Kompetisi" untuk mulai.</td></tr>
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

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action"    id="f-action"  value="add">
            <input type="hidden" name="id"        id="f-id"      value="">
            <input type="hidden" name="old_image" id="f-old-img" value="default.jpg">

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
                    <select name="category" id="f-category">
                        <option value="Design">Design</option>
                        <option value="Programming">Programming</option>
                        <option value="Hacking">Hacking</option>
                        <option value="All">All</option>
                    </select>
                </div>
                <div class="form-group">
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
    document.getElementById('f-desc').value     = '';
    document.getElementById('f-submit').textContent = 'Tambah';
    document.getElementById('previewBox').style.display = 'none';
    document.getElementById('overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function openEdit(id, title, image, location, date, category, desc) {
    document.getElementById('modal-title').textContent  = 'Edit Kompetisi';
    document.getElementById('f-action').value           = 'edit';
    document.getElementById('f-id').value               = id;
    document.getElementById('f-old-img').value          = image;
    document.getElementById('f-title').value            = title;
    document.getElementById('f-location').value         = location;
    document.getElementById('f-date').value             = date;
    document.getElementById('f-category').value         = category;
    document.getElementById('f-desc').value             = desc;
    document.getElementById('f-submit').textContent     = 'Simpan Perubahan';

    var pb = document.getElementById('previewBox');
    if (image && image !== 'default.jpg') {
        document.getElementById('previewImg').src = '../Assets/images/' + image;
        pb.style.display = 'block';
    } else {
        pb.style.display = 'none';
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

function previewImg(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('previewBox').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeOverlay();
});
</script>

</body>
</html>