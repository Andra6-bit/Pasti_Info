// Floating Search Bar Smooth Logic
const navbarSearch = document.getElementById('navbarSearch');
const mainSearchBar = document.querySelector('.search-wrapper');

window.addEventListener('scroll', function () {
  if (!mainSearchBar || !navbarSearch) return;

  // Deteksi posisi form pencarian di hero
  const searchBarRect = mainSearchBar.getBoundingClientRect();

  // Munculkan Pil Pencarian jika form utama sudah tergulung ke atas layar
  if (searchBarRect.bottom < 0) {
    navbarSearch.classList.add('visible');
  } else {
    navbarSearch.classList.remove('visible');
  }
});