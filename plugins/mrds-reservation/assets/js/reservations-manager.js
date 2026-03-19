/**
 * MRDS Reservations Manager - JavaScript
 *
 * Gestion des réservations côté restaurateur
 * Vue liste uniquement (calendrier supprimé)
 *
 * @package MRDS_Reservation
 */

(function () {
  "use strict";

  // Configuration
  const CONFIG = window.MRDSReservationsConfig || {
    restUrl: "/wp-json/mrds/v1/reservations",
    nonce: "",
    restaurantId: 0,
    allRestaurantIds: [],
    isAllMode: false,
    isAdmin: false,
  };

  // Nombre de colonnes : 7 (mono) ou 8 (multi, colonne Restaurant)
  const HAS_MULTIPLE =
    CONFIG.allRestaurantIds && CONFIG.allRestaurantIds.length > 1;

  let state = {
    reservations: [],
    filters: {
      status: "",
      dateFrom: "", // ← vide au lieu de aujourd'hui
      dateTo: "", // ← vide au lieu de +30 jours
    },
    selectedReservation: null,
  };

  // Labels des statuts (avec refused)
  const STATUS_LABELS = {
    pending: "En attente",
    confirmed: "Confirmée",
    refused: "Refusée",
    cancelled: "Annulée",
    completed: "Effectuée",
    "no-show": "Absent",
  };

  // Classes CSS pour les statuts (avec refused)
  const STATUS_CLASSES = {
    pending: "status-pending",
    confirmed: "status-confirmed",
    refused: "status-refused",
    cancelled: "status-cancelled",
    completed: "status-completed",
    "no-show": "status-noshow",
  };

  // ========================================
  // INITIALISATION
  // ========================================

  document.addEventListener("DOMContentLoaded", function () {
    initEventListeners();
    loadReservations();
  });

  function initEventListeners() {
    // Sélecteur de restaurant (si plusieurs)
    const selectRestaurant = document.getElementById("select-restaurant");
    if (selectRestaurant) {
      selectRestaurant.addEventListener("change", function () {
        const val = parseInt(this.value);
        // Recharger la page avec le nouveau restaurant_id (0 = tous)
        const url = new URL(window.location.href);
        url.searchParams.set("restaurant_id", val);
        window.location.href = url.toString();
      });
    }

    // Filtres
    const btnFilter = document.getElementById("btn-filter");
    if (btnFilter) {
      btnFilter.addEventListener("click", applyFilters);
    }

    // Rafraîchir
    const btnRefresh = document.getElementById("btn-refresh");
    if (btnRefresh) {
      btnRefresh.addEventListener("click", loadReservations);
    }

    // Actions modal (boutons dans le footer du modal)
    document
      .querySelectorAll("#reservationModal [data-action]")
      .forEach((btn) => {
        btn.addEventListener("click", function () {
          handleAction(this.dataset.action);
        });
      });

    // Confirmation action
    const btnConfirmAction = document.getElementById("btn-confirm-action");
    if (btnConfirmAction) {
      btnConfirmAction.addEventListener("click", executeConfirmedAction);
    }
  }

  // ========================================
  // CHARGEMENT DES DONNÉES
  // ========================================

  async function loadReservations() {
    showLoading();

    try {
      let params;

      if (
        CONFIG.isAllMode &&
        CONFIG.allRestaurantIds &&
        CONFIG.allRestaurantIds.length > 0
      ) {
        // Mode "tous" : envoyer la liste des IDs
        params = new URLSearchParams({
          restaurant_ids: CONFIG.allRestaurantIds.join(","),
          status: state.filters.status,
          date_from: state.filters.dateFrom,
          date_to: state.filters.dateTo,
        });
      } else {
        // Mode mono-restaurant
        params = new URLSearchParams({
          restaurant_id: CONFIG.restaurantId,
          status: state.filters.status,
          date_from: state.filters.dateFrom,
          date_to: state.filters.dateTo,
        });
      }

      const response = await fetch(`${CONFIG.restUrl}?${params}`, {
        headers: {
          "X-WP-Nonce": CONFIG.nonce,
        },
      });

      if (!response.ok) {
        throw new Error("Erreur lors du chargement");
      }

      const data = await response.json();
      state.reservations = data.reservations || [];

      updateStats(data.stats || {});
      renderReservations();
      updateCount();
    } catch (error) {
      console.error("Erreur:", error);
      showMessage("Erreur lors du chargement des réservations.", "danger");
    }
  }
  function applyFilters() {
    state.filters.status = document.getElementById("filter-status").value;

    // Convertir dd/mm/yyyy → yyyy-mm-dd
    const dateFrom = document.getElementById("filter-date-from").value;
    const dateTo = document.getElementById("filter-date-to").value;

    state.filters.dateFrom = convertDateFrToIso(dateFrom);
    state.filters.dateTo = convertDateFrToIso(dateTo);

    loadReservations();
  }

  // Fonction de conversion FR → ISO
  function convertDateFrToIso(dateFr) {
    if (!dateFr) return "";
    const parts = dateFr.split("/");
    if (parts.length === 3) {
      return parts[2] + "-" + parts[1] + "-" + parts[0]; // yyyy-mm-dd
    }
    return dateFr;
  }

  // ========================================
  // RENDU - LISTE UNIQUEMENT
  // ========================================

  function renderReservations() {
    const tbody = document.getElementById("reservations-tbody");
    if (!tbody) return;

    if (state.reservations.length === 0) {
      tbody.innerHTML = `
                <tr>
                    <td colspan="${HAS_MULTIPLE ? 8 : 7}" class="text-center py-4 text-muted">
                        Aucune réservation trouvée pour cette période.
                    </td>
                </tr>
            `;
      return;
    }

    tbody.innerHTML = state.reservations
      .map((resa) => {
        const dateObj = new Date(resa.date);
        const dateStr = dateObj.toLocaleDateString("fr-FR", {
          weekday: "short",
          day: "numeric",
          month: "short",
        });

        const statusClass = STATUS_CLASSES[resa.status] || "status-pending";
        const statusLabel = STATUS_LABELS[resa.status] || resa.status;

        // Colonne Restaurant : uniquement en mode multi
        const restaurantCell = HAS_MULTIPLE
          ? `<td><span class="restaurant-badge">${escapeHtml(resa.restaurant_name || "")}</span></td>`
          : "";

        return `
                <tr data-id="${resa.id}">
                    <td>
                        <strong>${dateStr}</strong>
                    </td>
                    <td>${resa.time}</td>
                    <td>
                        <div class="client-info">
                            <strong>${escapeHtml(resa.client_name)}</strong>
                            ${resa.email ? `<small class="text-muted d-block">${escapeHtml(resa.email)}</small>` : ""}
                        </div>
                    </td>
                    ${restaurantCell}
                    <td>
                        <span class="guests-badge">${resa.guests} pers.</span>
                    </td>
<td>
                        <a href="tel:${resa.phone}" class="phone-link">${escapeHtml(resa.phone)}</a>
                    </td>
                    <td>
                        ${resa.remise ? `<span class="remise-badge">${escapeHtml(resa.remise)}</span>` : '<span class="text-muted">-</span>'}
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${statusLabel}</span>
                    </td>
                    <td class="text-end">
                        <div class="action-buttons">
                            ${renderActionButtons(resa)}
                        </div>
                    </td>
                </tr>
            `;
      })
      .join("");

    // Event listeners pour les boutons d'action dans la table
    tbody.querySelectorAll("[data-action]").forEach((btn) => {
      btn.addEventListener("click", function (e) {
        e.stopPropagation();
        const row = this.closest("tr");
        const resaId = parseInt(row.dataset.id);
        const resa = state.reservations.find((r) => r.id === resaId);

        if (this.dataset.action === "view") {
          openDetailModal(resa);
        } else {
          state.selectedReservation = resa;
          handleQuickAction(this.dataset.action, resaId);
        }
      });
    });
  }

  /**
   * Générer les boutons d'action selon le statut
   */
  function renderActionButtons(resa) {
    let buttons = `
            <button class="btn btn-sm btn-outline-secondary" data-action="view" title="Voir détails">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        `;

    switch (resa.status) {
      case "pending":
        // Confirmer ou Refuser
        buttons += `
                    <button class="btn btn-sm btn-success" data-action="confirm" title="Confirmer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-danger" data-action="refuse" title="Refuser">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                `;
        break;

      case "confirmed":
        // Marquer effectuée, absent ou annuler
        buttons += `
                    <button class="btn btn-sm btn-outline-secondary" data-action="complete" title="Effectuée">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" data-action="cancel" title="Annuler">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </button>
                `;
        break;

      // Pour les autres statuts (completed, cancelled, refused, no-show) : seulement voir
      default:
        break;
    }

    return buttons;
  }

  // ========================================
  // MODAL DÉTAILS
  // ========================================

  async function openDetailModal(resa) {
    state.selectedReservation = resa;

    // Charger les détails complets si nécessaire
    try {
      const response = await fetch(`${CONFIG.restUrl}/${resa.id}`, {
        headers: {
          "X-WP-Nonce": CONFIG.nonce,
        },
      });

      if (response.ok) {
        const fullResa = await response.json();
        state.selectedReservation = fullResa;
        resa = fullResa;
      }
    } catch (e) {
      console.warn("Impossible de charger les détails complets", e);
    }

    // Remplir le modal
    document.getElementById("modal-reservation-id").value = resa.id;
    document.getElementById("modal-client").textContent = resa.client_name;
    document.getElementById("modal-email").textContent = resa.email || "-";
    document.getElementById("modal-phone").innerHTML = resa.phone
      ? `<a href="tel:${resa.phone}">${resa.phone}</a>`
      : "-";

    const dateObj = new Date(resa.date);
    const dateStr = dateObj.toLocaleDateString("fr-FR", {
      weekday: "long",
      day: "numeric",
      month: "long",
      year: "numeric",
    });
    document.getElementById("modal-datetime").textContent =
      `${dateStr} à ${resa.time}`;

    document.getElementById("modal-guests").textContent =
      `${resa.guests} personne(s)`;

    const statusLabel = STATUS_LABELS[resa.status] || resa.status;
    const statusClass = STATUS_CLASSES[resa.status] || "";
    document.getElementById("modal-status").innerHTML =
      `<span class="status-badge ${statusClass}">${statusLabel}</span>`;

    document.getElementById("modal-occasion").textContent =
      resa.occasion || "-";
    document.getElementById("modal-allergies").textContent =
      resa.allergies || "-";
    document.getElementById("modal-preferences").textContent =
      resa.preferences || "-";

    const remiseGroup = document.getElementById("modal-remise-group");
    const remiseEl = document.getElementById("modal-remise");
    if (remiseEl && remiseGroup) {
      remiseEl.textContent = resa.remise || "-";
      remiseGroup.style.display = resa.remise ? "" : "none";
    }

    document.getElementById("modal-notes").value = resa.notes || "";

    // Afficher/masquer les boutons selon le statut
    updateModalButtons(resa.status);

    // Ouvrir le modal
    const modal = new bootstrap.Modal(
      document.getElementById("reservationModal"),
    );
    modal.show();
  }

  /**
   * Afficher les bons boutons selon le statut
   */
  function updateModalButtons(status) {
    const btnConfirm = document.getElementById("btn-modal-confirm");
    const btnRefuse = document.getElementById("btn-modal-refuse");
    const btnComplete = document.getElementById("btn-modal-complete");
    const btnCancel = document.getElementById("btn-modal-cancel");
    const btnNoShow = document.getElementById("btn-modal-noshow");

    // Masquer tous par défaut
    [btnConfirm, btnRefuse, btnComplete, btnCancel, btnNoShow].forEach(
      (btn) => {
        if (btn) btn.classList.add("d-none");
      },
    );

    switch (status) {
      case "pending":
        // Peut confirmer ou refuser
        if (btnConfirm) btnConfirm.classList.remove("d-none");
        if (btnRefuse) btnRefuse.classList.remove("d-none");
        break;

      case "confirmed":
        // Peut marquer effectuée, absent ou annuler
        if (btnComplete) btnComplete.classList.remove("d-none");
        if (btnCancel) btnCancel.classList.remove("d-none");
        if (btnNoShow) btnNoShow.classList.remove("d-none");
        break;

      // Pour refused, cancelled, completed, no-show : pas d'actions
      default:
        break;
    }
  }

  // ========================================
  // ACTIONS
  // ========================================

  let pendingAction = null;

  function handleAction(action) {
    if (!state.selectedReservation) return;

    const actionLabels = {
      confirm: "confirmer cette réservation (un email sera envoyé au client)",
      refuse: "refuser cette réservation (un email sera envoyé au client)",
      cancel: "annuler cette réservation",
      complete: "marquer cette réservation comme effectuée",
      "no-show": "marquer ce client comme absent",
    };

    pendingAction = {
      action: action,
      reservationId: state.selectedReservation.id,
    };

    document.getElementById("confirm-action-text").textContent =
      `Êtes-vous sûr de vouloir ${actionLabels[action]} ?`;

    const confirmModal = new bootstrap.Modal(
      document.getElementById("confirmActionModal"),
    );
    confirmModal.show();
  }

  function handleQuickAction(action, reservationId) {
    const actionLabels = {
      confirm: "confirmer cette réservation (un email sera envoyé au client)",
      refuse: "refuser cette réservation (un email sera envoyé au client)",
      cancel: "annuler cette réservation",
      complete: "marquer cette réservation comme effectuée",
      "no-show": "marquer ce client comme absent",
    };

    pendingAction = {
      action: action,
      reservationId: reservationId,
    };

    document.getElementById("confirm-action-text").textContent =
      `Êtes-vous sûr de vouloir ${actionLabels[action]} ?`;

    const confirmModal = new bootstrap.Modal(
      document.getElementById("confirmActionModal"),
    );
    confirmModal.show();
  }

  async function executeConfirmedAction() {
    if (!pendingAction) return;

    const { action, reservationId } = pendingAction;

    // Fermer le modal de confirmation
    bootstrap.Modal.getInstance(
      document.getElementById("confirmActionModal"),
    ).hide();

    // Mapper l'action vers le statut
    const statusMap = {
      confirm: "confirmed",
      refuse: "refused",
      cancel: "cancelled",
      complete: "completed",
      "no-show": "no-show",
    };

    const newStatus = statusMap[action];
    if (!newStatus) return;

    try {
      const response = await fetch(
        `${CONFIG.restUrl}/${reservationId}/status`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": CONFIG.nonce,
          },
          body: JSON.stringify({
            status: newStatus,
            notes: document.getElementById("modal-notes")?.value || "",
          }),
        },
      );

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || "Erreur lors de la mise à jour");
      }

      // Succès - Message approprié selon l'action
      let successMessage = `Réservation ${STATUS_LABELS[newStatus].toLowerCase()} avec succès.`;
      if (action === "confirm" || action === "refuse") {
        successMessage += " Un email a été envoyé au client.";
      }
      showMessage(successMessage, "success");

      // Fermer le modal principal si ouvert
      const mainModal = bootstrap.Modal.getInstance(
        document.getElementById("reservationModal"),
      );
      if (mainModal) mainModal.hide();

      // Recharger les données
      loadReservations();
    } catch (error) {
      console.error("Erreur:", error);
      showMessage(error.message || "Une erreur est survenue.", "danger");
    }

    pendingAction = null;
  }

  // ========================================
  // UTILITAIRES
  // ========================================

  function updateStats(stats) {
    const statPending = document.getElementById("stat-pending");
    const statConfirmed = document.getElementById("stat-confirmed");
    const statRefused = document.getElementById("stat-refused");
    const statToday = document.getElementById("stat-today");
    const statWeek = document.getElementById("stat-week");

    if (statPending) statPending.textContent = stats.pending || 0;
    if (statConfirmed) statConfirmed.textContent = stats.confirmed || 0;
    if (statRefused) statRefused.textContent = stats.refused || 0;
    if (statToday) statToday.textContent = stats.today || 0;
    if (statWeek) statWeek.textContent = stats.week || 0;
  }

  function updateCount() {
    const count = state.reservations.length;
    const countEl = document.getElementById("reservations-count");
    if (countEl) {
      countEl.textContent = `${count} réservation${count > 1 ? "s" : ""} trouvée${count > 1 ? "s" : ""}`;
    }
  }

  function showLoading() {
    const tbody = document.getElementById("reservations-tbody");
    if (tbody) {
      tbody.innerHTML = `
                <tr>
                    <td colspan="${HAS_MULTIPLE ? 8 : 7}" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </td>
                </tr>
            `;
    }
  }

  function showMessage(message, type = "info") {
    const container = document.getElementById("reservations-message");
    if (!container) return;

    container.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        `;

    // Auto-hide après 5 secondes
    setTimeout(() => {
      const alert = container.querySelector(".alert");
      if (alert) {
        alert.classList.remove("show");
        setTimeout(() => (container.innerHTML = ""), 150);
      }
    }, 5000);
  }

  function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
})();
