// Modal
function openDetail(title, image, location, date, description) {
  document.getElementById("modalTitle").innerText = title;
  document.getElementById("modalImage").src = image;
  document.getElementById("modalLocation").innerText = "📍 " + location;
  document.getElementById("modalDate").innerText = "📅 " + date;
  document.getElementById("modalDescription").innerText = description;
  document.getElementById("detailModal").style.display = "flex";
  document.body.style.overflow = "hidden";
}

function closeDetail() {
  document.getElementById("detailModal").style.display = "none";
  document.body.style.overflow = "";
}

// 1. Fungsi Buka Tutup Pop-up Kategori
function toggleCategoryPopup() {
  const popup = document.getElementById("categoryPopup");
  // Logika toggle yang lebih stabil
  if (popup.style.display === "none" || popup.style.display === "") {
    popup.style.display = "block";
  } else {
    popup.style.display = "none";
  }
}

// 2. SATU-SATUNYA logika klik jendela (Gabungkan semua di sini)
window.onclick = function (event) {
  const popup = document.getElementById("categoryPopup");
  const trigger = document.querySelector(".btn-category-trigger");
  const modal = document.getElementById("detailModal");

  // LOGIKA POP-UP:
  // Jika klik terjadi BUKAN di tombol pemicu DAN BUKAN di dalam area pop-up
  if (popup && popup.style.display === "block") {
    if (!trigger.contains(event.target) && !popup.contains(event.target)) {
      popup.style.display = "none";
    }
  }

  // LOGIKA MODAL:
  if (event.target === modal) closeDetail();
};

// 3. Fungsi menampilkan kategori terpilih (Badge)
function updateSelectedBadges() {
  const container = document.getElementById("selectedBadges");
  const checkboxes = document.querySelectorAll(
    'input[name="categories[]"]:checked',
  );

  container.innerHTML = "";

  checkboxes.forEach((cb) => {
    const span = document.createElement("span");
    span.className = "badge-indicator";
    span.innerText = cb.value;
    container.appendChild(span);
  });
}

// Jalankan saat halaman pertama kali dimuat
window.addEventListener("load", updateSelectedBadges);
