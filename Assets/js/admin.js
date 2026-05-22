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
    var data;
    if (typeof id === 'object' && id !== null) {
        data = id;
    } else {
        data = {
            id: id,
            title: title,
            foto: image,
            image: image,
            pelaksanaan: pelaksanaan,
            date_range: date_range,
            target_peserta: target_peserta,
            biaya: biaya,
            category: category,
            description: desc,
            desc: desc
        };
    }

    document.getElementById('modal-title').textContent   = 'Edit Kompetisi';
    document.getElementById('f-action').value            = 'edit';
    document.getElementById('f-id').value                = data.id;
    document.getElementById('f-old-img').value           = data.foto || data.image || 'default.jpg';
    document.getElementById('f-title').value             = data.title || '';
    document.getElementById('f-pelaksanaan').value       = data.pelaksanaan || 'Online';
    document.getElementById('f-target-peserta').value    = data.target_peserta || 'Umum';
    document.getElementById('f-biaya').value             = data.biaya || '';
    document.getElementById('f-category').value          = data.category || '';
    
    var dateRange = data.date_range || '';
    var dates = dateRange.split(',');
    if (dates.length === 2) {
        document.getElementById('f-start-date').value = dates[0];
        document.getElementById('f-end-date').value   = dates[1];
    } else {
        document.getElementById('f-start-date').value = '';
        document.getElementById('f-end-date').value   = '';
    }

    document.getElementById('f-desc').value              = data.description || data.desc || '';
    document.getElementById('f-submit').textContent      = 'Simpan Perubahan';

    var pb = document.getElementById('previewBox');
    var previewImage = data.foto || data.image || '';
    if (previewImage && previewImage !== 'default.jpg') {
        document.getElementById('previewImg').src = '../Assets/images/' + previewImage;
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