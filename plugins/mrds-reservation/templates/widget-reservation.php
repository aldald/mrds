<?php

/**
 * Template : Widget de réservation
 * 
 * Affiché sur la page single-restaurant
 * 
 * @package MRDS_Reservation
 * @author Coccinet
 * 
 * Variables disponibles :
 * - $restaurant_id : ID du restaurant
 * - $restaurant : Objet WP_Post du restaurant
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_logged_in = is_user_logged_in();
$is_member = mrds_is_current_user_member();
$can_book = $user_logged_in && $is_member ? mrds_can_user_book(null, $restaurant_id) : false;
$has_booked = $user_logged_in && $is_member ? mrds_user_has_booked(null, $restaurant_id) : false;

// Récupérer la réduction du restaurant
$reduction = get_field('reduction', $restaurant_id) ?: '-20%';
?>

<div class="mrds-reservation-widget" data-restaurant-id="<?php echo esc_attr($restaurant_id); ?>">

    <!-- En-tête -->
    <div class="widget-header">
        <h3 class="widget-title"><?php _e('Réserver une table', 'mrds-reservation'); ?></h3>
        <span class="widget-reduction"><?php echo esc_html($reduction); ?></span>
    </div>

    <?php if (!$user_logged_in) : ?>
        <!-- Non connecté -->
        <div class="widget-content widget-login-required">
            <p><?php _e('Connectez-vous pour réserver et profiter de votre réduction membre.', 'mrds-reservation'); ?></p>
            <a href="#acces-membre" class="my-btn-gold widget-btn">
                <span class="btn-diamond">◆</span>
                <?php _e('Se connecter', 'mrds-reservation'); ?>
                <span class="btn-diamond">◆</span>
            </a>
            <p class="widget-join-link">
                <?php _e('Pas encore membre ?', 'mrds-reservation'); ?>
                <a href="<?php echo home_url('/nous-rejoindre/'); ?>"><?php _e('Rejoindre le club', 'mrds-reservation'); ?></a>
            </p>
        </div>

    <?php elseif (!$is_member) : ?>
        <!-- Connecté mais pas membre -->
        <div class="widget-content widget-member-required">
            <p><?php _e('Rejoignez le club pour réserver et bénéficier de réductions exclusives.', 'mrds-reservation'); ?></p>
            <a href="<?php echo home_url('/nous-rejoindre/'); ?>" class="my-btn-gold widget-btn">
                <span class="btn-diamond">◆</span>
                <?php _e('Rejoindre le club', 'mrds-reservation'); ?>
                <span class="btn-diamond">◆</span>
            </a>
        </div>

    <?php elseif ($has_booked) : ?>
        <div class="widget-content widget-already-booked">
            <p class="widget-reduction-text"><?php echo esc_html($reduction); ?></p>
            <p class="widget-already-booked-mention"><em><?php _e('Vous avez déjà réservé dans ce restaurant cette année.', 'mrds-reservation'); ?></em></p>
            <a href="<?php echo home_url('/mes-reservations/'); ?>" class="my-btn-gold widget-btn">
                <span class="btn-diamond">◆</span>
                <?php _e('Voir mes réservations', 'mrds-reservation'); ?>
                <span class="btn-diamond">◆</span>
            </a>
        </div>

    <?php else : ?>
        <!-- Membre peut réserver -->
        <div class="widget-content widget-form">
            <form class="reservation-widget-form" id="mrds-widget-form-<?php echo esc_attr($restaurant_id); ?>">

                <!-- Date -->
                <div class="widget-field">
                    <label for="widget-date-<?php echo esc_attr($restaurant_id); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <?php _e('Date', 'mrds-reservation'); ?>
                    </label>
                    <input type="text"
                        id="widget-date-<?php echo esc_attr($restaurant_id); ?>"
                        class="widget-input widget-date-picker"
                        placeholder="<?php _e('Choisir une date', 'mrds-reservation'); ?>"
                        readonly>
                </div>

                <!-- Période -->
                <div class="widget-field widget-field-periode" style="display: none;">
                    <label>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <?php _e('Période', 'mrds-reservation'); ?>
                    </label>
                    <div class="widget-periode-buttons">
                        <button type="button" class="periode-btn" data-periode="Midi">
                            <?php _e('Midi', 'mrds-reservation'); ?>
                        </button>
                        <button type="button" class="periode-btn" data-periode="Soir">
                            <?php _e('Soir', 'mrds-reservation'); ?>
                        </button>
                    </div>
                </div>

                <!-- Heure -->
                <div class="widget-field widget-field-time" style="display: none;">
                    <label for="widget-time-<?php echo esc_attr($restaurant_id); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <?php _e('Heure', 'mrds-reservation'); ?>
                    </label>
                    <select id="widget-time-<?php echo esc_attr($restaurant_id); ?>" class="widget-input widget-time-select">
                        <option value=""><?php _e('Choisir une heure', 'mrds-reservation'); ?></option>
                    </select>
                </div>

                <!-- Nombre de personnes -->
                <div class="widget-field">
                    <label for="widget-guests-<?php echo esc_attr($restaurant_id); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <?php _e('Personnes', 'mrds-reservation'); ?>
                    </label>
                    <select id="widget-guests-<?php echo esc_attr($restaurant_id); ?>" class="widget-input widget-guests-select">
                        <?php for ($i = 1; $i <= 10; $i++) : ?>
                            <option value="<?php echo $i; ?>" <?php selected($i, 2); ?>>
                                <?php echo $i; ?> <?php echo $i > 1 ? __('personnes', 'mrds-reservation') : __('personne', 'mrds-reservation'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Message fermé -->
                <div class="widget-closed-message" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php _e('Le restaurant est fermé ce jour', 'mrds-reservation'); ?></span>
                </div>

                <!-- Bouton Réserver -->
                <button type="submit" class="my-btn-gold widget-submit-btn" disabled>
                    <span class="btn-diamond">◆</span>
                    <?php _e('Réserver', 'mrds-reservation'); ?>
                    <span class="btn-diamond">◆</span>
                </button>

            </form>
        </div>
    <?php endif; ?>

</div>