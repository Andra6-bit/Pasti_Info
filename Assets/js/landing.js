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

window.onclick = function (event) {
  const modal = document.getElementById("detailModal");
  if (event.target === modal) closeDetail();
};

const isLoggedIn = !!document.querySelector(".auth-buttons span");
let idleTimer;
function resetTimer() {
  if (!isLoggedIn) return;

  clearTimeout(idleTimer);
  idleTimer = setTimeout(() => {
    window.location.href = "logout.php";
  }, 60000);
}

if (isLoggedIn) {
  window.addEventListener("load", resetTimer);
  window.addEventListener("mousemove", resetTimer);
  window.addEventListener("mousedown", resetTimer);
  window.addEventListener("touchstart", resetTimer);
  window.addEventListener("click", resetTimer);
  window.addEventListener("keydown", resetTimer);
}

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape") closeDetail();
});

// Debounce search agar tidak submit setiap ketukan
let debounceTimer;
function debounceSubmit() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(function () {
    document.getElementById("filterForm").submit();
  }, 500);
}
