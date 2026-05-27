// 1. Toggle Category Pop-up
function toggleCategoryPopup(popupId) {
  // Close all other pop-ups to avoid multiple overlapping dropdowns
  document.querySelectorAll('.category-popup-content').forEach(p => {
    if (p.id !== popupId) {
      p.style.display = 'none';
    }
  });

  // Open/Close clicked pop-up
  const popup = document.getElementById(popupId);
  if (popup.style.display === "none" || popup.style.display === "") {
    popup.style.display = "block";
  } else {
    popup.style.display = "none";
  }
}

// 2. Click outside the pop-up to close
window.addEventListener('click', function(event) {
  const isTriggerClick = event.target.closest('.btn-category-trigger');
  const isInsidePopup = event.target.closest('.category-popup-content');

  if (!isTriggerClick && !isInsidePopup) {
    document.querySelectorAll('.category-popup-content').forEach(popup => {
      popup.style.display = "none";
    });
  }
});

// 3. Show selected categories (Badges) without duplicates
function updateSelectedBadges() {
  const container = document.getElementById("selectedBadges");
  if (!container) return;

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

// Run on page load
window.addEventListener("load", updateSelectedBadges);

/* ============================================
   LOAD MORE FEATURE
   ============================================ */
document.addEventListener("DOMContentLoaded", function () {
  const cards = document.querySelectorAll(".card-grid .card");
  const loadMoreContainer = document.getElementById("loadMoreContainer");
  const loadMoreBtn = document.getElementById("loadMoreBtn");
  
  let itemsToShow = 10; // Initial number of cards to display

  // If total cards > 10, hide the rest and show the button
  if (cards.length > itemsToShow) {
    if (loadMoreContainer) loadMoreContainer.style.display = "block";
    
    // Hide cards starting from the 11th index
    for (let i = itemsToShow; i < cards.length; i++) {
      cards[i].style.display = "none";
    }
  }

  // Load more button click event
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener("click", function () {
      let hiddenCards = [];
      
      // Collect all hidden cards
      for (let i = 0; i < cards.length; i++) {
        if (cards[i].style.display === "none") {
          hiddenCards.push(cards[i]);
        }
      }

      // Display up to 10 additional cards
      for (let i = 0; i < 10 && i < hiddenCards.length; i++) {
        hiddenCards[i].style.display = "flex"; 
      }

      // Hide load more button if no more hidden cards
      if (hiddenCards.length <= 10) {
        if (loadMoreContainer) loadMoreContainer.style.display = "none";
      }
    });
  }
});
