/**
 * MRDS Réservation - Widget Inline (Sidebar Single Restaurant)
 * Version DEBUG
 */

(function () {
  "use strict";

  // Configuration
  const CONFIG = window.MRDS_Resa_Config || {
    ajax_url: "/wp-admin/admin-ajax.php",
    nonce: "",
    reservation_page: "/reserver/",
  };

  console.log("MRDS Widget Inline - Config:", CONFIG);

  class MRDSWidgetInline {
    constructor(container) {
      this.container = container;
      this.restaurantId = container.dataset.restaurantId;

      console.log("Widget init pour restaurant:", this.restaurantId);

      // Éléments
      this.dateInput = container.querySelector(".widget-date-picker");
      this.timeSelect = container.querySelector(".widget-time-select");
      this.guestsSelect = container.querySelector(".widget-guests-select");
      this.submitBtn = container.querySelector('[id^="btn-reserver-"]');

      this.selectedDate = null;
      this.closedDays = [];

      this.init();
    }

    async init() {
      // Charger les jours fermés
      await this.loadClosedDays();

      // Initialiser Flatpickr
      this.initDatePicker();

      // Event submit
      if (this.submitBtn) {
        this.submitBtn.addEventListener("click", (e) => this.handleSubmit(e));
      }
    }

    async loadClosedDays() {
      try {
        const formData = new FormData();
        formData.append("action", "mrds_get_closed_days");
        formData.append("nonce", CONFIG.nonce);
        formData.append("restaurant_id", this.restaurantId);

        console.log("Chargement jours fermés...");

        const response = await fetch(CONFIG.ajax_url, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });

        const result = await response.json();
        console.log("Jours fermés résultat:", result);

        if (result.success && result.data.closed_days) {
          this.closedDays = result.data.closed_days;
        }
      } catch (error) {
        console.error("Erreur chargement jours fermés:", error);
      }
    }

    initDatePicker() {
      if (!this.dateInput) {
        console.error("Input date non trouvé");
        return;
      }

      if (typeof flatpickr === "undefined") {
        console.error("Flatpickr non chargé");
        return;
      }

      const self = this;

      this.flatpickr = flatpickr(this.dateInput, {
        locale: "fr",
        dateFormat: "d/m/Y",
        minDate: "today",
        disableMobile: true,
        disable: [
          function (date) {
            let dayOfWeek = date.getDay();
            if (dayOfWeek === 0) dayOfWeek = 7;
            return self.closedDays.includes(dayOfWeek);
          },
        ],
        onChange: function (selectedDates, dateStr) {
          console.log("Date sélectionnée:", dateStr, selectedDates);
          if (selectedDates.length > 0) {
            self.selectedDate = dateStr;
            self.loadTimeSlots(selectedDates[0]);
            self.highlightRemises(selectedDates[0]);
          }
        },
      });

      console.log("Flatpickr initialisé");
    }

    async loadTimeSlots(dateObj) {
      // Formater la date pour l'API (YYYY-MM-DD)
      const year = dateObj.getFullYear();
      const month = String(dateObj.getMonth() + 1).padStart(2, "0");
      const day = String(dateObj.getDate()).padStart(2, "0");
      const apiDate = `${year}-${month}-${day}`;

      console.log("Chargement créneaux pour:", apiDate);

      // Désactiver le select pendant le chargement
      this.timeSelect.disabled = true;
      this.timeSelect.innerHTML = '<option value="">Chargement...</option>';

      try {
        // Récupérer les périodes disponibles
        const formData = new FormData();
        formData.append("action", "mrds_get_available_periods");
        formData.append("nonce", CONFIG.nonce);
        formData.append("restaurant_id", this.restaurantId);
        formData.append("date", apiDate);

        console.log("AJAX get_available_periods:", {
          restaurant_id: this.restaurantId,
          date: apiDate,
          nonce: CONFIG.nonce,
        });

        const response = await fetch(CONFIG.ajax_url, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });

        const result = await response.json();
        console.log("Périodes résultat:", result);

        if (result.success && result.data.periods) {
          const periods = result.data.periods;

          let options = '<option value="">Heure</option>';
          let hasSlots = false;

          // Pour chaque période disponible
          for (const [periode, available] of Object.entries(periods)) {
            console.log(`Période ${periode}:`, available);

            if (available) {
              const slots = await this.getTimeSlotsForPeriod(periode);
              console.log(`Créneaux ${periode}:`, slots);

              if (slots.length > 0) {
                hasSlots = true;
                options += `<optgroup label="${periode}">`;
                slots.forEach((slot) => {
                  options += `<option value="${slot}" data-periode="${periode}">${slot}</option>`;
                });
                options += "</optgroup>";
              }
            }
          }

          if (hasSlots) {
            this.timeSelect.innerHTML = options;
            this.timeSelect.disabled = false;
          } else {
            this.timeSelect.innerHTML =
              '<option value="">Aucun créneau</option>';
          }
        } else {
          console.error("Erreur périodes:", result);
          this.timeSelect.innerHTML = '<option value="">Fermé ce jour</option>';
        }
      } catch (error) {
        console.error("Erreur AJAX:", error);
        this.timeSelect.innerHTML = '<option value="">Erreur</option>';
      }
    }

    async getTimeSlotsForPeriod(periode) {
      try {
        const formData = new FormData();
        formData.append("action", "mrds_get_time_slots");
        formData.append("nonce", CONFIG.nonce);
        formData.append("periode", periode);

        const response = await fetch(CONFIG.ajax_url, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });

        const result = await response.json();
        console.log(`Time slots pour ${periode}:`, result);

        if (result.success && result.data.slots) {
          return result.data.slots;
        }
        return [];
      } catch (error) {
        console.error("Erreur time slots:", error);
        return [];
      }
    }

    highlightRemises(dateObj) {
      const offerBox = this.container.closest(".offer-box");
      if (!offerBox) return;

      const cards = offerBox.querySelectorAll(".remise-card");
      if (!cards.length) return;

      // JS getDay() : 0=dim, 1=lun, ..., 6=sam
      const dayMap = {
        0: "sun",
        1: "mon",
        2: "tue",
        3: "wed",
        4: "thu",
        5: "fri",
        6: "sat",
      };
      const dayCode = dayMap[dateObj.getDay()];
      const dateStr = `${dateObj.getFullYear()}-${String(dateObj.getMonth() + 1).padStart(2, "0")}-${String(dateObj.getDate()).padStart(2, "0")}`;

      cards.forEach((card) => {
        const dateDebut = card.dataset.dateDebut;
        const dateFin = card.dataset.dateFin;
        const jours = JSON.parse(card.dataset.jours || "[]");

        let applicable = true;

        if (jours.length > 0 && !jours.includes(dayCode)) applicable = false;
        if (dateDebut && dateStr < dateDebut) applicable = false;
        if (dateFin && dateStr > dateFin) applicable = false;

        if (applicable) {
          card.classList.add("remise-active");
          card.classList.remove("remise-inactive");
        } else {
          card.classList.remove("remise-active");
          card.classList.add("remise-inactive");
        }
      });
    }

    handleSubmit(e) {
      e.preventDefault();

      const date = this.selectedDate;
      const time = this.timeSelect.value;
      const guests = this.guestsSelect.value;

      console.log("Submit:", { date, time, guests });

      if (!date) {
        alert("Veuillez sélectionner une date");
        return;
      }

      if (!time) {
        alert("Veuillez sélectionner une heure");
        return;
      }

      const params = new URLSearchParams({
        resto_id: this.restaurantId,
        date: date,
        heure: time,
        personnes: guests,
      });

      const url = CONFIG.reservation_page + "?" + params.toString();
      console.log("Redirection vers:", url);

      window.location.href = url;
    }
  }

  // Initialisation
  function initAllWidgets() {
    const widgets = document.querySelectorAll(".reservation-widget-inline");
    console.log("Widgets trouvés:", widgets.length);

    widgets.forEach((widget) => {
      new MRDSWidgetInline(widget);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAllWidgets);
  } else {
    initAllWidgets();
  }

  window.MRDSWidgetInline = MRDSWidgetInline;
})();
