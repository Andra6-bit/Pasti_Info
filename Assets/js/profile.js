// Mengatur perpindahan tab pada halaman profile
function switchTab(tab, el) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sidenav-item').forEach(i => i.classList.remove('active'));
    var panel = document.getElementById('tab-' + tab);
    if (panel) panel.classList.add('active');
    if (el) el.classList.add('active');
}