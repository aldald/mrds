/**
 * MRDS Réservation - Formulaire complet (sans AJAX)
 */

(function () {
  "use strict";

  const CONFIG = window.MRDS_Resa_Config || {
    ajax_url: "/wp-admin/admin-ajax.php",
    nonce: "",
  };

  class MRDSReservationForm {
    constructor() {
      this.form = document.getElementById("mrds-reservation-form");
      if (!this.form) return;

      this.restaurantId = this.form.querySelector(
        'input[name="restaurant_id"]',
      )?.value;
      this.dateInput = document.getElementById("resa-date");
      this.timeSelect = document.getElementById("resa-time");
      this.guestsSelect = document.getElementById("resa-guests");
      this.submitBtn = document.getElementById("btn-reservation");

      // Récap
      this.recapContainer = document.getElementById("form-recap");
      this.recapDate = document.getElementById("recap-date");
      this.recapTime = document.getElementById("recap-time");
      this.recapGuests = document.getElementById("recap-guests");
      this.recapReductionItem = document.getElementById("recap-reduction-item");
      this.recapReduction = document.getElementById("recap-reduction");

      // État réduction (mis à jour dynamiquement à chaque changement de date)
      const initRed = window.MRDS_InitialReduction || {};
      this.hasReduction = initRed.has_reduction ?? false;
      this.reductionText = initRed.reduction_text ?? "";
      this.dateKnown = initRed.date_known ?? false;

      this.init();
    }

    init() {
      this.initDatePicker();
      this.initSelects();
      this.initFormValidation();
      this.applyUrlParams();
      this.updateRecap();
    }

    initDatePicker() {
      if (!this.dateInput || typeof flatpickr === "undefined") return;

      const self = this;
      const initialDate = this.getUrlParam("date");
      const isLocked = this.dateInput.hasAttribute("data-locked");

      this.flatpickr = flatpickr(this.dateInput, {
        locale: "fr",
        dateFormat: "d/m/Y",
        minDate: "today",
        disableMobile: true,
        defaultDate: initialDate || null,
        // Si le champ est verrouillé, on empêche l'ouverture du calendrier
        clickOpens: !isLocked,
        allowInput: false,
        onChange: function (selectedDates, dateStr) {
          if (isLocked) return; // sécurité supplémentaire
          if (selectedDates.length > 0) {
            self.loadAvailableHours(selectedDates[0]);
          }
          self.updateRecap();
        },
      });

      // Si une date initiale, charger les heures
      if (initialDate) {
        const parts = initialDate.split("/");
        if (parts.length === 3) {
          const dateObj = new Date(parts[2], parts[1] - 1, parts[0]);
          this.loadAvailableHours(dateObj);
        }
      }
    }

    async loadAvailableHours(dateObj) {
      const year = dateObj.getFullYear();
      const month = String(dateObj.getMonth() + 1).padStart(2, "0");
      const day = String(dateObj.getDate()).padStart(2, "0");
      const apiDate = `${year}-${month}-${day}`;

      this.timeSelect.disabled = true;
      this.timeSelect.innerHTML = '<option value="">Chargement...</option>';

      try {
        const formData = new FormData();
        formData.append("action", "mrds_get_available_periods");
        formData.append("nonce", CONFIG.nonce);
        formData.append("restaurant_id", this.restaurantId);
        formData.append("date", apiDate);

        const response = await fetch(CONFIG.ajax_url, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });

        const result = await response.json();

        if (result.success && result.data.periods) {
          const periods = result.data.periods;

          // Mettre à jour l'état de la réduction pour cette date
          this.hasReduction = result.data.has_reduction ?? false;
          this.reductionText = result.data.reduction_text ?? "";
          this.dateKnown = true;

          const timeSlots = {
            Matin: [
              "08:00",
              "08:15",
              "08:30",
              "08:45",
              "09:00",
              "09:15",
              "09:30",
              "09:45",
              "10:00",
              "10:15",
              "10:30",
              "10:45",
              "11:00",
              "11:15",
              "11:30",
              "11:45",
            ],
            Midi: [
              "12:00",
              "12:15",
              "12:30",
              "12:45",
              "13:00",
              "13:15",
              "13:30",
              "13:45",
              "14:00",
              "14:15",
              "14:30",
              "14:45",
            ],
            Soir: [
              "19:00",
              "19:15",
              "19:30",
              "19:45",
              "20:00",
              "20:15",
              "20:30",
              "20:45",
              "21:00",
              "21:15",
              "21:30",
              "21:45",
              "22:00",
              "22:15",
              "22:30",
              "22:45",
            ],
          };

          let options = '<option value="">Sélectionner une heure</option>';
          let hasSlots = false;

          for (const [periode, isAvailable] of Object.entries(periods)) {
            if (isAvailable && timeSlots[periode]) {
              hasSlots = true;
              options += `<optgroup label="${periode}">`;
              timeSlots[periode].forEach((slot) => {
                options += `<option value="${slot}">${slot}</option>`;
              });
              options += "</optgroup>";
            }
          }

          if (hasSlots) {
            this.timeSelect.innerHTML = options;
            this.timeSelect.disabled = false;

            const urlHeure = this.getUrlParam("heure");
            if (urlHeure) {
              this.timeSelect.value = urlHeure;
              // Pré-sélectionné mais éditable
            }
          } else {
            this.timeSelect.innerHTML =
              '<option value="">Restaurant fermé ce jour</option>';
          }
        } else {
          this.timeSelect.innerHTML =
            '<option value="">Restaurant fermé ce jour</option>';
        }

        this.updateRecap();
      } catch (error) {
        console.error("Erreur:", error);
        this.timeSelect.innerHTML =
          '<option value="">Erreur de chargement</option>';
      }
    }

    initSelects() {
      const self = this;

      if (this.timeSelect) {
        this.timeSelect.addEventListener("change", () => self.updateRecap());
      }

      if (this.guestsSelect) {
        this.guestsSelect.addEventListener("change", () => self.updateRecap());
      }
    }

    getUrlParam(name) {
      const params = new URLSearchParams(window.location.search);
      return params.get(name) || "";
    }

    applyUrlParams() {
      const personnes = this.getUrlParam("personnes");
      if (personnes && this.guestsSelect) {
        this.guestsSelect.value = personnes;
      }
    }

    updateRecap() {
      const date = this.dateInput?.value || "-";
      const time = this.timeSelect?.value || "-";
      const guests = this.guestsSelect?.value || "-";

      if (this.recapDate) this.recapDate.textContent = date;
      if (this.recapTime) this.recapTime.textContent = time || "-";
      if (this.recapGuests)
        this.recapGuests.textContent =
          guests + " personne" + (guests > 1 ? "s" : "");

      // Mise à jour de la réduction (récap bas + badge en-tête)
      const headerReduction = document.getElementById("header-reduction");
      if (this.recapReduction || headerReduction) {
        if (!this.dateKnown) {
          // Pas encore de date : masquer la ligne du récap bas
          if (this.recapReductionItem)
            this.recapReductionItem.style.display = "none";
        } else {
          if (this.recapReductionItem)
            this.recapReductionItem.style.display = "";

          if (this.hasReduction && this.reductionText) {
            // Récap bas
            if (this.recapReduction) {
              this.recapReduction.textContent = this.reductionText;
              this.recapReduction.classList.remove("no-reduction");
            }
            if (this.recapReductionItem)
              this.recapReductionItem.classList.remove("recap-no-reduction");
            // Badge en-tête
            if (headerReduction) {
              headerReduction.textContent =
                this.reductionText + " de réduction";
              headerReduction.classList.remove("recap-no-reduction");
            }
          } else {
            // Récap bas
            if (this.recapReduction) {
              this.recapReduction.textContent = "Aucune réduction ce jour";
              this.recapReduction.classList.add("no-reduction");
            }
            if (this.recapReductionItem)
              this.recapReductionItem.classList.add("recap-no-reduction");
            // Badge en-tête
            if (headerReduction) {
              headerReduction.textContent = "Aucune réduction ce jour";
              headerReduction.classList.add("recap-no-reduction");
            }
          }
        }
      }

      if (this.recapContainer && date !== "-") {
        this.recapContainer.style.display = "block";
      }
    }

    // Validation simple avant soumission POST
    initFormValidation() {
      if (!this.form) return;

      const self = this;

      this.form.addEventListener("submit", function (e) {
        // Validation basique
        if (!self.dateInput.value) {
          e.preventDefault();
          alert("Veuillez sélectionner une date.");
          return false;
        }

        const hiddenTime = document.getElementById("resa-time-hidden");
        const timeValue =
          self.timeSelect.value || (hiddenTime ? hiddenTime.value : "");
        if (!timeValue) {
          e.preventDefault();
          alert("Veuillez sélectionner une heure.");
          return false;
        }

        // Désactiver le bouton pour éviter double clic
        if (self.submitBtn) {
          self.submitBtn.disabled = true;
          self.submitBtn.innerHTML =
            '<span class="btn-diamond">◆</span> Envoi en cours... <span class="btn-diamond">◆</span>';
        }

        // Laisser le formulaire se soumettre normalement (POST)
        return true;
      });
    }
  }

  // Init
  if (document.readyState === "loading") {
    document.addEventListener(
      "DOMContentLoaded",
      () => new MRDSReservationForm(),
    );
  } else {
    new MRDSReservationForm();
  }
})();
