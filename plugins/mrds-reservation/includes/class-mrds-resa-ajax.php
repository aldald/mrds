<?php

/**
 * Classe MRDS_Resa_Ajax
 * 
 * Gère les requêtes AJAX du plugin
 * 
 * @package MRDS_Reservation
 * @author Coccinet
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Resa_Ajax
{

    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Soumettre une réservation (membres connectés uniquement)
        add_action('wp_ajax_mrds_submit_reservation', [$this, 'submit_reservation']);

        // Obtenir les périodes disponibles
        add_action('wp_ajax_mrds_get_available_periods', [$this, 'get_available_periods']);
        add_action('wp_ajax_nopriv_mrds_get_available_periods', [$this, 'get_available_periods']);

        // Obtenir les créneaux horaires
        add_action('wp_ajax_mrds_get_time_slots', [$this, 'get_time_slots']);
        add_action('wp_ajax_nopriv_mrds_get_time_slots', [$this, 'get_time_slots']);

        // Obtenir les jours de fermeture
        add_action('wp_ajax_mrds_get_closed_days', [$this, 'get_closed_days']);
        add_action('wp_ajax_nopriv_mrds_get_closed_days', [$this, 'get_closed_days']);

        // Vérifier si l'utilisateur peut réserver
        add_action('wp_ajax_mrds_check_can_book', [$this, 'check_can_book']);
    }

    /**
     * Vérifier le nonce
     */
    private function verify_nonce()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mrds_resa_nonce')) {
            wp_send_json_error(['message' => __('Session expirée. Veuillez rafraîchir la page.', 'mrds-reservation')]);
        }
    }

    /**
     * Soumettre une réservation
     */
    public function submit_reservation()
    {
        $this->verify_nonce();

        // Vérifier que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Vous devez être connecté pour réserver.', 'mrds-reservation')]);
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        // Vérifier que c'est un membre
        $allowed_roles = ['customer', 'subscriber', 'administrator'];
        if (empty(array_intersect($allowed_roles, $user->roles))) {
            wp_send_json_error(['message' => __('Seuls les membres peuvent effectuer des réservations.', 'mrds-reservation')]);
        }

        // Récupérer et sanitizer les données
        $data = [
            'user_id' => $user_id,
            'restaurant_id' => intval($_POST['restaurant_id'] ?? 0),
            'date' => sanitize_text_field($_POST['date'] ?? ''),
            'time' => sanitize_text_field($_POST['time'] ?? ''),
            'guests' => intval($_POST['guests'] ?? 2),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? $user->user_email),
            'occasion' => sanitize_text_field($_POST['occasion'] ?? ''),
            'allergies' => sanitize_textarea_field($_POST['allergies'] ?? ''),
            'preferences' => sanitize_textarea_field($_POST['preferences'] ?? ''),
        ];

        // Créer la réservation
        $reservation = MRDS_Resa_Reservation::get_instance();
        $result = $reservation->create_reservation($data);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code(),
            ]);
        }

        // Préparer la réponse AVANT les emails
        $restaurant = get_post($data['restaurant_id']);
        $restaurant_name = $restaurant ? $restaurant->post_title : 'Restaurant';

        // Convertir la date pour l'affichage
        $date_display = $data['date'];
        if (strpos($data['date'], '-') !== false) {
            $date_display = date_i18n('l j F Y', strtotime($data['date']));
        }

        $response = [
            'message' => __('Votre réservation a été confirmée !', 'mrds-reservation'),
            'reservation_id' => $result,
            'reservation' => [
                'restaurant' => $restaurant_name,
                'date' => $date_display,
                'time' => $data['time'],
                'guests' => $data['guests'],
            ],
        ];

        // Capturer toute sortie parasite des emails
        ob_start();
        try {
            do_action('mrds_resa_created', $result, $data);
        } catch (Exception $e) {
            error_log('MRDS Email Error: ' . $e->getMessage());
        }
        ob_end_clean();

        // Envoyer la réponse JSON propre
        wp_send_json_success($response);
    }


    /**
     * AJAX : Récupérer les périodes disponibles
     */
    public function get_available_periods()
    {
        check_ajax_referer('mrds_resa_nonce', 'nonce');

        $restaurant_id = intval($_POST['restaurant_id'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? '');

        if (!$restaurant_id || !$date) {
            wp_send_json_error(['message' => 'Paramètres manquants']);
        }

        $reservation = MRDS_Resa_Reservation::get_instance();
        $periods = $reservation->get_available_periods($restaurant_id, $date);

        // S'assurer que les clés sont les noms de période (string)
        $formatted_periods = [];
        foreach ($periods as $periode => $available) {
            $formatted_periods[$periode] = $available;
        }

        // Vérifier si une réduction s'applique pour cette date
        $has_reduction = false;
        $reduction_text = '';
        if (class_exists('MRDS_Remises_management')) {
            $remises_du_jour = MRDS_Remises_management::get_instance()
                ->get_applicable_remises_for_restaurant($restaurant_id, $date);
            if (!empty($remises_du_jour)) {
                if (is_string($remises_du_jour)) {
                    $has_reduction = true;
                    $reduction_text = $remises_du_jour;
                } elseif (is_array($remises_du_jour) || is_object($remises_du_jour)) {
                    $textes = [];
                    foreach ($remises_du_jour as $remise) {
                        $remise_post = is_object($remise) && isset($remise->ID)
                            ? $remise
                            : (is_numeric($remise) ? get_post((int) $remise) : null);
                        if (!$remise_post) continue;
                        $pct = get_field('pourcentage', $remise_post->ID)
                            ?: get_post_meta($remise_post->ID, 'pourcentage', true);
                        $textes[] = $pct ? '-' . $pct . '%' : $remise_post->post_title;
                    }
                    if (!empty($textes)) {
                        $has_reduction = true;
                        $reduction_text = implode(' / ', $textes);
                    }
                }
            }
        }

        wp_send_json_success([
            'periods'        => $formatted_periods,
            'has_reduction'  => $has_reduction,
            'reduction_text' => $reduction_text,
        ]);
    }

    /**
     * AJAX : Récupérer les créneaux horaires
     */
    public function get_time_slots()
    {
        check_ajax_referer('mrds_resa_nonce', 'nonce');

        $periode = sanitize_text_field($_POST['periode'] ?? '');

        if (empty($periode)) {
            wp_send_json_error(['message' => 'Période manquante']);
        }

        $reservation = MRDS_Resa_Reservation::get_instance();
        $slots = $reservation->get_time_slots($periode);

        wp_send_json_success(['slots' => $slots]);
    }

    /**
     * Obtenir les jours de fermeture du restaurant
     */
    public function get_closed_days()
    {
        $this->verify_nonce();

        $restaurant_id = intval($_POST['restaurant_id'] ?? 0);

        if (!$restaurant_id) {
            wp_send_json_error(['message' => __('Restaurant non spécifié.', 'mrds-reservation')]);
        }

        $reservation = MRDS_Resa_Reservation::get_instance();
        $closed_days = $reservation->get_closed_days($restaurant_id);

        wp_send_json_success(['closed_days' => $closed_days]);
    }

    /**
     * Vérifier si l'utilisateur peut réserver dans ce restaurant
     */
    public function check_can_book()
    {
        $this->verify_nonce();

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'can_book' => false,
                'message' => __('Vous devez être connecté pour réserver.', 'mrds-reservation'),
            ]);
        }

        $restaurant_id = intval($_POST['restaurant_id'] ?? 0);

        if (!$restaurant_id) {
            wp_send_json_error(['message' => __('Restaurant non spécifié.', 'mrds-reservation')]);
        }

        $user_id = get_current_user_id();
        $reservation = MRDS_Resa_Reservation::get_instance();
        $can_book = $reservation->can_user_book($user_id, $restaurant_id);

        if (is_wp_error($can_book)) {
            wp_send_json_success([
                'can_book' => false,
                'message' => $can_book->get_error_message(),
            ]);
        }

        wp_send_json_success([
            'can_book' => true,
            'message' => '',
        ]);
    }
}
