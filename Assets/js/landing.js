// 1. Fungsi Buka Tutup Pop-up Kategori (Sekarang dinamis sesuai ID)
function toggleCategoryPopup(popupId) {
  const popup = document.getElementById(popupId);
  if (popup.style.display === "none" || popup.style.display === "") {
    popup.style.display = "block";
  } else {
    popup.style.display = "none";
  }
}

// 2. Klik jendela menutup Pop-up kategori
window.onclick = function (event) {
  const popups = ['categoryPopupHero', 'categoryPopupFloating'];
  
  popups.forEach(id => {
      const popup = document.getElementById(id);
      if (popup && popup.style.display === "block") {
          const isClickInside = popup.contains(event.target);
          const isClickTrigger = event.target.closest('.btn-category-trigger') || event.target.closest('.btn-category-trigger--dark');
          
          if (!isClickInside && !isClickTrigger) {
              popup.style.display = "none";
          }
      }
  });
};

// 3. Fungsi menampilkan kategori terpilih (Badge) tanpa duplikat
function updateSelectedBadges() {
  const container = document.getElementById("selectedBadges");
  if (!container) return; // Mencegah error jika tidak ada container

  const checkboxes = document.querySelectorAll('input[name="categories[]"]:checked');
  
  container.innerHTML = "";
  
  const uniqueCategories = new Set();
  
  checkboxes.forEach((cb) => {
    uniqueCategories.add(cb.value);
  });

  uniqueCategories.forEach((val) => {
    const span = document.createElement("span");
    span.className = "badge-indicator";
    span.innerText = val;
    container.appendChild(span);
  });
}

// Jalankan saat halaman pertama kali dimuat
window.addEventListener("load", updateSelectedBadges);