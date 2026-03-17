<?php

/**
 * Classe MRDS_Resa_Shortcodes
 * 
 * Gère les shortcodes du plugin
 * 
 * @package MRDS_Reservation
 * @author Coccinet
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Resa_Shortcodes
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
        // Formulaire de réservation complet
        add_shortcode('mrds_reservation_form', [$this, 'reservation_form']);

        // Widget de réservation (pour single restaurant)
        add_shortcode('mrds_reservation_widget', [$this, 'reservation_widget']);

        // Liste des réservations à venir
        add_shortcode('mrds_upcoming_reservations', [$this, 'upcoming_reservations']);

        // Liste des réservations passées
        add_shortcode('mrds_past_reservations', [$this, 'past_reservations']);

        // Message de succès après réservation
        add_shortcode('mrds_reservation_success_message', [$this, 'reservation_success_message']);
    }

    /**
     * Shortcode : Formulaire de réservation complet
     * 
     * Usage : [mrds_reservation_form]
     */
    public function reservation_form($atts)
    {
        // Démarrer la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ========================================
        // TRAITEMENT DU FORMULAIRE POST
        // ========================================
        if (isset($_POST['mrds_reservation_submit']) && $_POST['mrds_reservation_submit'] == '1') {

            // Vérifier nonce
            if (!wp_verify_nonce($_POST['mrds_reservation_nonce'] ?? '', 'mrds_reservation_action')) {
                return $this->render_error('Session expirée. Veuillez rafraîchir la page.');
            }

            // Vérifier connexion
            if (!is_user_logged_in()) {
                return $this->render_login_required();
            }

            $user_id = get_current_user_id();
            $user = get_userdata($user_id);

            // Vérifier rôle membre
            $allowed_roles = ['customer', 'subscriber', 'administrator'];
            if (empty(array_intersect($allowed_roles, $user->roles))) {
                return $this->render_member_required();
            }

            // Préparer les données
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
                return $this->render_error($result->get_error_message());
            }

            // Succès ! Rediriger vers la page mes-reservations
            $_SESSION['mrds_reservation_success'] = true;
            $_SESSION['mrds_reservation_restaurant'] = get_post($data['restaurant_id'])->post_title ?? 'Restaurant';

            wp_redirect(home_url('/mes-reservations/'));
            exit;
        }

        // ========================================
        // AFFICHAGE DU FORMULAIRE
        // ========================================

        // Vérifier si l'utilisateur est connecté
        if (!is_user_logged_in()) {
            return $this->render_login_required();
        }

        // Vérifier si c'est un membre
        $user = wp_get_current_user();
        $allowed_roles = ['customer', 'subscriber', 'administrator'];
        if (empty(array_intersect($allowed_roles, $user->roles))) {
            return $this->render_member_required();
        }

        // Récupérer les paramètres GET
        $restaurant_id = intval($_GET['resto_id'] ?? 0);
        $date = sanitize_text_field($_GET['date'] ?? '');
        $time = sanitize_text_field($_GET['heure'] ?? '');
        $guests = intval($_GET['personnes'] ?? 2);

        // Vérifier que le restaurant existe
        if (!$restaurant_id) {
            return $this->render_error(__('Aucun restaurant sélectionné.', 'mrds-reservation'));
        }

        $restaurant = get_post($restaurant_id);
        if (!$restaurant || $restaurant->post_type !== 'restaurant') {
            return $this->render_error(__('Restaurant non trouvé.', 'mrds-reservation'));
        }

        // Vérifier la règle 1/an
        // $reservation_service = MRDS_Resa_Reservation::get_instance();
        // $can_book = $reservation_service->can_user_book(get_current_user_id(), $restaurant_id);
        // if (is_wp_error($can_book)) {
        //     return $this->render_error($can_book->get_error_message());
        // }

        // Charger le template
        ob_start();
        include MRDS_RESA_PLUGIN_DIR . 'templates/form-reservation.php';
        return ob_get_clean();
    }

    /**
     * Afficher le message de succès
     */
    private function render_success($reservation)
    {
        // Formater la date
        $date_display = $reservation['date'];
        if (strpos($reservation['date'], '-') !== false) {
            $date_display = date_i18n('l j F Y', strtotime($reservation['date']));
        } elseif (strpos($reservation['date'], '/') !== false) {
            $parts = explode('/', $reservation['date']);
            if (count($parts) === 3) {
                $date_display = date_i18n('l j F Y', strtotime($parts[2] . '-' . $parts[1] . '-' . $parts[0]));
            }
        }

        ob_start();
?>
        <div class="reservation-success">

            <h2><?php _e('Réservation confirmée !', 'mrds-reservation'); ?></h2>

            <div class="success-details">
                <p><strong><?php _e('Restaurant', 'mrds-reservation'); ?> :</strong> <?php echo esc_html($reservation['restaurant']); ?></p>
                <p><strong><?php _e('Date', 'mrds-reservation'); ?> :</strong> <?php echo esc_html($date_display); ?></p>
                <p><strong><?php _e('Heure', 'mrds-reservation'); ?> :</strong> <?php echo esc_html($reservation['time']); ?></p>
                <p><strong><?php _e('Personnes', 'mrds-reservation'); ?> :</strong> <?php echo esc_html($reservation['guests']); ?></p>
            </div>

            <p><?php _e('Vous allez recevoir un email de confirmation.', 'mrds-reservation'); ?></p>

            <?php
            echo do_shortcode(sprintf(
                '[mrds_button class="my-btn-secondary" text="Voir mes réservations" link="/le-carnet-dadresses/" ]',
            ));
            ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Shortcode : Widget de réservation
     * 
     * Usage : [mrds_reservation_widget restaurant_id="123"]
     */
    public function reservation_widget($atts)
    {
        $atts = shortcode_atts([
            'restaurant_id' => get_the_ID(),
        ], $atts, 'mrds_reservation_widget');

        $restaurant_id = intval($atts['restaurant_id']);

        if (!$restaurant_id) {
            return '';
        }

        $restaurant = get_post($restaurant_id);
        if (!$restaurant || $restaurant->post_type !== 'restaurant') {
            return '';
        }

        // Charger le template
        ob_start();
        include MRDS_RESA_PLUGIN_DIR . 'templates/widget-reservation.php';
        return ob_get_clean();
    }

    /**
     * Shortcode : Réservations à venir
     * 
     * Usage : [mrds_upcoming_reservations limit="5"]
     */
    public function upcoming_reservations($atts)
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'limit' => -1,
        ], $atts, 'mrds_upcoming_reservations');

        $user_id = get_current_user_id();
        $reservation_service = MRDS_Resa_Reservation::get_instance();
        $reservations = $reservation_service->get_user_upcoming_reservations($user_id, intval($atts['limit']));

        if (empty($reservations)) {
            return '<p class="mrds-no-reservations">' . __('Aucune réservation à venir.', 'mrds-reservation') . '</p>';
        }

        ob_start();
    ?>
        <div class="mrds-reservations-list mrds-upcoming">
            <?php foreach ($reservations as $resa) : ?>
                <div class="restaurant-card-horizontal">
                    <div class="card-image">
                        <?php if ($resa['restaurant_image']) : ?>
                            <img src="<?php echo esc_url($resa['restaurant_image']); ?>" alt="<?php echo esc_attr($resa['restaurant_name']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title">
                            <a href="<?php echo esc_url($resa['restaurant_link']); ?>"><?php echo esc_html($resa['restaurant_name']); ?></a>
                        </h3>
                        <p class="card-location"><?php echo esc_html($resa['location']); ?></p>

                        <?php if (!empty($resa['tags'])) : ?>
                            <div class="card-tags">
                                <?php foreach ($resa['tags'] as $tag) : ?>
                                    <span class="tag"><?php echo esc_html($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="reservation-infos">
                            <span class="resa-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <?php echo esc_html($resa['date_formatted']); ?>
                            </span>
                            <span class="resa-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?php echo esc_html($resa['time_formatted']); ?>
                            </span>
                            <span class="resa-info">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <?php echo esc_html($resa['guests']); ?> <?php _e('couverts', 'mrds-reservation'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Shortcode : Réservations passées
     * 
     * Usage : [mrds_past_reservations limit="4"]
     */
    public function past_reservations($atts)
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $atts = shortcode_atts([
            'limit' => 4,
        ], $atts, 'mrds_past_reservations');

        $user_id = get_current_user_id();
        $reservation_service = MRDS_Resa_Reservation::get_instance();
        $reservations = $reservation_service->get_user_past_reservations($user_id, intval($atts['limit']));

        if (empty($reservations)) {
            return '<p class="mrds-no-reservations">' . __('Aucune réservation passée.', 'mrds-reservation') . '</p>';
        }

        ob_start();
    ?>
        <div class="mrds-reservations-list mrds-past">
            <div class="row">
                <?php foreach ($reservations as $resa) : ?>
                    <div class="col-12 col-md-6 col-lg-3 mb-4">
                        <div class="restaurant-card">
                            <div class="card-image">
                                <?php if ($resa['restaurant_image']) : ?>
                                    <img src="<?php echo esc_url($resa['restaurant_image']); ?>" alt="<?php echo esc_attr($resa['restaurant_name']); ?>">
                                <?php endif; ?>
                                <a href="<?php echo esc_url($resa['restaurant_link']); ?>" class="card-arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35">
                                        <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" fill="#fff" />
                                    </svg>
                                </a>
                            </div>
                            <div class="card-content">
                                <h3 class="card-title">
                                    <a href="<?php echo esc_url($resa['restaurant_link']); ?>"><?php echo esc_html($resa['restaurant_name']); ?></a>
                                </h3>
                                <p class="card-location"><?php echo esc_html($resa['location']); ?></p>

                                <?php if (!empty($resa['tags'])) : ?>
                                    <div class="card-tags">
                                        <?php foreach (array_slice($resa['tags'], 0, 3) as $tag) : ?>
                                            <span class="tag"><?php echo esc_html($tag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($resa['citation']) : ?>
                                    <blockquote class="card-quote">
                                        « <?php echo esc_html($resa['citation']); ?> »
                                    </blockquote>
                                    <?php if ($resa['citation_auteur']) : ?>
                                        <p class="card-chef">— <?php echo esc_html($resa['citation_auteur']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }


    /**
     * Shortcode : Message de succès après réservation
     * 
     * Usage : [mrds_reservation_success_message]
     */
    public function reservation_success_message($atts)
    {
        // Démarrer la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérifier s'il y a un message de succès en session
        if (empty($_SESSION['mrds_reservation_success'])) {
            return '';
        }

        $restaurant = $_SESSION['mrds_reservation_restaurant'] ?? 'le restaurant';

        // Supprimer les données de session (affichage unique)
        unset($_SESSION['mrds_reservation_success'], $_SESSION['mrds_reservation_restaurant']);

        ob_start();
    ?>
        <div class="mrds-message mrds-message-success">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h3><?php _e('Réservation enregistrée !', 'mrds-reservation'); ?></h3>
            <p><?php printf(__('Votre demande de réservation chez <strong>%s</strong> a bien été envoyée.', 'mrds-reservation'), esc_html($restaurant)); ?></p>
            <p class="success-notice"><?php _e('Elle est en attente de confirmation par le restaurant. Vous recevrez un email dès qu\'elle sera validée.', 'mrds-reservation'); ?></p>
        </div>
    <?php
        return ob_get_clean();
    }


    /**
     * Afficher message connexion requise
     */
    private function render_login_required()
    {
        ob_start();
    ?>
        <div class="mrds-message mrds-message-warning">
            <h3><?php _e('Connexion requise', 'mrds-reservation'); ?></h3>
            <p><?php _e('Vous devez être connecté pour effectuer une réservation.', 'mrds-reservation'); ?></p>
            <a href="#acces-membre" class="my-btn-gold">
                <span class="btn-diamond">◆</span>
                <?php _e('Se connecter', 'mrds-reservation'); ?>
                <span class="btn-diamond">◆</span>
            </a>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Afficher message membre requis
     */
    private function render_member_required()
    {
        ob_start();
    ?>
        <div class="mrds-message mrds-message-warning">
            <h3><?php _e('Réservé aux membres', 'mrds-reservation'); ?></h3>
            <p><?php _e('Seuls les membres du club peuvent effectuer des réservations.', 'mrds-reservation'); ?></p>
            <a href="<?php echo home_url('/nous-rejoindre/'); ?>" class="my-btn-gold">
                <span class="btn-diamond">◆</span>
                <?php _e('Rejoindre le club', 'mrds-reservation'); ?>
                <span class="btn-diamond">◆</span>
            </a>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Afficher message d'erreur
     */
    private function render_error($message)
    {
        ob_start();
    ?>
        <div class="mrds-message mrds-message-error">
            <h3><?php _e('Réservation impossible', 'mrds-reservation'); ?></h3>
            <p><?php echo esc_html($message); ?></p>
            <a href="<?php echo home_url('/le-carnet-dadresses/'); ?>" class="my-btn-fourth">
                <span class="btn-text"><?php _e('Voir tous les restaurants', 'mrds-reservation'); ?></span>
                <span class="btn-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 35 35">
                        <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" fill="#141b42" />
                    </svg>
                </span>
            </a>
        </div>
<?php
        return ob_get_clean();
    }
}
