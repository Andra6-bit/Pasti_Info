/**
 * Logika Modal - P Info
 * Search & Filter sekarang dihandle oleh PHP (GET form)
 */

// Buka modal detail
function openDetail(title, image, location, date, description) {
    document.getElementById("modalTitle").innerText       = title;
    document.getElementById("modalImage").src             = image;
    document.getElementById("modalLocation").innerText    = "📍 " + location;
    document.getElementById("modalDate").innerText        = "📅 " + date;
    document.getElementById("modalDescription").innerText = description;
    document.getElementById("detailModal").style.display  = "flex";
    document.body.style.overflow = "hidden";
}

// Tutup modal
function closeDetail() {
    document.getElementById("detailModal").style.display = "none";
    document.body.style.overflow = "";
}

// Tutup modal jika klik di luar box
window.onclick = function (event) {
    const modal = document.getElementById("detailModal");
    if (event.target === modal) {
        closeDetail();
    }
};

// Tutup modal dengan tombol Escape
document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        closeDetail();
    }
});