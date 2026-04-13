document.addEventListener("DOMContentLoaded", function () {
  // Éléments
  const btnToggle = document.getElementById("btnToggleSearch");
  const searchForm = document.getElementById("searchForm");
  const btnRechercher = document.getElementById("btnRechercher");
  const btnActualiser = document.getElementById("btnActualiser");
  const sectionResultats = document.getElementById("sectionResultats");
  const resultatsGrid = document.getElementById("resultatsGridContent");
  const ctaSection = document.querySelector(".cta-section");

  // Config AJAX
  const ajaxUrl =
    typeof MRDS_Search_Config !== "undefined"
      ? MRDS_Search_Config.ajax_url
      : "/wp-admin/admin-ajax.php";

  // Stockage des filtres sélectionnés
  let selectedFilters = {
    tags_restaurant: [], // ["restaurant_tag:12", ...]
    types_cuisine: [],   // ["type_cuisine:5", ...]
    arrondissements: [],
  };


  // ========================================
  // TOGGLE SEARCH FORM
  // ========================================
  if (btnToggle && searchForm) {
    btnToggle.addEventListener("click", function () {
      btnToggle.classList.toggle("active");
      searchForm.classList.toggle("active");

      // Cacher les résultats si on réouvre le formulaire
      if (sectionResultats) {
        sectionResultats.classList.remove("active");
      }
    });
  }

  // ========================================
  // FILTRES DU FORMULAIRE (section search-form)
  // ========================================

  // Toggle Tags restaurant - Formulaire
  const searchTagsRestaurant = document.querySelectorAll(
    '.search-tag[data-filter="restaurant_tag"]',
  );
  searchTagsRestaurant.forEach((tag) => {
    tag.addEventListener("click", function () {
      this.classList.toggle("active");

      const value = this.dataset.value;
      const tax = this.dataset.tax || "restaurant_tag";
      const key = value ? `${tax}:${value}` : "";

      if (this.classList.contains("active")) {
        if (key && !selectedFilters.tags_restaurant.includes(key)) {
          selectedFilters.tags_restaurant.push(key);
        }
      } else {
        selectedFilters.tags_restaurant = selectedFilters.tags_restaurant.filter(
          (v) => v !== key,
        );
      }

      console.log("Tags restaurant:", selectedFilters.tags_restaurant);
    });
  });

  // Toggle Types de cuisine - Formulaire
  const searchTypesCuisine = document.querySelectorAll(
    '.search-tag[data-filter="type_cuisine"]',
  );
  searchTypesCuisine.forEach((tag) => {
    tag.addEventListener("click", function () {
      this.classList.toggle("active");

      const value = this.dataset.value;
      const tax = this.dataset.tax || "type_cuisine";
      const key = value ? `${tax}:${value}` : "";

      if (this.classList.contains("active")) {
        if (key && !selectedFilters.types_cuisine.includes(key)) {
          selectedFilters.types_cuisine.push(key);
        }
      } else {
        selectedFilters.types_cuisine = selectedFilters.types_cuisine.filter(
          (v) => v !== key,
        );
      }

      console.log("Types de cuisine:", selectedFilters.types_cuisine);
    });
  });


  // Toggle Arrondissements - Formulaire
  const searchArrondissements = document.querySelectorAll(
    ".search-arrondissement",
  );
  searchArrondissements.forEach((arr) => {
    arr.addEventListener("click", function () {
      this.classList.toggle("active");

      const value = parseInt(this.dataset.value);

      if (this.classList.contains("active")) {
        if (!selectedFilters.arrondissements.includes(value)) {
          selectedFilters.arrondissements.push(value);
        }
      } else {
        selectedFilters.arrondissements =
          selectedFilters.arrondissements.filter((v) => v !== value);
      }

      console.log("Arrondissements:", selectedFilters.arrondissements);
    });
  });

  // ========================================
  // FILTRES DE LA SECTION RÉSULTATS (cliquables)
  // ========================================

  // Délégation d'événements pour les filtres dans les résultats
const filterTagsRestaurant = document.getElementById("filterTagsRestaurant");
const filterTypesCuisine = document.getElementById("filterTypesCuisine");
  const filterArrondissements = document.getElementById(
    "filterArrondissements",
  );

if (filterTagsRestaurant) {
  filterTagsRestaurant.addEventListener("click", function (e) {
    const filterItem = e.target.closest('.filter-item[data-filter="restaurant_tag"]');
    if (!filterItem) return;

    filterItem.classList.toggle("active");

    const value = filterItem.dataset.value;
    const tax = filterItem.dataset.tax || "restaurant_tag";
    const key = value ? `${tax}:${value}` : "";

    if (filterItem.classList.contains("active")) {
      if (key && !selectedFilters.tags_restaurant.includes(key)) {
        selectedFilters.tags_restaurant.push(key);
      }
    } else {
      selectedFilters.tags_restaurant = selectedFilters.tags_restaurant.filter((v) => v !== key);
    }

    syncFormFilters();
    console.log("Tags restaurant (résultats):", selectedFilters.tags_restaurant);
  });
}

if (filterTypesCuisine) {
  filterTypesCuisine.addEventListener("click", function (e) {
    const filterItem = e.target.closest('.filter-item[data-filter="type_cuisine"]');
    if (!filterItem) return;

    filterItem.classList.toggle("active");

    const value = filterItem.dataset.value;
    const tax = filterItem.dataset.tax || "type_cuisine";
    const key = value ? `${tax}:${value}` : "";

    if (filterItem.classList.contains("active")) {
      if (key && !selectedFilters.types_cuisine.includes(key)) {
        selectedFilters.types_cuisine.push(key);
      }
    } else {
      selectedFilters.types_cuisine = selectedFilters.types_cuisine.filter((v) => v !== key);
    }

    syncFormFilters();
    console.log("Types cuisine (résultats):", selectedFilters.types_cuisine);
  });
}

  // Arrondissements - Section résultats
  if (filterArrondissements) {
    filterArrondissements.addEventListener("click", function (e) {
      const filterItem = e.target.closest(
        '.filter-item[data-filter="arrondissement"]',
      );
      if (!filterItem) return;

      filterItem.classList.toggle("active");
      const value = parseInt(filterItem.dataset.value);

      if (filterItem.classList.contains("active")) {
        if (!selectedFilters.arrondissements.includes(value)) {
          selectedFilters.arrondissements.push(value);
        }
      } else {
        selectedFilters.arrondissements =
          selectedFilters.arrondissements.filter((v) => v !== value);
      }

      // Synchroniser avec le formulaire
      syncFormFilters();
      console.log(
        "Arrondissements (résultats):",
        selectedFilters.arrondissements,
      );
    });
  }


  // ========================================
  // SYNCHRONISATION FORMULAIRE <-> RÉSULTATS
  // ========================================

  /**
   * Synchroniser les filtres du formulaire avec les filtres de la section résultats
   */
  function syncFormFilters() {
  // Tags restaurant
  document
    .querySelectorAll('.search-tag[data-filter="restaurant_tag"]')
    .forEach((tag) => {
      const value = tag.dataset.value;
      const tax = tag.dataset.tax || "restaurant_tag";
      const key = value ? `${tax}:${value}` : "";
      tag.classList.toggle("active", selectedFilters.tags_restaurant.includes(key));
    });

  // Types de cuisine
  document
    .querySelectorAll('.search-tag[data-filter="type_cuisine"]')
    .forEach((tag) => {
      const value = tag.dataset.value;
      const tax = tag.dataset.tax || "type_cuisine";
      const key = value ? `${tax}:${value}` : "";
      tag.classList.toggle("active", selectedFilters.types_cuisine.includes(key));
    });

  // Arrondissements
  document.querySelectorAll(".search-arrondissement").forEach((arr) => {
    const value = parseInt(arr.dataset.value);
    arr.classList.toggle("active", selectedFilters.arrondissements.includes(value));
  });
}


  /**
   * Synchroniser les filtres de la section résultats avec le formulaire
   */
  function syncResultsFilters() {
// Tags restaurant
document
  .querySelectorAll('#filterTagsRestaurant .filter-item[data-filter="restaurant_tag"]')
  .forEach((item) => {
    const value = item.dataset.value;
    const tax = item.dataset.tax || "restaurant_tag";
    const key = value ? `${tax}:${value}` : "";
    item.classList.toggle("active", selectedFilters.tags_restaurant.includes(key));
  });

// Types cuisine
document
  .querySelectorAll('#filterTypesCuisine .filter-item[data-filter="type_cuisine"]')
  .forEach((item) => {
    const value = item.dataset.value;
    const tax = item.dataset.tax || "type_cuisine";
    const key = value ? `${tax}:${value}` : "";
    item.classList.toggle("active", selectedFilters.types_cuisine.includes(key));
  });


    // Arrondissements
    document
      .querySelectorAll(
        '#filterArrondissements .filter-item[data-filter="arrondissement"]',
      )
      .forEach((item) => {
        const value = parseInt(item.dataset.value);
        if (selectedFilters.arrondissements.includes(value)) {
          item.classList.add("active");
        } else {
          item.classList.remove("active");
        }
      });

    // Types de remise
    document
      .querySelectorAll(
        '#filterReductions .filter-item[data-filter="type_remise"]',
      )
      .forEach((item) => {
        const value = parseInt(item.dataset.value);
        if (selectedFilters.types_remise.includes(value)) {
          item.classList.add("active");
        } else {
          item.classList.remove("active");
        }
      });
  }

  // ========================================
  // BOUTONS RECHERCHER & ACTUALISER
  // ========================================

  // Bouton Rechercher (formulaire)
  if (btnRechercher) {
    btnRechercher.addEventListener("click", function () {
      executeSearch();
    });
  }

  // Bouton Actualiser (section résultats)
  if (btnActualiser) {
    btnActualiser.addEventListener("click", function () {
      executeSearch(false); // false = ne pas cacher le formulaire (déjà caché)
    });
  }

  // ========================================
  // RECHERCHE AJAX
  // ========================================

  /**
   * Exécuter la recherche AJAX
   * @param {boolean} hideForm - Cacher le formulaire (true par défaut)
   */
  async function executeSearch(hideForm = true) {
    // Cacher le formulaire si demandé
    if (hideForm && searchForm) {
      searchForm.classList.remove("active");
      btnToggle.classList.remove("active");
    }

    // Afficher les résultats avec loading
    sectionResultats.classList.add("active");

    // Synchroniser les filtres de la section résultats
    syncResultsFilters();

    // Afficher loading
    if (resultatsGrid) {
      resultatsGrid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="loading-spinner">
                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#DA9D42" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round">
                                <animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/>
                            </path>
                        </svg>
                    </div>
                    <p class="mt-3">Recherche en cours...</p>
                </div>
            `;
    }

    // Scroll vers les résultats
    sectionResultats.scrollIntoView({ behavior: "smooth" });

    // Préparer les données
    const formData = new FormData();
    formData.append("action", "mrds_search_restaurants");

    // ✅ inchangé, mais maintenant envies[] contient "tax:id"
const enviesMerged = [
  ...selectedFilters.tags_restaurant,
  ...selectedFilters.types_cuisine,
];

enviesMerged.forEach((v) => formData.append("envies[]", v));

selectedFilters.arrondissements.forEach((v) =>
  formData.append("arrondissements[]", v),
);


    console.log("Envoi recherche...", selectedFilters);

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
      } else {
        displayError("Une erreur est survenue.");
      }
    } catch (error) {
      console.error("Erreur:", error);
      displayError("Une erreur est survenue. Veuillez réessayer.");
    }
  }

  // ========================================
  // AFFICHAGE DES RÉSULTATS
  // ========================================

  /**
   * Afficher les résultats
   */
  function displayResults(restaurants, message) {
    if (!resultatsGrid) return;

    if (restaurants.length === 0) {
      resultatsGrid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <p class="no-results">${message || "Aucun restaurant trouvé avec ces critères."}</p>
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
                <div class="col-12 col-md-6 col-lg-3 mb-4">
                    <div class="restaurant-card" data-restaurant-id="${resto.id}">
                        <div class="card-image">
<img src="${escapeHtml(resto.image)}" alt="${escapeHtml(resto.title)}">
                            ${resto.remise ? `<span class="card-remise-badge">${escapeHtml(resto.remise)}</span>` : ""}
                            <button class="card-favorite" data-restaurant-id="${resto.id}">
                                <span class="heart-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25.475" height="23.129" viewBox="0 0 25.475 23.129">
                                        <g transform="translate(1 1)">
                                            <path d="M21.623,1.9a6.307,6.307,0,0,0-8.978,0l-.883.883L10.879,1.9A6.348,6.348,0,0,0,1.9,10.879l9.861,9.861,9.861-9.861a6.307,6.307,0,0,0,0-8.978" transform="translate(-0.025 -0.025)" fill="none" stroke="#da9d42" stroke-width="2" fill-rule="evenodd" />
                                        </g>
                                    </svg>
                                </span>
                            </button>
                            <a href="${escapeHtml(resto.link)}" class="card-arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35">
                                    <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" fill="#fff" />
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
                </div>
            `;
    });

    resultatsGrid.innerHTML = html;
  }

  /**
   * Afficher une erreur
   */
  function displayError(message) {
    if (resultatsGrid) {
      resultatsGrid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <p class="error-message text-danger">${message}</p>
                </div>
            `;
    }
  }

  /**
   * Échapper le HTML
   */
  function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
});
