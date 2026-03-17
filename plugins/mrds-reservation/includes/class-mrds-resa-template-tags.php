<?php
/**
 * Classe MRDS_Resa_Template_Tags
 * 
 * Fonctions helper pour le thème
 * 
 * @package MRDS_Reservation
 * @author Coccinet
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Resa_Template_Tags {

    /**
     * Initialisation automatique des fonctions globales
     */
    public static function init() {
        // Les fonctions sont définies ci-dessous
    }
}

// ========================================
// FONCTIONS GLOBALES POUR LE THÈME
// ========================================

/**
 * Afficher le widget de réservation
 * 
 * @param int $restaurant_id ID du restaurant
 */
function mrds_display_reservation_widget($restaurant_id = null) {
    if (!$restaurant_id) {
        $restaurant_id = get_the_ID();
    }

    echo do_shortcode('[mrds_reservation_widget restaurant_id="' . intval($restaurant_id) . '"]');
}

/**
 * Afficher les réservations à venir
 * 
 * @param int $limit Nombre de réservations à afficher (-1 pour toutes)
 */
function mrds_display_upcoming_reservations($limit = -1) {
    echo do_shortcode('[mrds_upcoming_reservations limit="' . intval($limit) . '"]');
}

/**
 * Afficher les réservations passées
 * 
 * @param int $limit Nombre de réservations à afficher
 */
function mrds_display_past_reservations($limit = 4) {
    echo do_shortcode('[mrds_past_reservations limit="' . intval($limit) . '"]');
}

/**
 * Vérifier si l'utilisateur actuel est membre
 * 
 * @return bool
 */
function mrds_is_current_user_member() {
    if (!is_user_logged_in()) {
        return false;
    }

    $user = wp_get_current_user();
    $allowed_roles = ['customer', 'subscriber', 'administrator'];

    return !empty(array_intersect($allowed_roles, $user->roles));
}

/**
 * Vérifier si un utilisateur peut réserver dans un restaurant
 * 
 * @param int $user_id ID de l'utilisateur (optionnel, utilise l'utilisateur actuel)
 * @param int $restaurant_id ID du restaurant
 * @return bool
 */
function mrds_can_user_book($user_id = null, $restaurant_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id || !$restaurant_id) {
        return false;
    }

    $reservation_service = MRDS_Resa_Reservation::get_instance();
    $can_book = $reservation_service->can_user_book($user_id, $restaurant_id);

    return !is_wp_error($can_book);
}

/**
 * Vérifier si l'utilisateur a déjà réservé dans ce restaurant
 * 
 * @param int $user_id ID de l'utilisateur (optionnel)
 * @param int $restaurant_id ID du restaurant
 * @return bool
 */
function mrds_user_has_booked($user_id = null, $restaurant_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id || !$restaurant_id) {
        return false;
    }

    $reservation_service = MRDS_Resa_Reservation::get_instance();
    return $reservation_service->user_has_booked_restaurant($user_id, $restaurant_id);
}

/**
 * Obtenir l'URL de réservation pour un restaurant
 * 
 * @param int $restaurant_id ID du restaurant
 * @param array $params Paramètres additionnels (date, heure, personnes)
 * @return string
 */
function mrds_get_reservation_url($restaurant_id, $params = []) {
    $page = get_page_by_path('reserver');
    $base_url = $page ? get_permalink($page->ID) : home_url('/reserver/');

    $query_args = array_merge(
        ['restaurant' => $restaurant_id],
        $params
    );

    return add_query_arg($query_args, $base_url);
}

/**
 * Obtenir les réservations à venir d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur (optionnel)
 * @param int $limit Nombre de réservations (-1 pour toutes)
 * @return array
 */
function mrds_get_upcoming_reservations($user_id = null, $limit = -1) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return [];
    }

    $reservation_service = MRDS_Resa_Reservation::get_instance();
    return $reservation_service->get_user_upcoming_reservations($user_id, $limit);
}

/**
 * Obtenir les réservations passées d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur (optionnel)
 * @param int $limit Nombre de réservations
 * @return array
 */
function mrds_get_past_reservations($user_id = null, $limit = -1) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return [];
    }

    $reservation_service = MRDS_Resa_Reservation::get_instance();
    return $reservation_service->get_user_past_reservations($user_id, $limit);
}

/**
 * Compter les réservations à venir d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur (optionnel)
 * @return int
 */
function mrds_count_upcoming_reservations($user_id = null) {
    $reservations = mrds_get_upcoming_reservations($user_id, -1);
    return count($reservations);
}

/**
 * Compter les réservations passées d'un utilisateur
 * 
 * @param int $user_id ID de l'utilisateur (optionnel)
 * @return int
 */
function mrds_count_past_reservations($user_id = null) {
    $reservations = mrds_get_past_reservations($user_id, -1);
    return count($reservations);
}

/**
 * Obtenir le nombre total de restaurants où l'utilisateur peut encore réserver
 * 
 * @param int $user_id ID de l'utilisateur (optionnel)
 * @return int
 */
