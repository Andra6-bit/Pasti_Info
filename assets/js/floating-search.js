// Floating Search Bar Smooth Logic
const navbarSearch = document.getElementById('navbarSearchWrapper');
const mainSearchBar = document.querySelector('.search-wrapper');

if (mainSearchBar && navbarSearch) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      // If the main search bar is out of view and has scrolled above the viewport
      if (!entry.isIntersecting && entry.boundingClientRect.bottom < 0) {
        navbarSearch.classList.add('visible');
      } else {
        navbarSearch.classList.remove('visible');
      }
    });
  }, {
    threshold: 0,
    rootMargin: '0px'
  });

  observer.observe(mainSearchBar);
}

/* ============================================
   SINKRONISASI FORM MAIN & FLOATING SEARCH
   ============================================ */
document.addEventListener("DOMContentLoaded", function () {
  // 1. Sinkronisasi Kolom Teks Pencarian
  const mainSearchInput = document.getElementById('searchInput');
  const floatingSearchInput = document.querySelector('#navbarSearch input[name="search"]');

  if (mainSearchInput && floatingSearchInput) {
    // Kalau ngetik di Main, update Floating
    mainSearchInput.addEventListener('input', function() {
      floatingSearchInput.value = this.value;
    });
    // Kalau ngetik di Floating, update Main
    floatingSearchInput.addEventListener('input', function() {
      mainSearchInput.value = this.value;
    });
  }

  // 2. Sinkronisasi Centang Kategori
  const mainCheckboxes = document.querySelectorAll('#categoryPopupHero input[type="checkbox"]');
  const floatingCheckboxes = document.querySelectorAll('#categoryPopupFloating input[type="checkbox"]');

  // Kalau klik kategori di Main, centang juga di Floating
  mainCheckboxes.forEach((cb, index) => {
    cb.addEventListener('change', function() {
      if (floatingCheckboxes[index]) {
        floatingCheckboxes[index].checked = this.checked;
      }
      // Panggil fungsi badge dari landing.js jika tersedia
      if (typeof updateSelectedBadges === 'function') updateSelectedBadges();
    });
  });

  // Kalau klik kategori di Floating, centang juga di Main
  floatingCheckboxes.forEach((cb, index) => {
    cb.addEventListener('change', function() {
      if (mainCheckboxes[index]) {
        mainCheckboxes[index].checked = this.checked;
      }
      if (typeof updateSelectedBadges === 'function') updateSelectedBadges();
    });
  });
});
