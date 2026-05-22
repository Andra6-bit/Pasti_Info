// ── TAB SWITCHING UTAMA ──
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidenav-item').forEach(i => i.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
    history.replaceState(null, '', '?tab=' + tab);
}

// ── SCRIPT KHUSUS KELOLA LOMBA (MODAL) ──
function openAdd() {
    document.getElementById('modal-title').textContent   = 'Tambah Kompetisi';
    document.getElementById('f-action').value            = 'add';
    document.getElementById('f-id').value                = '';
    document.getElementById('f-old-img').value           = 'default.jpg';
    document.getElementById('f-title').value             = '';
    document.getElementById('f-pelaksanaan').value       = 'Online';
    document.getElementById('f-target-peserta').value    = 'Umum';
    document.getElementById('f-biaya').value             = '';
    document.getElementById('f-category').value          = '';
    document.getElementById('f-start-date').value        = '';
    document.getElementById('f-end-date').value          = '';
    document.getElementById('f-desc').value              = '';
    document.getElementById('f-submit').textContent      = 'Tambah';
    document.getElementById('previewBox').style.display  = 'none';
    document.getElementById('overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function openEdit(id, title, image, pelaksanaan, date_range, target_peserta, biaya, category, desc) {
    document.getElementById('modal-title').textContent   = 'Edit Kompetisi';
    document.getElementById('f-action').value            = 'edit';
    document.getElementById('f-id').value                = id;
    document.getElementById('f-old-img').value           = image;
    document.getElementById('f-title').value             = title;
    document.getElementById('f-pelaksanaan').value       = pelaksanaan;
    document.getElementById('f-target-peserta').value    = target_peserta;
    document.getElementById('f-biaya').value             = biaya;
    document.getElementById('f-category').value          = category;
    
    let dates = date_range.split(',');
    if(dates.length === 2) {
        document.getElementById('f-start-date').value = dates[0];
        document.getElementById('f-end-date').value   = dates[1];
    } else {
        document.getElementById('f-start-date').value = '';
        document.getElementById('f-end-date').value   = '';
    }

    document.getElementById('f-desc').value              = desc;
    document.getElementById('f-submit').textContent      = 'Simpan Perubahan';

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