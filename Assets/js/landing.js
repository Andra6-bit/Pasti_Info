// 1. Fungsi Buka Tutup Pop-up Kategori
function toggleCategoryPopup(popupId) {
  // Tutup semua pop-up lain terlebih dahulu agar tidak ada yang terbuka ganda
  document.querySelectorAll('.category-popup-content').forEach(p => {
    if (p.id !== popupId) {
      p.style.display = 'none';
    }
  });

  // Buka/Tutup pop-up yang sedang diklik
  const popup = document.getElementById(popupId);
  if (popup.style.display === "none" || popup.style.display === "") {
    popup.style.display = "block";
  } else {
    popup.style.display = "none";
  }
}

// 2. Klik sembarang tempat untuk menutup Pop-up
window.addEventListener('click', function(event) {
  // Cek apakah klik terjadi di tombol kategori atau di dalam kotak pop-up
  const isTriggerClick = event.target.closest('.btn-category-trigger');
  const isInsidePopup = event.target.closest('.category-popup-content');

  // Jika klik BUKAN di tombol dan BUKAN di dalam kotak, maka tutup semua pop-up
  if (!isTriggerClick && !isInsidePopup) {
    document.querySelectorAll('.category-popup-content').forEach(popup => {
      popup.style.display = "none";
    });
  }
});

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

/* ============================================
   FITUR LOAD MORE (MUAT LEBIH BANYAK)
   ============================================ */
document.addEventListener("DOMContentLoaded", function () {
  const cards = document.querySelectorAll(".card-grid .card");
  const loadMoreContainer = document.getElementById("loadMoreContainer");
  const loadMoreBtn = document.getElementById("loadMoreBtn");
  
  let itemsToShow = 10; // Jumlah awal card yang ditampilkan

  // Jika total card lebih dari 10, sembunyikan sisanya dan munculkan tombol
  if (cards.length > itemsToShow) {
    if (loadMoreContainer) loadMoreContainer.style.display = "block";
    
    // Sembunyikan card ke-11 dan seterusnya
    for (let i = itemsToShow; i < cards.length; i++) {
      cards[i].style.display = "none";
    }
  }

  // Aksi ketika tombol ditekan
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener("click", function () {
      let hiddenCards = [];
      
      // Kumpulkan semua card yang sedang tersembunyi
      for (let i = 0; i < cards.length; i++) {
        if (cards[i].style.display === "none") {
          hiddenCards.push(cards[i]);
        }
      }

      // Tampilkan maksimal 10 card tambahan
      for (let i = 0; i < 10 && i < hiddenCards.length; i++) {
        // Karena card kamu memakai flexbox, kita kembalikan ke 'flex' bukan 'block'
        hiddenCards[i].style.display = "flex"; 
      }

      // Jika sisa card yang tersembunyi sudah habis (atau kurang dari 10), sembunyikan tombol
      if (hiddenCards.length <= 10) {
        if (loadMoreContainer) loadMoreContainer.style.display = "none";
      }
    });
  }
});