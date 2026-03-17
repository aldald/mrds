<?php
/**
 * Classe MRDS_Resa_Restaurant_Manager
 * 
 * Gère l'interface de gestion des réservations pour les restaurateurs
 * Support multi-restaurants et compatibilité ACF
 * 
 * @package MRDS_Reservation
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Resa_Restaurant_Manager
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
        // Shortcode
        add_shortcode('mrds_reservations_manager', [$this, 'render_reservations_manager']);
        
        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Assets - priorité haute pour s'assurer que c'est chargé
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 20);
    }

    /**
     * Enregistrer les routes REST API
     */
    public function register_rest_routes()
    {
        // Liste des réservations d'un restaurant
        register_rest_route('mrds/v1', '/reservations', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_list_reservations'],
                'permission_callback' => [$this, 'check_restaurant_permission'],
                'args' => [
                    'restaurant_id' => [
                        'required' => false,
                        'type' => 'integer',
                        'default' => 0,
                    ],
                    'restaurant_ids' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => '',
                    ],
                    'status' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'date_from' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                    'date_to' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                ],
            ],
        ]);

        // Mettre à jour le statut d'une réservation
        register_rest_route('mrds/v1', '/reservations/(?P<id>\d+)/status', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'rest_update_status'],
                'permission_callback' => [$this, 'check_reservation_permission'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'status' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['pending', 'confirmed', 'refused', 'cancelled', 'completed', 'no-show'],
                    ],
                    'notes' => [
                        'type' => 'string',
                        'default' => '',
                    ],
                ],
            ],
        ]);

        // Détails d'une réservation
        register_rest_route('mrds/v1', '/reservations/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'rest_get_reservation'],
                'permission_callback' => [$this, 'check_reservation_permission'],
            ],
        ]);
    }

    /**
     * Vérifier si l'utilisateur peut accéder aux réservations du restaurant
     */
    public function check_restaurant_permission(WP_REST_Request $request)
    {
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MRDS REST Permission Check - User logged in: ' . (is_user_logged_in() ? 'YES' : 'NO'));
            error_log('MRDS REST Permission Check - User ID: ' . get_current_user_id());
        }

        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                'Vous devez être connecté pour accéder à cette ressource.',
                ['status' => 401]
            );
        }

        $user = wp_get_current_user();
        
        // Admins OK
        if (in_array('administrator', (array) $user->roles)) {
            return true;
        }

        // Mode multi-restaurants (restaurant_ids = "1,2,3")
        $restaurant_ids_param = $request->get_param('restaurant_ids');
        if (!empty($restaurant_ids_param)) {
            $ids = array_filter(array_map('intval', explode(',', $restaurant_ids_param)));
            foreach ($ids as $rid) {
                if (!$this->user_can_manage_restaurant($user->ID, $rid)) {
                    return new WP_Error(
                        'rest_forbidden',
                        'Vous n\'avez pas la permission de gérer un ou plusieurs de ces restaurants.',
                        ['status' => 403]
                    );
                }
            }
            return true;
        }

        $restaurant_id = $request->get_param('restaurant_id');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MRDS REST: Checking permission for user {$user->ID} on restaurant $restaurant_id");
        }
        
        $has_permission = $this->user_can_manage_restaurant($user->ID, $restaurant_id);
        
        if (!$has_permission) {
            return new WP_Error(
                'rest_forbidden',
                'Vous n\'avez pas la permission de gérer ce restaurant.',
                ['status' => 403]
            );
        }
        
        return true;
    }

    /**
     * Vérifier si l'utilisateur peut gérer cette réservation
     */
    public function check_reservation_permission(WP_REST_Request $request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                'Vous devez être connecté pour accéder à cette ressource.',
                ['status' => 401]
            );
        }

        $user = wp_get_current_user();

        // Admins OK
        if (in_array('administrator', (array) $user->roles)) {
            return true;
        }

        $reservation_id = $request->get_param('id');
        $restaurant_id = get_post_meta($reservation_id, '_mrds_restaurant_id', true);
        
        $has_permission = $this->user_can_manage_restaurant($user->ID, $restaurant_id);
        
        if (!$has_permission) {
            return new WP_Error(
                'rest_forbidden',
                'Vous n\'avez pas la permission de gérer cette réservation.',
                ['status' => 403]
            );
        }
        
        return true;
    }

    /**
     * Vérifier si un utilisateur peut gérer un restaurant
     * Compatible avec le format de stockage ACF
     */
    private function user_can_manage_restaurant($user_id, $restaurant_id)
    {
        $user_id = (int) $user_id;
        $restaurant_id = (int) $restaurant_id;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MRDS user_can_manage_restaurant: User $user_id, Restaurant $restaurant_id");
        }

        // Owner du restaurant (via ACF)
        $owner = get_field('restaurant_owner', $restaurant_id);
        if ($owner) {
            $owner_id = is_object($owner) ? (int) $owner->ID : (int) $owner;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MRDS: Owner ID = $owner_id");
            }
            if ($owner_id === $user_id) {
                return true;
            }
        }

        // Restaurateurs assignés (via ACF)
        $restaurateurs = get_field('restaurant_restaurateurs', $restaurant_id);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MRDS: Restaurateurs = " . print_r($restaurateurs, true));
        }
        if ($restaurateurs && is_array($restaurateurs)) {
            foreach ($restaurateurs as $rest) {
                $rest_id = is_object($rest) ? (int) $rest->ID : (int) $rest;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("MRDS: Checking restaurateur ID $rest_id against user $user_id");
                }
                if ($rest_id === $user_id) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("MRDS: MATCH FOUND! User $user_id can manage restaurant $restaurant_id");
                    }
                    return true;
                }
            }
        }

        // Fallback : vérifier avec raw meta (au cas où ACF n'est pas chargé)
        $raw_owner = get_post_meta($restaurant_id, 'restaurant_owner', true);
        if ($raw_owner) {
            if ((int) $raw_owner === $user_id) {
                return true;
            }
        }

        $raw_rests = get_post_meta($restaurant_id, 'restaurant_restaurateurs', true);
        if ($raw_rests && is_array($raw_rests)) {
            foreach ($raw_rests as $r) {
                if ((int) $r === $user_id) {
                    return true;
                }
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MRDS: User $user_id does NOT have permission for restaurant $restaurant_id");
        }

        return false;
    }

    /**
     * Liste des réservations
     */
    public function rest_list_reservations(WP_REST_Request $request)
    {
        $restaurant_id = (int) $request->get_param('restaurant_id');
        $restaurant_ids_param = sanitize_text_field($request->get_param('restaurant_ids'));
        $status = sanitize_text_field($request->get_param('status'));
        $date_from = sanitize_text_field($request->get_param('date_from'));
        $date_to = sanitize_text_field($request->get_param('date_to'));

        // Construire le filtre restaurant : mono ou multi
        $is_multi = !empty($restaurant_ids_param);
        if ($is_multi) {
            $ids = array_values(array_filter(array_map('intval', explode(',', $restaurant_ids_param))));
            $restaurant_meta_clause = [
                'key'     => '_mrds_restaurant_id',
                'value'   => $ids,
                'compare' => 'IN',
            ];
        } else {
            $ids = [$restaurant_id];
            $restaurant_meta_clause = [
                'key'     => '_mrds_restaurant_id',
                'value'   => $restaurant_id,
                'compare' => '=',
            ];
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MRDS REST list_reservations: ids=" . implode(',', $ids) . ", status=$status, from=$date_from, to=$date_to");
        }

        // Query args
        $meta_query = [$restaurant_meta_clause];

        // Filtre par statut
        if (!empty($status)) {
            $meta_query[] = [
                'key' => '_mrds_status',
                'value' => $status,
                'compare' => '=',
            ];
        }

        // Filtre par date
        if (!empty($date_from)) {
            $meta_query[] = [
                'key' => '_mrds_date',
                'value' => $date_from,
                'compare' => '>=',
                'type' => 'DATE',
            ];
        }

        if (!empty($date_to)) {
            $meta_query[] = [
                'key' => '_mrds_date',
                'value' => $date_to,
                'compare' => '<=',
                'type' => 'DATE',
            ];
        }

        $args = [
            'post_type' => MRDS_Resa_Post_Type::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => $meta_query,
            'meta_key' => '_mrds_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ];

        $posts = get_posts($args);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("MRDS REST: Found " . count($posts) . " reservations");
        }
        
        $reservations = [];

        foreach ($posts as $post) {
            $reservations[] = $this->format_reservation($post);
        }

        // Stats : agrégées sur tous les IDs concernés
        $stats = $this->get_reservation_stats($ids, $date_from, $date_to);

        return [
            'reservations' => $reservations,
            'stats' => $stats,
            'is_multi' => $is_multi,
        ];
    }

    /**
     * Détails d'une réservation
     */
    public function rest_get_reservation(WP_REST_Request $request)
    {
        $reservation_id = (int) $request->get_param('id');
        $post = get_post($reservation_id);

        if (!$post || $post->post_type !== MRDS_Resa_Post_Type::POST_TYPE) {
            return new WP_Error('not_found', 'Réservation non trouvée.', ['status' => 404]);
        }

        return $this->format_reservation($post, true);
    }

    /**
     * Mettre à jour le statut d'une réservation
     */
    public function rest_update_status(WP_REST_Request $request)
    {
        $reservation_id = (int) $request->get_param('id');
        $new_status = sanitize_text_field($request->get_param('status'));
        $notes = sanitize_textarea_field($request->get_param('notes'));

        $post = get_post($reservation_id);

        if (!$post || $post->post_type !== MRDS_Resa_Post_Type::POST_TYPE) {
            return new WP_Error('not_found', 'Réservation non trouvée.', ['status' => 404]);
        }

        // Récupérer l'ancien statut
        $old_status = get_post_meta($reservation_id, '_mrds_status', true) ?: 'pending';

        // Mettre à jour le statut (cela déclenche automatiquement les emails via le hook on_status_change)
        update_post_meta($reservation_id, '_mrds_status', $new_status);

        // Sauvegarder les notes si fournies
        if (!empty($notes)) {
            update_post_meta($reservation_id, '_mrds_notes', $notes);
        }

        // Logger le changement
        $this->log_status_change($reservation_id, $old_status, $new_status);

        return [
            'success' => true,
            'message' => 'Statut mis à jour avec succès.',
            'reservation' => $this->format_reservation($post),
        ];
    }

    /**
     * Formater une réservation pour l'API
     */
    private function format_reservation($post, $full = false)
    {
        $user_id = get_post_meta($post->ID, '_mrds_user_id', true);
        $user = get_userdata($user_id);

        $restaurant_id_meta = (int) get_post_meta($post->ID, '_mrds_restaurant_id', true);
        $restaurant_post    = get_post($restaurant_id_meta);

        $data = [
            'id'              => $post->ID,
            'restaurant_id'   => $restaurant_id_meta,
            'restaurant_name' => $restaurant_post ? $restaurant_post->post_title : '',
            'date'   => get_post_meta($post->ID, '_mrds_date', true),
            'time'   => get_post_meta($post->ID, '_mrds_time', true),
            'guests' => (int) get_post_meta($post->ID, '_mrds_guests', true),
            'status' => get_post_meta($post->ID, '_mrds_status', true) ?: 'pending',
            'client_name' => $user ? trim($user->first_name . ' ' . $user->last_name) : 'Client inconnu',
            'phone' => get_post_meta($post->ID, '_mrds_phone', true),
            'email' => get_post_meta($post->ID, '_mrds_email', true),
        ];

        if ($full) {
            $data['occasion'] = get_post_meta($post->ID, '_mrds_occasion', true);
            $data['allergies'] = get_post_meta($post->ID, '_mrds_allergies', true);
            $data['preferences'] = get_post_meta($post->ID, '_mrds_preferences', true);
            $data['notes'] = get_post_meta($post->ID, '_mrds_notes', true);
            $data['created_at'] = get_post_meta($post->ID, '_mrds_created_at', true);
            $data['status_logs'] = get_post_meta($post->ID, '_mrds_status_logs', true) ?: [];
        }

        return $data;
    }

    /**
     * Obtenir les statistiques des réservations
     */
    /**
     * Obtenir les statistiques — accepte un int ou un array d'IDs
     */
    private function get_reservation_stats($restaurant_id, $date_from = '', $date_to = '')
    {
        $today      = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end   = date('Y-m-d', strtotime('sunday this week'));

        return [
            'pending'   => $this->count_reservations($restaurant_id, 'pending',   $date_from, $date_to),
            'confirmed' => $this->count_reservations($restaurant_id, 'confirmed', $date_from, $date_to),
            'refused'   => $this->count_reservations($restaurant_id, 'refused',   $date_from, $date_to),
            'today'     => $this->count_reservations($restaurant_id, '', $today,      $today),
            'week'      => $this->count_reservations($restaurant_id, '', $week_start, $week_end),
        ];
    }

    /**
     * Compter les réservations — $restaurant_id peut être un int ou un array d'ints
     */
    private function count_reservations($restaurant_id, $status = '', $date_from = '', $date_to = '')
    {
        // Clause restaurant : mono ou multi
        if (is_array($restaurant_id)) {
            $restaurant_clause = [
                'key'     => '_mrds_restaurant_id',
                'value'   => $restaurant_id,
                'compare' => 'IN',
            ];
        } else {
            $restaurant_clause = [
                'key'     => '_mrds_restaurant_id',
                'value'   => $restaurant_id,
                'compare' => '=',
            ];
        }

        $meta_query = [$restaurant_clause];

        if (!empty($status)) {
            $meta_query[] = [
                'key' => '_mrds_status',
                'value' => $status,
                'compare' => '=',
            ];
        } else {
            // Exclure les annulées et refusées pour les stats générales
            $meta_query[] = [
                'key' => '_mrds_status',
                'value' => ['cancelled', 'refused'],
                'compare' => 'NOT IN',
            ];
        }

        if (!empty($date_from)) {
            $meta_query[] = [
                'key' => '_mrds_date',
                'value' => $date_from,
                'compare' => '>=',
                'type' => 'DATE',
            ];
        }

        if (!empty($date_to)) {
            $meta_query[] = [
                'key' => '_mrds_date',
                'value' => $date_to,
                'compare' => '<=',
                'type' => 'DATE',
            ];
        }

        $args = [
            'post_type' => MRDS_Resa_Post_Type::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => $meta_query,
            'fields' => 'ids',
        ];

        $posts = get_posts($args);
        return count($posts);
    }

    /**
     * Logger le changement de statut
     */
    private function log_status_change($reservation_id, $old_status, $new_status)
    {
        $logs = get_post_meta($reservation_id, '_mrds_status_logs', true) ?: [];
        
        $logs[] = [
            'from' => $old_status,
            'to' => $new_status,
            'user_id' => get_current_user_id(),
            'date' => current_time('mysql'),
        ];

        update_post_meta($reservation_id, '_mrds_status_logs', $logs);
    }

    /**
     * Charger les assets
     */
    public function enqueue_assets()
    {
        // Ne charger que si le shortcode est utilisé
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'mrds_reservations_manager')) {
            return;
        }

        wp_enqueue_style(
            'mrds-reservations-manager',
            MRDS_RESA_PLUGIN_URL . 'assets/css/reservations-manager.css',
            [],
            defined('MRDS_RESA_VERSION') ? MRDS_RESA_VERSION : '1.0.0'
        );

        wp_enqueue_script(
            'mrds-reservations-manager',
            MRDS_RESA_PLUGIN_URL . 'assets/js/reservations-manager.js',
            ['jquery'],
            defined('MRDS_RESA_VERSION') ? MRDS_RESA_VERSION : '1.0.0',
            true
        );

        // Localiser le script ici aussi pour s'assurer que la config est disponible
        // même si le shortcode n'a pas encore été rendu
        $user_id = get_current_user_id();
        $restaurants = $this->get_all_user_restaurants($user_id);
        
        if (!empty($restaurants)) {
            $restaurant = $restaurants[0];
            $restaurant_ids = array_map(function($r) { return $r->ID; }, $restaurants);
            
            wp_localize_script('mrds-reservations-manager', 'MRDSReservationsConfig', [
                'restUrl' => esc_url_raw(rest_url('mrds/v1/reservations')),
                'nonce' => wp_create_nonce('wp_rest'),
                    'isAdmin' => current_user_can('administrator'),
                'restaurantId' => $restaurant->ID,
                'allRestaurantIds' => $restaurant_ids,
            ]);
        }
    }

    /**
     * Afficher le gestionnaire de réservations
     */
    public function render_reservations_manager($atts = [])
    {
        // Vérifier la connexion
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning">Vous devez être connecté pour accéder à cette page.</div>';
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        // Vérifier les rôles
        $allowed_roles = ['administrator', 'super_restaurateur', 'restaurateur'];
        $has_role = array_intersect($allowed_roles, (array) $user->roles);

        if (empty($has_role)) {
            return '<div class="alert alert-danger">Vous n\'avez pas les droits pour accéder à cette page.</div>';
        }

        // Trouver TOUS les restaurants de l'utilisateur
        $restaurants = $this->get_all_user_restaurants($user_id);

        // Debug temporaire
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MRDS Reservations Manager - User ID: ' . $user_id);
            error_log('MRDS Reservations Manager - Restaurants trouvés: ' . count($restaurants));
            foreach ($restaurants as $r) {
                error_log('  - ' . $r->post_title . ' (ID: ' . $r->ID . ')');
            }
        }

        if (empty($restaurants)) {
            $debug_info = '';
            if (current_user_can('administrator')) {
                //$debug_info = '<br><small class="text-muted">Debug: User ID = ' . $user_id . '</small>';
            }
            return '<div class="alert alert-warning">Aucun restaurant n\'est associé à votre compte.' . $debug_info . '</div>';
        }

        // Détecter le mode "tous les restaurants"
        $is_all_mode = isset($_GET['restaurant_id']) && $_GET['restaurant_id'] === '0';

        // Prendre le premier restaurant par défaut, ou celui sélectionné
        $selected_id = isset($_GET['restaurant_id']) ? (int) $_GET['restaurant_id'] : 0;
        $restaurant = null;
        
        if ($selected_id) {
            foreach ($restaurants as $r) {
                if ($r->ID === $selected_id) {
                    $restaurant = $r;
                    break;
                }
            }
        }
        
        if (!$restaurant) {
            $restaurant = $restaurants[0];
        }

        // Localiser le script avec tous les IDs de restaurants
        $restaurant_ids = array_map(function($r) { return $r->ID; }, $restaurants);
        
        wp_localize_script('mrds-reservations-manager', 'MRDSReservationsConfig', [
            'restUrl'          => esc_url_raw(rest_url('mrds/v1/reservations')),
            'nonce'            => wp_create_nonce('wp_rest'),
            'restaurantId'     => $is_all_mode ? 0 : $restaurant->ID,
            'allRestaurantIds' => $restaurant_ids,
            'isAllMode'        => $is_all_mode,
            'isAdmin'          => current_user_can('administrator'),
        ]);

        // Charger le template
        ob_start();

        $restaurant_id = $restaurant->ID;
        $all_restaurants = $restaurants;
        $template = MRDS_RESA_PLUGIN_DIR . 'templates/reservations-manager-template.php';

        if (file_exists($template)) {
            include $template;
        } else {
            echo '<p>Template non trouvé.</p>';
        }

        return ob_get_clean();
    }

    /**
     * Récupérer TOUS les restaurants d'un utilisateur
     */
    private function get_all_user_restaurants($user_id)
    {
        $user_id = (int) $user_id;
        $user_restaurants = [];
        
        // Récupérer tous les restaurants publiés
        $args = [
            'post_type' => 'restaurant',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];

        $restaurants = get_posts($args);

        foreach ($restaurants as $restaurant) {
            $is_manager = false;
            
            // Vérifier si owner (via ACF get_field)
            $owner = get_field('restaurant_owner', $restaurant->ID);
            if ($owner) {
                $owner_id = is_object($owner) ? (int) $owner->ID : (int) $owner;
                if ($owner_id === $user_id) {
                    $is_manager = true;
                }
            }

            // Vérifier si dans les restaurateurs (via ACF get_field)
            if (!$is_manager) {
                $restaurateurs = get_field('restaurant_restaurateurs', $restaurant->ID);
                if ($restaurateurs && is_array($restaurateurs)) {
                    foreach ($restaurateurs as $rest) {
                        $rest_id = is_object($rest) ? (int) $rest->ID : (int) $rest;
                        if ($rest_id === $user_id) {
                            $is_manager = true;
                            break;
                        }
                    }
                }
            }

            // Fallback raw meta si ACF n'a pas trouvé
            if (!$is_manager) {
                $raw_owner = get_post_meta($restaurant->ID, 'restaurant_owner', true);
                if ($raw_owner && (int) $raw_owner === $user_id) {
                    $is_manager = true;
                }
            }

            if (!$is_manager) {
                $raw_rests = get_post_meta($restaurant->ID, 'restaurant_restaurateurs', true);
                if ($raw_rests && is_array($raw_rests)) {
                    foreach ($raw_rests as $r) {
                        if ((int) $r === $user_id) {
                            $is_manager = true;
                            break;
                        }
                    }
                }
            }

            if ($is_manager) {
                $user_restaurants[] = $restaurant;
            }
        }

        return $user_restaurants;
    }

    /**
     * Récupérer le restaurant d'un utilisateur (premier trouvé)
     */
    private function get_user_restaurant($user_id)
    {
        $restaurants = $this->get_all_user_restaurants($user_id);
        return !empty($restaurants) ? $restaurants[0] : null;
    }
}

// Initialiser
MRDS_Resa_Restaurant_Manager::get_instance();