function mrds_get_available_restaurants_count($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return 0;
    }

    // Total des restaurants
    $total = wp_count_posts('restaurant')->publish;

    // Restaurants déjà réservés cette année
    $one_year_ago = date('Y-m-d', strtotime('-1 year'));

    $booked = get_posts([
        'post_type' => MRDS_Resa_Post_Type::POST_TYPE,
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'fields' => 'ids',
        'meta_query' => [
            'relation' => 'AND',
            ['key' => '_mrds_user_id', 'value' => $user_id],
            ['key' => '_mrds_status', 'value' => MRDS_Resa_Post_Type::STATUS_CANCELLED, 'compare' => '!='],
            ['key' => '_mrds_date', 'value' => $one_year_ago, 'compare' => '>=', 'type' => 'DATE'],
        ],
    ]);

    // Compter les restaurants uniques réservés
    $booked_restaurants = [];
    foreach ($booked as $resa_id) {
        $restaurant_id = get_post_meta($resa_id, '_mrds_restaurant_id', true);
        if ($restaurant_id) {
            $booked_restaurants[$restaurant_id] = true;
        }
    }

    return max(0, $total - count($booked_restaurants));
}

/**
 * Obtenir les périodes disponibles pour un restaurant et une date
 * 
 * @param int $restaurant_id ID du restaurant
 * @param string $date Date au format Y-m-d
 * @return array
 */
function mrds_get_available_periods($restaurant_id, $date) {
    $reservation_service = MRDS_Resa_Reservation::get_instance();
    return $reservation_service->get_available_periods($restaurant_id, $date);
}

/**
 * Obtenir les créneaux horaires pour une période
 * 
 * @param string $periode 'Midi' ou 'Soir'
 * @return array
 */
function mrds_get_time_slots($periode) {
    $reservation_service = MRDS_Resa_Reservation::get_instance();
    return $reservation_service->get_time_slots($periode);
}

/**
 * Obtenir les jours de fermeture d'un restaurant
 * 
 * @param int $restaurant_id ID du restaurant
 * @return array Jours fermés (1-7, lundi-dimanche)
 */
function mrds_get_closed_days($restaurant_id) {
    $reservation_service = MRDS_Resa_Reservation::get_instance();
    return $reservation_service->get_closed_days($restaurant_id);
}

/**
 * Afficher le bloc "Rejoindre le club" pour les non-membres
 * 
 * @param int $restaurant_id ID du restaurant (optionnel, pour contexte)
 */
function mrds_display_join_club_block($restaurant_id = null) {
    ?>
    <div class="mrds-join-club-block">
        <div class="join-club-content">
            <h3><?php _e('Envie de réserver ?', 'mrds-reservation'); ?></h3>
            <p><?php _e('Rejoignez le club et bénéficiez de réductions exclusives dans nos restaurants partenaires.', 'mrds-reservation'); ?></p>
            <a href="<?php echo home_url('/nous-rejoindre/'); ?>" class="my-btn-gold">
                <span class="btn-diamond">◆</span>
                <?php _e('Rejoindre le club', 'mrds-reservation'); ?>
                <span class="btn-diamond">◆</span>
            </a>
        </div>
    </div>
    <?php
}

/**
 * Afficher le message si déjà réservé dans ce restaurant
 * 
 * @param int $restaurant_id ID du restaurant
 */
function mrds_display_already_booked_message($restaurant_id) {
    if (!mrds_user_has_booked(null, $restaurant_id)) {
        return;
    }

    // Trouver la date de la dernière réservation
    $user_id = get_current_user_id();
    $one_year_ago = date('Y-m-d', strtotime('-1 year'));

    $reservations = get_posts([
        'post_type' => MRDS_Resa_Post_Type::POST_TYPE,
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'meta_query' => [
            'relation' => 'AND',
            ['key' => '_mrds_user_id', 'value' => $user_id],
            ['key' => '_mrds_restaurant_id', 'value' => $restaurant_id],
            ['key' => '_mrds_status', 'value' => MRDS_Resa_Post_Type::STATUS_CANCELLED, 'compare' => '!='],
            ['key' => '_mrds_date', 'value' => $one_year_ago, 'compare' => '>=', 'type' => 'DATE'],
        ],
        'meta_key' => '_mrds_date',
        'orderby' => 'meta_value',
        'order' => 'DESC',
    ]);

    if (empty($reservations)) {
        return;
    }

    $resa_date = get_post_meta($reservations[0]->ID, '_mrds_date', true);
    $next_available = date('d/m/Y', strtotime($resa_date . ' +1 year'));
    ?>
    <div class="mrds-already-booked-message">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <p>
            <?php printf(
                __('Vous avez déjà réservé dans ce restaurant le %s. Vous pourrez réserver à nouveau à partir du %s.', 'mrds-reservation'),
                date_i18n('d/m/Y', strtotime($resa_date)),
                $next_available
            ); ?>
        </p>
    </div>
    <?php
}

/**
 * Obtenir le statut formaté d'une réservation
 * 
 * @param string $status Code du statut
 * @return string Label du statut
 */
function mrds_get_status_label($status) {
    $post_type = MRDS_Resa_Post_Type::get_instance();
    return $post_type->get_status_label($status);
}

/**
 * Vérifier si le plugin est actif et fonctionnel
 * 
 * @return bool
 */
function mrds_reservation_is_active() {
    return class_exists('MRDS_Reservation') && class_exists('MRDS_Resa_Reservation');
}