<?php

/**
 * Template : Formulaire de réservation complet
 * 
 * Affiché sur la page /reserver/ via shortcode [mrds_reservation_form]
 * 
 * @package MRDS_Reservation
 * @author Coccinet
 * 
 * Variables disponibles :
 * - $restaurant_id : ID du restaurant
 * - $restaurant : Objet WP_Post du restaurant
 * - $date : Date pré-sélectionnée (Y-m-d)
 * - $time : Heure pré-sélectionnée (H:i)
 * - $guests : Nombre de personnes pré-sélectionné
 */

if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();

// Infos restaurant
$adresse = get_field('adresse', $restaurant_id);
$arrondissement = $adresse['arrondissement'] ?? '';
$location = $arrondissement ? 'Paris ' . $arrondissement . ($arrondissement == 1 ? 'er' : 'e') : ($adresse['ville'] ?? '');
// Vérifier si la remise s'applique au jour de la réservation avant de l'afficher
$reduction = '';
$has_reduction = false;
if (function_exists('mrdstheme_get_restaurant_remise_text')) {
    $remise_applicable = true;
    if (!empty($date) && class_exists('MRDS_Remises_management')) {
        $remises_du_jour = MRDS_Remises_management::get_instance()->get_applicable_remises_for_restaurant($restaurant_id, $date);
        $remise_applicable = !empty($remises_du_jour);
    }
    if ($remise_applicable) {
        $reduction = mrdstheme_get_restaurant_remise_text($restaurant_id);
        $has_reduction = true;
    }
}
$restaurant_image = get_the_post_thumbnail_url($restaurant_id, 'medium');

// Infos utilisateur (pré-remplissage)
$user_phone = get_user_meta($user->ID, 'billing_phone', true);
$user_email = $user->user_email;
?>

