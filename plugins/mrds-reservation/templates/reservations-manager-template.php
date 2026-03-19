<?php

/**
 * Template: Gestion des Réservations - Restaurateur
 * 
 * Affiche les réservations du restaurant avec possibilité de confirmer/refuser
 * 
 * Variables disponibles :
 * - $restaurant_id : ID du restaurant actif
 * - $restaurant : Objet WP_Post du restaurant actif
 * - $all_restaurants : Liste de tous les restaurants gérés par l'utilisateur
 * 
 * @package MRDS_Reservation
 */

if (!defined('ABSPATH')) {
    exit;
}

// Sécuriser les variables
$all_restaurants = isset($all_restaurants) ? $all_restaurants : [];
$restaurant = isset($restaurant) ? $restaurant : null;
$restaurant_id = isset($restaurant_id) ? $restaurant_id : 0;
$is_all_mode = isset($is_all_mode) ? $is_all_mode : false;
$restaurant_name = $is_all_mode ? 'Tous les restaurants' : ($restaurant ? $restaurant->post_title : 'Restaurant');
$has_multiple = count($all_restaurants) > 1;
?>

<div id="mrds-reservations-app" class="container my-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Gestion des réservations</h1>

            <?php if ($has_multiple) : ?>
                <!-- Sélecteur de restaurant (si plusieurs) -->
                <div class="restaurant-selector mt-2">
                    <label for="select-restaurant" class="form-label mb-1 text-muted small">Restaurant :</label>
                    <select id="select-restaurant" class="form-select form-select-sm" style="max-width: 300px;">
                        <option value="0" <?php selected($is_all_mode, true); ?>>
                            — Tous les restaurants —
                        </option>
                        <?php foreach ($all_restaurants as $rest) : ?>
                            <option value="<?php echo esc_attr($rest->ID); ?>" <?php selected(!$is_all_mode && $rest->ID === $restaurant_id); ?>>
                                <?php echo esc_html($rest->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else : ?>
                <p class="text-muted mb-0">
                    <?php echo esc_html($restaurant_name); ?> —
                    <span id="reservations-count">Chargement...</span>
                </p>
            <?php endif; ?>
        </div>
        <div class="header-actions">
            <button type="button" id="btn-refresh" class="btn btn-outline-secondary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6"></path>
                    <path d="M1 20v-6h6"></path>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                Actualiser
            </button>
        </div>
    </div>

    <!-- Info restaurant sélectionné (si plusieurs) -->
    <?php if ($has_multiple) : ?>
        <p class="text-muted mb-3">
            <span id="reservations-count">Chargement...</span>
        </p>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter-status" class="form-label">Statut</label>
                    <select id="filter-status" class="form-select">
                        <option value="" selected>Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="confirmed">Confirmées</option>
                        <option value="refused">Refusées</option>
                        <option value="cancelled">Annulées</option>
                        <option value="completed">Effectuées</option>
                        <option value="no-show">Absents</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter-date-from" class="form-label">Du</label>
                    <input type="text" id="filter-date-from" class="form-control filter-date-picker"
                        value=""
                        placeholder="jj/mm/aaaa" readonly>
                </div>
                <div class="col-md-3">
                    <label for="filter-date-to" class="form-label">Au</label>

                    <input type="text" id="filter-date-to" class="form-control filter-date-picker"
                        value=""
                        placeholder="jj/mm/aaaa" readonly>
                </div>
                <div class="col-md-3">
                    <button type="button" id="btn-filter" class="btn btn-primary w-100">
                        Filtrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats rapides -->
    <div class="row mb-4">
        <div class="col-6 col-md-2">
            <div class="stat-card stat-pending">
                <span class="stat-number" id="stat-pending">0</span>
                <span class="stat-label">En attente</span>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card stat-confirmed">
                <span class="stat-number" id="stat-confirmed">0</span>
                <span class="stat-label">Confirmées</span>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card stat-refused">
                <span class="stat-number" id="stat-refused">0</span>
                <span class="stat-label">Refusées</span>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card stat-today">
                <span class="stat-number" id="stat-today">0</span>
                <span class="stat-label">Aujourd'hui</span>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card stat-week">
                <span class="stat-number" id="stat-week">0</span>
                <span class="stat-label">Cette semaine</span>
            </div>
        </div>
    </div>

    <!-- Liste des réservations -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Réservations</h5>
        </div>

        <!-- Vue Liste -->
        <div class="card-body p-0" id="view-list">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Client</th>
                            <?php if ($has_multiple) : ?>
                                <th>Restaurant</th>
                            <?php endif; ?>
                            <th>Couverts</th>
                            <th>Téléphone</th>
                            <th>Remise</th>

                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reservations-tbody">
                        <tr>
                            <td colspan="<?php echo $has_multiple ? 9 : 8; ?>" class="text-center py-4">

                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Message zone -->
    <div id="reservations-message" class="mt-3"></div>

</div>

<!-- Modal Détails Réservation -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reservationModalLabel">Détails de la réservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-group">
                            <label>Client</label>
                            <p id="modal-client" class="detail-value">-</p>
                        </div>
                        <div class="detail-group">
                            <label>Email</label>
                            <p id="modal-email" class="detail-value">-</p>
                        </div>
                        <div class="detail-group">
                            <label>Téléphone</label>
                            <p id="modal-phone" class="detail-value">-</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-group">
                            <label>Date & Heure</label>
                            <p id="modal-datetime" class="detail-value">-</p>
                        </div>
                        <div class="detail-group">
                            <label>Nombre de couverts</label>
                            <p id="modal-guests" class="detail-value">-</p>
                        </div>
                        <div class="detail-group">
                            <label>Statut</label>
                            <p id="modal-status" class="detail-value">-</p>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <div class="detail-group">
                            <label>Occasion</label>
                            <p id="modal-occasion" class="detail-value">-</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-group">
                            <label>Allergies</label>
                            <p id="modal-allergies" class="detail-value">-</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-group">
                            <label>Préférences</label>
                            <p id="modal-preferences" class="detail-value">-</p>
                        </div>
                    </div>
                </div>

                <!-- Remise -->
                <div class="row mt-2" id="modal-remise-group">
                    <div class="col-md-12">
                        <div class="detail-group">
                            <label>Remise accordée</label>
                            <p id="modal-remise" class="detail-value">-</p>
                        </div>
                    </div>
                </div>

                <!-- Notes restaurateur -->
                <div class="mt-3">
                    <label for="modal-notes" class="form-label">Notes internes</label>
                    <textarea id="modal-notes" class="form-control" rows="2" placeholder="Ajouter une note..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="modal-reservation-id">
                <div class="modal-actions-left">
                    <!-- Bouton Refuser (pour pending) -->
                    <button type="button" class="btn btn-outline-danger d-none" id="btn-modal-refuse" data-action="refuse">
                        Refuser
                    </button>
                    <!-- Bouton Annuler (pour confirmed) -->
                    <button type="button" class="btn btn-outline-secondary d-none" id="btn-modal-cancel" data-action="cancel">
                        Annuler
                    </button>
                    <!-- Bouton Absent -->
                    <button type="button" class="btn btn-outline-warning d-none" id="btn-modal-noshow" data-action="no-show">
                        Absent
                    </button>
                </div>
                <div class="modal-actions-right">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Fermer
                    </button>
                    <!-- Bouton Confirmer (pour pending) -->
                    <button type="button" class="btn btn-success d-none" id="btn-modal-confirm" data-action="confirm">
                        Confirmer
                    </button>
                    <!-- Bouton Effectuée (pour confirmed) -->
                    <button type="button" class="btn btn-primary d-none" id="btn-modal-complete" data-action="complete">
                        Marquer effectuée
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation Action -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p id="confirm-action-text">Êtes-vous sûr de vouloir effectuer cette action ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Non</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-action">Oui</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Changement de restaurant
    document.addEventListener('DOMContentLoaded', function() {
        const selectRestaurant = document.getElementById('select-restaurant');
        if (selectRestaurant) {
            selectRestaurant.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('restaurant_id', this.value);
                window.location.href = url.toString();
            });
        }
    });

    // Initialiser Flatpickr pour les filtres de date
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof flatpickr !== 'undefined') {
            flatpickr('#filter-date-from', {
                locale: 'fr',
                dateFormat: 'd/m/Y',
                defaultDate: null
            });

            flatpickr('#filter-date-to', {
                locale: 'fr',
                dateFormat: 'd/m/Y',
                defaultDate: null
            });
        }
    });
</script>