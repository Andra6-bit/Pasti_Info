// Fungsi Buka Tutup Pop-up Kategori
function toggleCategoryPopup(popupId) {
  const popup = document.getElementById(popupId);
  if (!popup) return;
  if (popup.style.display === "none" || popup.style.display === "") {
    popup.style.display = "block";
  } else {
    popup.style.display = "none";
  }
}

// Klik di luar jendela untuk menutup dropdown popup
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

function updateSelectedBadges() {
  const container = document.getElementById("selectedBadges");
  if (!container) return;
  const checkboxes = document.querySelectorAll('input[name="categories[]"]:checked');
  container.innerHTML = "";
  const uniqueCategories = new Set();
  checkboxes.forEach((cb) => uniqueCategories.add(cb.value));
  uniqueCategories.forEach((val) => {
    const span = document.createElement("span");
    span.className = "badge-indicator";
    span.innerText = val;
    container.appendChild(span);
  });
}

// ─── FUNGSI DINAMIS AJAX UNTUK PROSES TOGGLE BOOKMARK ───
function toggleBookmark(element, competitionId, isDetailPage = false) {
    if (!competitionId) return;

    const formData = new FormData();
    formData.append('competition_id', competitionId);

    fetch('toggle_bookmark.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.status === 401) {
            alert('Silakan login terlebih dahulu untuk menyimpan kompetisi!');
            window.location.href = 'auth.php'; 
            return null;
        }
        return response.json();
    })
    .then(data => {
        if (!data) return;

        if (data.status === 'success') {
            const isActive = data.action === 'added';
            element.setAttribute('data-active', isActive ? 'true' : 'false');

            if (isDetailPage) {
                element.innerHTML = isActive ? '★' : '☆';
                element.style.color = isActive ? '#f6a623' : '#4a6070';
            } else {
                element.innerHTML = isActive ? '♥' : '♡';
                element.style.color = isActive ? '#e53e3e' : '#aaa';
            }
        } else {
            alert('Gagal: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kendala jaringan saat memproses bookmark.');
    });
}

window.addEventListener("load", updateSelectedBadges);