<section class="section-reservation">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">

                <!-- En-tête avec récap restaurant -->
                <div class="reservation-header">
                    <div class="reservation-restaurant-recap">
                        <?php if ($restaurant_image) : ?>
                            <div class="recap-image">
                                <img src="<?php echo esc_url($restaurant_image); ?>" alt="<?php echo esc_attr($restaurant->post_title); ?>">
                            </div>
                        <?php endif; ?>
                        <div class="recap-info">
                            <h1 class="recap-title"><?php echo esc_html($restaurant->post_title); ?></h1>
                            <p class="recap-location"><?php echo esc_html($location); ?></p>
                            <?php if ($has_reduction) : ?>
                                <span class="recap-reduction" id="header-reduction">
                                    <?php echo esc_html($reduction); ?> <?php _e('de réduction', 'mrds-reservation'); ?>
                                </span>
                            <?php else : ?>
                                <span class="recap-reduction recap-no-reduction" id="header-reduction">
                                    <?php _e('Aucune réduction ce jour', 'mrds-reservation'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Formulaire -->
                <div class="reservation-form-container">
                    <h2 class="form-title"><?php _e('Finaliser ma réservation', 'mrds-reservation'); ?></h2>

                    <form id="mrds-reservation-form" class="reservation-form" method="post" action="">
                        <input type="hidden" name="mrds_reservation_submit" value="1">
                        <?php wp_nonce_field('mrds_reservation_action', 'mrds_reservation_nonce'); ?>
                        <!-- Nonce + Données cachées -->
                        <input type="hidden" name="restaurant_id" value="<?php echo esc_attr($restaurant_id); ?>">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mrds_resa_nonce'); ?>">

                        <!-- Zone messages -->
                        <div class="form-messages" id="reservation-messages"></div>

                        <!-- Section : Date & Heure -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">1</span>
                                <?php _e('Date et heure', 'mrds-reservation'); ?>
                            </h3>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="resa-date"><?php _e('Date', 'mrds-reservation'); ?> *</label>
                                    <input type="text"
                                        id="resa-date"
                                        name="date"
                                        class="form-date-picker"
                                        value="<?php echo esc_attr($date); ?>"
                                        placeholder="<?php _e('Sélectionner une date', 'mrds-reservation'); ?>"
                                        data-locked="1"
                                        required
                                        readonly>
                                </div>
                                <div class="form-group">
                                    <label for="resa-guests"><?php _e('Nombre de personnes', 'mrds-reservation'); ?> *</label>
                                    <select id="resa-guests" name="guests" required disabled>
                                        <?php for ($i = 1; $i <= 10; $i++) : ?>
                                            <option value="<?php echo $i; ?>" <?php selected($guests, $i); ?>>
                                                <?php echo $i; ?> <?php echo $i > 1 ? __('personnes', 'mrds-reservation') : __('personne', 'mrds-reservation'); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <!-- disabled fields are not submitted, hidden input carries the value -->
                                    <input type="hidden" name="guests" value="<?php echo esc_attr($guests); ?>">
                                </div>
                            </div>

                            <div class="form-row">

                                <div class="form-group">
                                    <label for="resa-time"><?php _e('Heure', 'mrds-reservation'); ?> *</label>
                                    <select id="resa-time" name="time" required disabled>
                                        <option value=""><?php _e('Sélectionnez d\'abord une date', 'mrds-reservation'); ?></option>
                                    </select>
                                </div>

                            </div>

                            <!-- Section : Coordonnées -->
                            <div class="form-section">
                                <h3 class="form-section-title">
                                    <span class="section-number">2</span>
                                    <?php _e('Vos coordonnées', 'mrds-reservation'); ?>
                                </h3>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="resa-lastname"><?php _e('Nom', 'mrds-reservation'); ?> *</label>
                                        <input type="text" id="resa-lastname" name="lastname" value="<?php echo esc_attr($user->last_name); ?>" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="resa-firstname"><?php _e('Prénom', 'mrds-reservation'); ?> *</label>
                                        <input type="text" id="resa-firstname" name="firstname" value="<?php echo esc_attr($user->first_name); ?>" required readonly>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="resa-phone"><?php _e('Téléphone', 'mrds-reservation'); ?> *</label>
                                        <input type="tel" id="resa-phone" name="phone" value="<?php echo esc_attr($user_phone); ?>" placeholder="06 12 34 56 78" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="resa-email"><?php _e('Email', 'mrds-reservation'); ?> *</label>
                                        <input type="email" id="resa-email" name="email" value="<?php echo esc_attr($user_email); ?>" required readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Section : Informations complémentaires (optionnel) -->
                            <div class="form-section form-section-optional">
                                <h3 class="form-section-title">
                                    <span class="section-number">3</span>
                                    <?php _e('Informations complémentaires', 'mrds-reservation'); ?>
                                    <span class="section-optional"><?php _e('(optionnel)', 'mrds-reservation'); ?></span>
                                </h3>

                                <div class="form-group full-width">
                                    <label for="resa-occasion"><?php _e('Occasion spéciale', 'mrds-reservation'); ?></label>
                                    <select id="resa-occasion" name="occasion">
                                        <option value=""><?php _e('Sélectionner une occasion', 'mrds-reservation'); ?></option>
                                        <option value="anniversaire"><?php _e('Anniversaire', 'mrds-reservation'); ?></option>
                                        <option value="anniversaire_mariage"><?php _e('Anniversaire de mariage', 'mrds-reservation'); ?></option>
                                        <option value="saint_valentin"><?php _e('Saint-Valentin', 'mrds-reservation'); ?></option>
                                        <option value="affaires"><?php _e('Repas d\'affaires', 'mrds-reservation'); ?></option>
                                        <option value="autre"><?php _e('Autre célébration', 'mrds-reservation'); ?></option>
                                    </select>
                                </div>

                                <div class="form-group full-width">
                                    <label for="resa-allergies"><?php _e('Allergies ou régimes alimentaires', 'mrds-reservation'); ?></label>
                                    <textarea id="resa-allergies" name="allergies" rows="2" placeholder="<?php _e('Ex : Sans gluten, végétarien, allergie aux fruits de mer...', 'mrds-reservation'); ?>"></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <label for="resa-preferences"><?php _e('Préférences ou demandes particulières', 'mrds-reservation'); ?></label>
                                    <textarea id="resa-preferences" name="preferences" rows="2" placeholder="<?php _e('Ex : Table en terrasse, chaise haute pour enfant, accès PMR...', 'mrds-reservation'); ?>"></textarea>
                                </div>
                            </div>

                            <!-- Récapitulatif -->
                            <div class="form-recap" id="form-recap" style="display: none;">
                                <h4 class="recap-title"><?php _e('Récapitulatif', 'mrds-reservation'); ?></h4>
                                <div class="recap-content">
                                    <div class="recap-item">
                                        <span class="recap-label"><?php _e('Restaurant', 'mrds-reservation'); ?></span>
                                        <span class="recap-value"><?php echo esc_html($restaurant->post_title); ?></span>
                                    </div>
                                    <div class="recap-item">
                                        <span class="recap-label"><?php _e('Date', 'mrds-reservation'); ?></span>
                                        <span class="recap-value" id="recap-date">-</span>
                                    </div>
                                    <div class="recap-item">
                                        <span class="recap-label"><?php _e('Heure', 'mrds-reservation'); ?></span>
                                        <span class="recap-value" id="recap-time">-</span>
                                    </div>
                                    <div class="recap-item">
                                        <span class="recap-label"><?php _e('Personnes', 'mrds-reservation'); ?></span>
                                        <span class="recap-value" id="recap-guests">-</span>
                                    </div>
                                    <div class="recap-item recap-reduction-item" id="recap-reduction-item">
                                        <span class="recap-label"><?php _e('Réduction membre', 'mrds-reservation'); ?></span>
                                        <span class="recap-value" id="recap-reduction">
                                            <?php if ($has_reduction) : ?>
                                                <?php echo esc_html($reduction); ?>
                                            <?php else : ?>
                                                <?php _e('Aucune réduction ce jour', 'mrds-reservation'); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <script>
                                        window.MRDS_InitialReduction = {
                                            has_reduction: <?php echo $has_reduction ? 'true' : 'false'; ?>,
                                            reduction_text: <?php echo wp_json_encode($reduction); ?>,
                                            date_known: <?php echo !empty($date) ? 'true' : 'false'; ?>
                                        };
                                    </script>

                                </div>
                            </div>

                            <!-- Bouton submit -->
                            <div class="form-submit">

                                <?php echo do_shortcode('[mrds_button class="my-btn-gold" text="Confirmer ma réservation" type="submit" id="btn-reservation"]'); ?>
                            </div>

                    </form>
                </div>

                <!-- Panel de succès (caché par défaut) -->
                <div class="reservation-success" id="reservation-success" style="display: none;">

                    <h2 class="success-title"><?php _e('Réservation confirmée !', 'mrds-reservation'); ?></h2>
                    <p class="success-text"><?php _e('Votre réservation a bien été enregistrée. Un email de confirmation vous a été envoyé.', 'mrds-reservation'); ?></p>

                    <div class="success-recap">
                        <div class="success-recap-item">
                            <span class="label"><?php _e('Restaurant', 'mrds-reservation'); ?></span>
                            <span class="value" id="success-restaurant"><?php echo esc_html($restaurant->post_title); ?></span>
                        </div>
                        <div class="success-recap-item">
                            <span class="label"><?php _e('Date', 'mrds-reservation'); ?></span>
                            <span class="value" id="success-date">-</span>
                        </div>
                        <div class="success-recap-item">
                            <span class="label"><?php _e('Heure', 'mrds-reservation'); ?></span>
                            <span class="value" id="success-time">-</span>
                        </div>
                        <div class="success-recap-item">
                            <span class="label"><?php _e('Personnes', 'mrds-reservation'); ?></span>
                            <span class="value" id="success-guests">-</span>
                        </div>
                    </div>

                    <?php
                    echo do_shortcode(sprintf(
                        '[mrds_button class="my-btn-secondary" text="Voir mes réservations" link="/le-carnet-dadresses/" ]',
                    ));
                    ?>
                </div>

            </div>
        </div>
    </div>
</section>