/**
 * Carnet d'adresses - Filtres + AJAX + Carte
 */
document.addEventListener("DOMContentLoaded", function () {
  // ================================
  // CONFIGURATION
  // ================================
  const ajaxUrl =
    typeof MRDS_Search_Config !== "undefined"
      ? MRDS_Search_Config.ajax_url
      : "/wp-admin/admin-ajax.php";

  const carnetGrid = document.getElementById("carnetGrid");
  const carnetPagination = document.getElementById("carnetPagination");
  const resetBtn = document.getElementById("resetFilters");
  const filterDropdowns = document.querySelectorAll(".filter-dropdown");

  let selectedFilters = {
    restaurant_tag: [],
    type_cuisine: [],
    arrondissement: [],
  };

  let currentPage = 1;
  let searchQuery = "";

  // ================================
  // GESTION DES DROPDOWNS
  // ================================
  filterDropdowns.forEach(function (dropdown) {
    const btn = dropdown.querySelector(".filter-btn");
    const menu = dropdown.querySelector(".filter-menu");
    const btnText = btn.querySelector("span");
    const filterType = dropdown.dataset.filter;
    const defaultText = btnText.textContent;

    btn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      filterDropdowns.forEach(function (other) {
        if (other !== dropdown) {
          other.classList.remove("active");
        }
      });

      dropdown.classList.toggle("active");
    });

    if (menu) {
      menu.querySelectorAll("a").forEach(function (option) {
        option.addEventListener("click", function (e) {
          e.preventDefault();

          const value = this.dataset.value;
          const tax = this.dataset.tax || "";

          if (
            filterType === "restaurant_tag" ||
            filterType === "type_cuisine"
          ) {
            const key = value ? `${tax}:${value}` : "";

            if (!key) {
              selectedFilters[filterType] = [];
            } else {
              const idx = selectedFilters[filterType].indexOf(key);
              if (idx === -1) selectedFilters[filterType].push(key);
              else selectedFilters[filterType].splice(idx, 1);
            }
          } else if (filterType === "arrondissement") {
            if (!value) {
              selectedFilters.arrondissement = [];
            } else {
              const idx = selectedFilters.arrondissement.indexOf(value);
              if (idx === -1) selectedFilters.arrondissement.push(value);
              else selectedFilters.arrondissement.splice(idx, 1);
            }
          }

          function updateBtnLabel() {
            if (filterType === "restaurant_tag") {
              btnText.textContent = selectedFilters.restaurant_tag.length
                ? `${selectedFilters.restaurant_tag.length} sélectionné(s)`
                : defaultText;
            } else if (filterType === "type_cuisine") {
              btnText.textContent = selectedFilters.type_cuisine.length
                ? `${selectedFilters.type_cuisine.length} sélectionné(s)`
                : defaultText;
            } else if (filterType === "arrondissement") {
              btnText.textContent = selectedFilters.arrondissement.length
                ? `${selectedFilters.arrondissement.length} sélectionné(s)`
                : defaultText;
            }
          }
          updateBtnLabel();

          if (!value) {
            menu
              .querySelectorAll("a")
              .forEach((a) => a.classList.remove("selected"));
          } else {
            this.classList.toggle("selected");
          }

          currentPage = 1;
          executeSearch();
        });
      });
    }
  });

  // Fermer dropdown si clic en dehors
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".filter-dropdown")) {
      filterDropdowns.forEach(function (dropdown) {
        dropdown.classList.remove("active");
      });
    }
  });

  // ================================
  // DOTS DE PAGINATION — DÉLÉGATION
  // ================================
  if (carnetPagination) {
    carnetPagination.addEventListener("click", function (e) {
      const dot = e.target.closest(".pagination-dot");
      if (!dot) return;

      e.preventDefault();

      currentPage = parseInt(dot.dataset.page);
      executeSearch();
    });
  }

  // ================================
  // BOUTON RESET
  // ================================
  if (resetBtn) {
    resetBtn.addEventListener("click", function (e) {
      e.preventDefault();

      selectedFilters = {
        restaurant_tag: [],
        type_cuisine: [],
        arrondissement: [],
      };

      filterDropdowns.forEach(function (dropdown) {
        const btn = dropdown.querySelector(".filter-btn span");
        const filterType = dropdown.dataset.filter;

        if (filterType === "restaurant_tag") {
          btn.textContent = "De quoi avez-vous envie ?";
        } else if (filterType === "type_cuisine") {
          btn.textContent = "Types de cuisine";
        } else if (filterType === "arrondissement") {
          btn.textContent = "Dans quel arrondissement ?";
        }

        dropdown
          .querySelectorAll(".filter-menu a")
          .forEach((a) => a.classList.remove("selected"));
      });

      currentPage = 1;
      searchQuery = "";
      if (filterSearch) filterSearch.value = "";
      executeSearch();
    });
  }

  if (filterSearch) {
    let searchTimer = null;
    filterSearch.addEventListener("input", function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        searchQuery = filterSearch.value.trim();
        currentPage = 1;
        executeSearch();
      }, 300);
    });
  }

  // ================================
  // RECHERCHE AJAX
  // ================================
  async function executeSearch() {
    if (carnetGrid) {
      carnetGrid.innerHTML = `
        <div class="loading-container">
          <div class="loading-spinner">
            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#DA9D42" stroke-width="2">
              <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
              <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round">
                <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
              </path>
            </svg>
          </div>
          <p>Recherche en cours...</p>
        </div>
      `;
    }

    const formData = new FormData();
    formData.append("action", "mrds_search_restaurants");
    formData.append("search", searchQuery);
    formData.append("paged", currentPage);

    selectedFilters.restaurant_tag.forEach((v) =>
      formData.append("envies[]", v),
    );
    selectedFilters.type_cuisine.forEach((v) => formData.append("envies[]", v));
    selectedFilters.arrondissement.forEach((v) =>
      formData.append("arrondissements[]", v),
    );

    console.log("Filtres:", selectedFilters, "Page:", currentPage);

    try {
      const response = await fetch(ajaxUrl, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      });

      const result = await response.json();
      console.log("Résultats:", result);

      if (result.success) {
        displayResults(result.data.restaurants, result.data.message);
        updatePagination(result.data.total_pages);
        updateMap(result.data.restaurants);
      } else {
        displayError("Une erreur est survenue.");
      }
    } catch (error) {
      console.error("Erreur:", error);
      displayError("Une erreur est survenue. Veuillez réessayer.");
    }
  }

  // ================================
  // AFFICHER LES RÉSULTATS
  // ================================
  function displayResults(restaurants, message) {
    if (!carnetGrid) return;

    if (restaurants.length === 0) {
      carnetGrid.innerHTML = `
        <div class="no-results">
          <p>${message || "Aucun restaurant trouvé avec ces critères."}</p>
        </div>
      `;
      return;
    }

    let html = "";
    restaurants.forEach((resto) => {
      const tagsHtml = (resto.tags || [])
        .slice(0, 3)
        .map((tag) => `<span class="tag">${escapeHtml(tag)}</span>`)
        .join("");

      const cuisinesHtml = (resto.cuisines || [])
        .slice(0, 3)
        .map(
          (c) => `<span class="tag tag-type-cuisine">${escapeHtml(c)}</span>`,
        )
        .join("");

      const allTagsHtml = `${tagsHtml}${cuisinesHtml}`;

      html += `
        <div class="restaurant-card" data-restaurant-id="${resto.id}">
<div class="card-image">
    <img src="${escapeHtml(resto.image)}" alt="${escapeHtml(resto.title)}">
    ${resto.remise ? `<span class="card-remise-badge">${escapeHtml(resto.remise)}</span>` : ""}
    <a href="${escapeHtml(resto.link)}" class="card-arrow">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35">
            <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.264L14.318,31.818,17.5,35,35,17.5Z" fill="#fff" />
        </svg>
    </a>
</div>
          <div class="card-content">
            <h3 class="card-title">
              <a href="${escapeHtml(resto.link)}">${escapeHtml(resto.title)}</a>
            </h3>
            <p class="card-location">${escapeHtml(resto.location)}</p>
            ${allTagsHtml ? `<div class="card-tags">${allTagsHtml}</div>` : ""}
            ${resto.quote ? `<blockquote class="card-quote">« ${escapeHtml(resto.quote.length > 120 ? resto.quote.substring(0, 120).trim() + "..." : resto.quote)} »</blockquote>` : ""}
${resto.chef ? `<p class="card-chef">— ${escapeHtml(resto.chef)}</p>` : ""}
          </div>
        </div>
      `;
    });

    carnetGrid.innerHTML = html;
  }

  // ================================
  // METTRE À JOUR LA PAGINATION
  // ================================
  function updatePagination(totalPages) {
    if (!carnetPagination) return;

    if (!totalPages || totalPages <= 1) {
      carnetPagination.style.display = "none";
      return;
    }

    // Reconstruire les dots
    let dotsHtml = "";
    for (let i = 1; i <= totalPages; i++) {
      const activeClass = i === currentPage ? "active" : "";
      dotsHtml += `<a href="#" class="pagination-dot ${activeClass}" data-page="${i}" aria-label="Page ${i}"></a>`;
    }

    carnetPagination.innerHTML = dotsHtml;
    carnetPagination.style.display = "";
  }

  // ================================
  // METTRE À JOUR LA CARTE
  // ================================
  function updateMap(restaurants) {
    if (typeof MRDS_Map === "undefined") {
      console.warn("MRDS_Map non disponible");
      return;
    }

    const mapInstance = MRDS_Map.get("map-carnet");
    if (!mapInstance) {
      console.warn("Instance map-carnet non trouvée");
      return;
    }

    // Normaliser les clés pour qu'elles correspondent
    // à ce qu'attend map-leaflet.js (address + reduction)
    const normalized = restaurants
      .filter((r) => r.lat && r.lng)
      .map((r) => ({
        ...r,
        address: r.address || r.location || "",
        reduction: r.reduction || r.remise || "",
      }));

    mapInstance.updateMarkers(normalized);
  }

  // ================================
  // AFFICHER ERREUR
  // ================================
  function displayError(message) {
    if (carnetGrid) {
      carnetGrid.innerHTML = `
        <div class="error-container">
          <p class="error-message">${message}</p>
        </div>
      `;
    }
  }

  // ================================
  // HELPER
  // ================================
  function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
});
