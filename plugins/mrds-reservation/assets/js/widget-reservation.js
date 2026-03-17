/**
 * MRDS Réservation - Widget JavaScript
 * 
 * Gère le widget de réservation sur la page single-restaurant
 * 
 * @package MRDS_Reservation
 * @author Coccinet
 */

(function($) {
    'use strict';

    // Configuration
    const CONFIG = window.MRDS_Resa_Config || {
        ajax_url: '/wp-admin/admin-ajax.php',
        nonce: '',
        reservation_page: '/reserver/',
        i18n: {}
    };

    /**
     * Classe Widget Reservation
     */
    class MRDSReservationWidget {
        
        constructor(container) {
            this.$container = $(container);
            this.restaurantId = this.$container.data('restaurant-id');
            this.selectedDate = null;
            this.selectedPeriode = null;
            this.selectedTime = null;
            this.closedDays = [];
            
            this.init();
        }

        init() {
            this.cacheElements();
            this.bindEvents();
            this.loadClosedDays();
        }

        cacheElements() {
            this.$form = this.$container.find('.reservation-widget-form');
            this.$dateInput = this.$container.find('.widget-date-picker');
            this.$periodeField = this.$container.find('.widget-field-periode');
            this.$periodeBtns = this.$container.find('.periode-btn');
            this.$timeField = this.$container.find('.widget-field-time');
            this.$timeSelect = this.$container.find('.widget-time-select');
            this.$guestsSelect = this.$container.find('.widget-guests-select');
            this.$submitBtn = this.$container.find('.widget-submit-btn');
            this.$closedMessage = this.$container.find('.widget-closed-message');
        }

        bindEvents() {
            // Sélection de période
            this.$periodeBtns.on('click', (e) => this.onPeriodeClick(e));
            
            // Changement d'heure
            this.$timeSelect.on('change', () => this.onTimeChange());
            
            // Soumission du formulaire
            this.$form.on('submit', (e) => this.onSubmit(e));
        }

        /**
         * Charger les jours de fermeture du restaurant
         */
        loadClosedDays() {
            $.ajax({
                url: CONFIG.ajax_url,
                type: 'POST',
                data: {
                    action: 'mrds_get_closed_days',
                    nonce: CONFIG.nonce,
                    restaurant_id: this.restaurantId
                },
                success: (response) => {
                    if (response.success) {
                        this.closedDays = response.data.closed_days || [];
                        this.initDatePicker();
                    }
                },
                error: () => {
                    this.initDatePicker();
                }
            });
        }

        /**
         * Initialiser Flatpickr
         */
        initDatePicker() {
            const self = this;
            
            // Convertir jours fermés (1-7) en jours Flatpickr (0-6, dimanche = 0)
            const disabledDays = this.closedDays.map(day => {
                return day === 7 ? 0 : day; // Dimanche : 7 -> 0
            });

            this.flatpickr = flatpickr(this.$dateInput[0], {
                locale: 'fr',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'l j F Y',
                minDate: 'today',
                disableMobile: true,
                disable: [
                    function(date) {
                        // Désactiver les jours de fermeture
                        const dayOfWeek = date.getDay(); // 0 = dimanche
                        return disabledDays.includes(dayOfWeek);
                    }
                ],
                onChange: (selectedDates, dateStr) => {
                    self.onDateChange(dateStr);
                }
            });
        }

        /**
         * Changement de date
         */
        onDateChange(dateStr) {
            this.selectedDate = dateStr;
            this.selectedPeriode = null;
            this.selectedTime = null;
            
            // Reset
            this.$periodeBtns.removeClass('active');
            this.$timeSelect.html('<option value="">' + (CONFIG.i18n.select_time || 'Choisir une heure') + '</option>');
            this.$timeField.hide();
            this.$closedMessage.hide();
            this.updateSubmitButton();

            if (!dateStr) {
                this.$periodeField.hide();
                return;
            }

            // Charger les périodes disponibles
            this.loadAvailablePeriods(dateStr);
        }

        /**
         * Charger les périodes disponibles
         */
        loadAvailablePeriods(date) {
            $.ajax({
                url: CONFIG.ajax_url,
                type: 'POST',
                data: {
                    action: 'mrds_get_available_periods',
                    nonce: CONFIG.nonce,
                    restaurant_id: this.restaurantId,
                    date: date
                },
                success: (response) => {
                    if (response.success) {
                        const periods = response.data.periods || [];
                        
                        if (periods.length === 0) {
                            // Restaurant fermé ce jour
                            this.$periodeField.hide();
                            this.$closedMessage.show();
                        } else {
                            // Afficher les périodes disponibles
                            this.$closedMessage.hide();
                            this.$periodeBtns.each(function() {
                                const periode = $(this).data('periode');
                                if (periods.includes(periode)) {
                                    $(this).removeClass('disabled').prop('disabled', false);
                                } else {
                                    $(this).addClass('disabled').prop('disabled', true);
                                }
                            });
                            this.$periodeField.show();
                        }
                    }
                },
                error: () => {
                    this.$periodeField.show();
                }
            });
        }

        /**
         * Clic sur une période
         */
        onPeriodeClick(e) {
            const $btn = $(e.currentTarget);
            
            if ($btn.hasClass('disabled')) {
                return;
            }

            this.$periodeBtns.removeClass('active');
            $btn.addClass('active');
            
            this.selectedPeriode = $btn.data('periode');
            this.selectedTime = null;
            
            // Charger les créneaux horaires
            this.loadTimeSlots(this.selectedPeriode);
        }

        /**
         * Charger les créneaux horaires
         */
        loadTimeSlots(periode) {
            this.$timeSelect.html('<option value="">' + (CONFIG.i18n.loading || 'Chargement...') + '</option>');
            this.$timeField.show();

            $.ajax({
                url: CONFIG.ajax_url,
                type: 'POST',
                data: {
                    action: 'mrds_get_time_slots',
                    nonce: CONFIG.nonce,
                    periode: periode
                },
                success: (response) => {
                    if (response.success) {
                        const slots = response.data.slots || [];
                        let html = '<option value="">' + (CONFIG.i18n.select_time || 'Choisir une heure') + '</option>';
                        
                        slots.forEach(slot => {
                            const formatted = slot.replace(':', 'h');
                            html += '<option value="' + slot + '">' + formatted + '</option>';
                        });
                        
                        this.$timeSelect.html(html);
                    }
                },
                error: () => {
                    this.$timeSelect.html('<option value="">' + (CONFIG.i18n.error || 'Erreur') + '</option>');
                }
            });
        }

        /**
         * Changement d'heure
         */
        onTimeChange() {
            this.selectedTime = this.$timeSelect.val();
            this.updateSubmitButton();
        }

        /**
         * Mettre à jour le bouton submit
         */
        updateSubmitButton() {
            const isValid = this.selectedDate && this.selectedPeriode && this.selectedTime;
            this.$submitBtn.prop('disabled', !isValid);
        }

        /**
         * Soumission du formulaire
         */
        onSubmit(e) {
            e.preventDefault();

            if (!this.selectedDate || !this.selectedTime) {
                return;
            }

            const guests = this.$guestsSelect.val();

            // Construire l'URL de redirection
            const params = new URLSearchParams({
                restaurant: this.restaurantId,
                date: this.selectedDate,
                heure: this.selectedTime,
                personnes: guests
            });

            // Rediriger vers la page de réservation
            window.location.href = CONFIG.reservation_page + '?' + params.toString();
        }
    }

    /**
     * Initialisation au chargement
     */
    $(document).ready(function() {
        $('.mrds-reservation-widget').each(function() {
            new MRDSReservationWidget(this);
        });
    });

})(jQuery);