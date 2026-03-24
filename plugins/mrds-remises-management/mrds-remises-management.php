<?php

/**
 * Plugin Name: MRDS - Gestion Remises
 * Description: Gestion des remises / promotions par restaurant.
 * Author: Coccinet
 * Version: 0.1.0
 * Text Domain: restaurant-remises
 */

if (!defined('ABSPATH')) {
    exit; // Sécurité
}

class MRDS_Remises_management
{

    /**
     * Instance singleton
     * @var MRDS_Remises_management|null
     */
    protected static $instance = null;

    /**
     * Slug du CPT des remises
     * @var string
     */
    protected $remise_post_type = 'remise';

    /**
     * Slug du CPT restaurant (si tu en as un déjà ailleurs, adapte ici)
     * @var string
     */
    protected $restaurant_post_type = 'restaurant';

    /**
     * Slug de la taxonomy type_remise
     * @var string
     */
    protected $type_remise_taxonomy = 'type_remise';

    /**
     * Singleton
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    protected function __construct()
    {
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);

        register_activation_hook(__FILE__, ['MRDS_Remises_management', 'activate']);
        register_deactivation_hook(__FILE__, ['MRDS_Remises_management', 'deactivate']);

        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_shortcode('restaurant_remises_manager', [$this, 'render_remises_manager']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Sync remises_liees côté restaurant lors de la sauvegarde WP-Admin d'une remise
        add_action('acf/save_post', [$this, 'sync_remises_liees_on_save'], 20);
    }
    /**
     * Synchronise remises_liees côté restaurant lors de la sauvegarde WP-Admin d'une remise.
     * - Ajoute la remise au nouveau restaurant lié
     * - Retire la remise de l'ancien restaurant si le restaurant a changé
     *
     * @param int $post_id ID du post sauvegardé
     */
    public function sync_remises_liees_on_save($post_id)
    {
        // Uniquement pour le CPT remise
        if (get_post_type($post_id) !== $this->remise_post_type) {
            return;
        }

        // Nouveaux restaurants sélectionnés (ACF relationship → array)
        $restaurant_concerne = get_field('restaurant_concerne', $post_id);
        $restaurant_concerne = is_array($restaurant_concerne) ? $restaurant_concerne : [];

        // Normaliser en tableau d'IDs entiers
        $nouveaux_ids = array_map(function ($r) {
            return is_object($r) ? (int) $r->ID : (int) $r;
        }, $restaurant_concerne);
        $nouveaux_ids = array_values(array_filter($nouveaux_ids));

        // Anciens restaurants mémorisés lors de la sauvegarde précédente
        $anciens_ids_raw = get_post_meta($post_id, '_mrds_previous_restaurants', true);
        $anciens_ids = is_array($anciens_ids_raw) ? $anciens_ids_raw : [];

        // Restaurants supprimés de la sélection → retirer la remise de leur remises_liees
        $retires = array_diff($anciens_ids, $nouveaux_ids);
        foreach ($retires as $rid) {
            $remises = get_field('remises_liees', $rid);
            $remises = is_array($remises) ? $remises : [];
            $ids = array_map(function ($r) { return is_object($r) ? (int) $r->ID : (int) $r; }, $remises);
            $ids = array_values(array_filter($ids, fn($i) => $i !== (int) $post_id));
            update_field('remises_liees', $ids, $rid);
        }

        // Tous les restaurants sélectionnés → ajouter la remise si absente
        foreach ($nouveaux_ids as $rid) {
            $remises = get_field('remises_liees', $rid);
            $remises = is_array($remises) ? $remises : [];
            $ids = array_map(function ($r) { return is_object($r) ? (int) $r->ID : (int) $r; }, $remises);

            if (!in_array((int) $post_id, $ids, true)) {
                $ids[] = (int) $post_id;
                update_field('remises_liees', $ids, $rid);
            }
        }

        // Si la remise est liée à au moins un restaurant, on l'active automatiquement
        if (!empty($nouveaux_ids) && !get_field('remise_active', $post_id)) {
            update_field('remise_active', true, $post_id);
        }

        // Mémoriser les restaurants actuels pour la prochaine sauvegarde
        update_post_meta($post_id, '_mrds_previous_restaurants', $nouveaux_ids);
    }

    public function enqueue_assets()
    {
        if (!is_user_logged_in()) {
            return;
        }

        // Tu peux raffiner la condition : uniquement sur une page précise, etc.
        wp_register_script(
            'restaurant-remises-manager',
            plugin_dir_url(__FILE__) . 'assets/js/restaurant-remises-manager.js',
            ['wp-api-fetch'],
            '0.1.0',
            true
        );

        // Exemple : on suppose que le restaurateur est lié à 1 restaurant_id (à adapter)
        $current_user_id = get_current_user_id();
        $restaurant_id = $_GET['restaurant_id']; // a mettre a jours le id de restorant lié à l'utilisateur
        // $restaurant_id = get_user_meta($current_user_id, 'restaurant_id', true);

        wp_localize_script(
            'restaurant-remises-manager',
            'RestaurantRemisesConfig',
            [
                'restUrl' => esc_url_raw(rest_url('restaurant-remises/v1/remises')),
                'nonce' => wp_create_nonce('wp_rest'),
                'restaurantId' => (int) $restaurant_id,
            ]
        );

        wp_enqueue_script('restaurant-remises-manager');


        wp_enqueue_style(
            'mrds-gestion-remises',
            plugin_dir_url(__FILE__) . 'assets/css/gestion-remises.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/gestion-remises.css')
        );
    }

    /**
     * REST API routes
     */
    public function register_rest_routes()
    {

        register_rest_route(
            'restaurant-remises/v1',
            '/remises',
            [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'rest_list_remises'],
                    'permission_callback' => [$this, 'rest_check_permission'],
                ],
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'rest_create_remise'],
                    'permission_callback' => [$this, 'rest_check_permission'],
                ],
            ]
        );

        register_rest_route(
            'restaurant-remises/v1',
            '/remises/(?P<id>\d+)',
            [
                [
                    'methods' => 'PUT',
                    'callback' => [$this, 'rest_update_remise'],
                    'permission_callback' => [$this, 'rest_check_permission'],
                ],
                [
                    'methods' => 'DELETE',
                    'callback' => [$this, 'rest_delete_remise'],
                    'permission_callback' => [$this, 'rest_check_permission'],
                ],
                [
                    'methods' => 'PATCH',
                    'callback' => [$this, 'reset_swap_active_status_remise'],
                    'permission_callback' => [$this, 'rest_check_permission'],
                ],
            ]
        );
    }

    /**
     * Permission : ici, on limite aux utilisateurs connectés (à affiner : rôle restaurateur, etc.)
     */
    public function rest_check_permission($request)
    {
        return true;
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        $method = $request->get_method();

        // Si tu passes un restaurant_id dans l'URL ou dans le body
        $restaurant_id = null;

        if ($request->get_param('restaurant_id')) {
            $restaurant_id = (int) $request->get_param('restaurant_id');
        } elseif ($request->get_param('restaurant')) {
            $restaurant_id = (int) $request->get_param('restaurant');
        }

        if (!$restaurant_id && $method === 'GET') {
            return current_user_can('edit_remises') || current_user_can('manage_restaurant_network');
        }

        if ($restaurant_id) {
            return MRDS_Gestion_Restaurant::get_instance()->user_can_manage_restaurant($user_id, $restaurant_id);
        }

        return false;
    }

    /**
     * GET /remises : liste des remises du restaurant connecté
     */
    /**
     * GET /remises : liste des remises du restaurant associé à l'utilisateur
     */
    public function rest_list_remises(WP_REST_Request $request)
    {

        $restaurant_id = $request->get_param('restaurant_id') ?? null;

        if (!$restaurant_id) {
            return [];
        }

        $args = [
            'post_type' => $this->remise_post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'restaurant_concerne',
                    'value' => '"' . intval($restaurant_id) . '"', // ACF relationship stocke un array sérialisé
                    'compare' => 'LIKE',
                ],
            ],
        ];

        $posts = get_posts($args);
        $data = [];

        foreach ($posts as $post) {

            $id = $post->ID;

            // Champs ACF
            $remise_active = (bool) get_field('remise_active', $id);
            $restaurant_concerne = get_field('restaurant_concerne', $id); // array d'IDs (relationship)
            $type_de_remise = get_field('type_de_remise', $id);      // ID de terme
            $valeur_de_la_remise = get_field('valeur_de_la_remise', $id);
            $valeur_max_remise = get_field('valeur_max_remise', $id);
            $description_interne = get_field('description_interne', $id);

            $date_debut = get_field('date_debut', $id);         // format d/m/Y
            $date_fin = get_field('date_fin', $id);
            $jours_semaine = get_field('jours_semaine', $id);      // array
            $services = get_field('services', $id); // array: ['dejeuner','diner']
            $nombre_min_couverts = get_field('nombre_minimum_de_couverts', $id);
            $nombre_max_couverts = get_field('nombre_maximum_de_couverts', $id);
            $montant_minimum_commande = get_field('montant_minimum_commande', $id);

            $scope_remise = get_field('scope_remise', $id);
            $categories_produits = get_field('categories_produits', $id);
            $produits_cibles = get_field('produits_cibles', $id);
            $menu_concerne = get_field('menu_concerne', $id);

            // Label du type de remise (taxonomy type_remise)
            $type_de_remise_label = '';
            if ($type_de_remise) {
                $term = get_term((int) $type_de_remise, 'type_remise');
                if ($term && !is_wp_error($term)) {
                    $type_de_remise_label = $term->name;
                }
            }

            $data[] = [
                'id' => $id,
                'title' => $post->post_title,

                'remise_active' => $remise_active,
                'restaurant_concerne' => $restaurant_concerne,
                'type_de_remise' => $type_de_remise,
                'type_de_remise_label' => $type_de_remise_label,
                'valeur_de_la_remise' => ($valeur_de_la_remise !== '' ? (float) $valeur_de_la_remise : null),
                'valeur_max_remise' => ($valeur_max_remise !== '' ? (float) $valeur_max_remise : null),
                'description_interne' => $description_interne,

                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'jours_semaine' => is_array($jours_semaine) ? $jours_semaine : [],
                'services' => is_array($services) ? $services : [],
                'nombre_minimum_de_couverts' => ($nombre_min_couverts !== '' ? (int) $nombre_min_couverts : null),
                'nombre_maximum_de_couverts' => ($nombre_max_couverts !== '' ? $nombre_max_couverts : null),
                'montant_minimum_commande' => ($montant_minimum_commande !== '' ? (float) $montant_minimum_commande : null),

                'scope_remise' => $scope_remise,
                'categories_produits' => $categories_produits,
                'produits_cibles' => $produits_cibles,
                'menu_concerne' => $menu_concerne,
                'remise_text' => $this->generate_remise_text($id),
            ];
        }

        return $data;
    }


    /**
     * POST /remises : création
     */
    public function rest_create_remise(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $user_id = get_current_user_id();
        $restaurant_id = $params['restaurant_id'] ?? null;

        $can_manage = MRDS_Gestion_Restaurant::get_instance()->user_can_manage_restaurant($user_id, $restaurant_id);
        if (!$can_manage) {
            return new WP_Error('no_restaurant', __('Acces invalide pour gerer ce restaurant.', 'restaurant-remises'), ['status' => 400]);
        }



        if (!$restaurant_id) {
            return new WP_Error('no_restaurant', __('Aucun restaurant associé à cet utilisateur.', 'restaurant-remises'), ['status' => 400]);
        }

$title = isset($params['title']) ? sanitize_text_field($params['title']) : 'Remise';
$restaurant_name = get_post_field('post_title', (int) $restaurant_id);
if ($restaurant_name) {
    $title = $restaurant_name . ' : ' . $title;
}

$post_id = wp_insert_post([
    'post_type' => $this->remise_post_type,
    'post_status' => 'publish',
    'post_title' => $title,
], true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // On enregistre les metas (ACF)
        $this->save_remise_meta_from_params($post_id, $params, $restaurant_id);

        return ['success' => true, 'id' => $post_id];
    }

    /**
     * PUT /remises/{id} : mise à jour
     */
    public function rest_update_remise(WP_REST_Request $request)
    {

        $id = (int) $request['id'];
        $params = $request->get_json_params();

        $post = get_post($id);

        if (!$post || $post->post_type !== $this->remise_post_type) {
            return new WP_Error('not_found', __('Remise introuvable.', 'restaurant-remises'), ['status' => 404]);
        }

        // Optionnel : vérifier que cette remise est bien liée au restaurant du user

if (isset($params['title'])) {
    $restaurant_id_upd = $params['restaurant_id'] ?? null;
    $restaurant_name   = $restaurant_id_upd ? get_post_field('post_title', (int) $restaurant_id_upd) : '';
    $new_title         = sanitize_text_field($params['title']);

    // Supprimer le préfixe s'il existe déjà
    if ($restaurant_name && str_starts_with($new_title, $restaurant_name . ' : ')) {
        $new_title = substr($new_title, strlen($restaurant_name . ' : '));
    }

    if ($restaurant_name) {
        $new_title = $restaurant_name . ' : ' . $new_title;
    }

    wp_update_post([
        'ID'         => $id,
        'post_title' => $new_title,
    ]);
}

        $user_id = get_current_user_id();
        $restaurant_id = $params['restaurant_id'] ?? null;

        $this->save_remise_meta_from_params($id, $params, $restaurant_id);

        return ['success' => true];
    }

    /**
     * DELETE /remises/{id}
     */
    public function rest_delete_remise(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $post = get_post($id);

        if (!$post || $post->post_type !== $this->remise_post_type) {
            return new WP_Error('not_found', __('Remise introuvable.', 'restaurant-remises'), ['status' => 404]);
        }

        // Retirer la remise de remises_liees côté restaurant
        $restaurant_id = get_field('restaurant_concerne', $id);
        if ($restaurant_id) {
            $rid = is_array($restaurant_id) ? (int) $restaurant_id[0] : (int) $restaurant_id;
            $remises_liees = get_field('remises_liees', $rid);
            $remises_liees = is_array($remises_liees) ? $remises_liees : [];

            $ids = array_map(function ($r) {
                return is_object($r) ? (int) $r->ID : (int) $r;
            }, $remises_liees);

            $ids = array_values(array_filter($ids, fn($i) => $i !== (int) $id));
            update_field('remises_liees', $ids, $rid);
        }

        wp_trash_post($id);
        return ['success' => true];
    }

    /**
     * Sauvegarde des champs ACF / metas depuis le JSON du front
     */
    /**
     * Sauvegarde des champs ACF / metas depuis le JSON du front
     *
     * @param int   $post_id       ID de la remise
     * @param array $params        Données envoyées par le front
     * @param int   $restaurant_id ID du restaurant lié (optionnel mais recommandé)
     */
    protected function save_remise_meta_from_params($post_id, $params, $restaurant_id = null)
    {

        // Restaurant concerné (relationship ACF → array d'IDs)
        if ($restaurant_id) {
            update_field('restaurant_concerne', [(int) $restaurant_id], $post_id);

            // Sync inverse : mettre à jour remises_liees côté restaurant
            $remises_liees_actuelles = get_field('remises_liees', $restaurant_id);
            $remises_liees_actuelles = is_array($remises_liees_actuelles) ? $remises_liees_actuelles : [];

            // Normaliser en IDs
            $ids_actuels = array_map(function ($r) {
                return is_object($r) ? (int) $r->ID : (int) $r;
            }, $remises_liees_actuelles);

            // Ajouter cette remise si pas déjà présente
            if (!in_array((int) $post_id, $ids_actuels, true)) {
                $ids_actuels[] = (int) $post_id;
                update_field('remises_liees', $ids_actuels, (int) $restaurant_id);
            }
        } elseif (isset($params['restaurant_concerne']) && is_array($params['restaurant_id'])) {
            $ids = array_map('intval', $params['restaurant_id']);
            update_field('restaurant_concerne', $ids, $post_id);
        }

        // --- Remise – Général ---

        if (isset($params['remise_active'])) {
            update_field('remise_active', (bool) $params['remise_active'], $post_id);
        }

        // type_de_remise : taxonomy (return_format = id)
        if (isset($params['type_de_remise']) && $params['type_de_remise'] !== '') {
            $type_id = (int) $params['type_de_remise'];
            update_field('type_de_remise', $type_id, $post_id);
            // optionnel : synchroniser aussi la taxonomy WP
            wp_set_post_terms($post_id, [$type_id], 'type_remise', false);
        }

        if (array_key_exists('valeur_de_la_remise', $params)) {
            $value = $params['valeur_de_la_remise'];
            update_field(
                'valeur_de_la_remise',
                ($value !== null && $value !== '' ? (float) $value : ''),
                $post_id
            );
        }

        if (array_key_exists('valeur_max_remise', $params)) {
            $value = $params['valeur_max_remise'];
            update_field(
                'valeur_max_remise',
                ($value !== null && $value !== '' ? (float) $value : ''),
                $post_id
            );
        }

        if (isset($params['description_interne'])) {
            update_field('description_interne', wp_kses_post($params['description_interne']), $post_id);
        }

        // --- Conditions d’application ---

        if (isset($params['date_debut'])) {
            // Le JS envoie déjà au format d/m/Y (via toAcfDate), donc on stocke tel quel
            update_field('date_debut', sanitize_text_field($params['date_debut']), $post_id);
        }

        if (isset($params['date_fin'])) {
            update_field('date_fin', sanitize_text_field($params['date_fin']), $post_id);
        }

        if (isset($params['jours_semaine']) && is_array($params['jours_semaine'])) {
            $jours = array_map('sanitize_text_field', $params['jours_semaine']);
            update_field('jours_semaine', $jours, $post_id);
        }
        if (isset($params['services']) && is_array($params['services'])) {
            $services = array_map('sanitize_text_field', $params['services']);
            // Sécurise: ne garde que les valeurs attendues
            $services = array_values(array_intersect($services, ['dejeuner', 'diner']));
            update_field('services', $services, $post_id);
        }

        if (array_key_exists('nombre_minimum_de_couverts', $params)) {
            $v = $params['nombre_minimum_de_couverts'];
            update_field(
                'nombre_minimum_de_couverts',
                ($v !== null && $v !== '' ? (int) $v : ''),
                $post_id
            );
        }

        if (array_key_exists('nombre_maximum_de_couverts', $params)) {
            // Dans ton ACF, c’est un champ texte, pas number
            $v = $params['nombre_maximum_de_couverts'];
            update_field(
                'nombre_maximum_de_couverts',
                ($v !== null ? sanitize_text_field($v) : ''),
                $post_id
            );
        }

        if (array_key_exists('montant_minimum_commande', $params)) {
            $v = $params['montant_minimum_commande'];
            update_field(
                'montant_minimum_commande',
                ($v !== null && $v !== '' ? (float) $v : ''),
                $post_id
            );
        }

        // --- Ciblage de la remise ---

        if (isset($params['scope_remise'])) {
            update_field('scope_remise', sanitize_text_field($params['scope_remise']), $post_id);
        }

        if (array_key_exists('categories_produits', $params)) {
            update_field(
                'categories_produits',
                sanitize_text_field((string) $params['categories_produits']),
                $post_id
            );
        }

        if (array_key_exists('produits_cibles', $params)) {
            update_field(
                'produits_cibles',
                sanitize_text_field((string) $params['produits_cibles']),
                $post_id
            );
        }

        if (array_key_exists('menu_concerne', $params)) {
            update_field(
                'menu_concerne',
                sanitize_text_field((string) $params['menu_concerne']),
                $post_id
            );
        }
    }


    /**
     * Shortcode : [restaurant_remises_manager]
     */
    public function render_remises_manager()
    {
        $user_id = get_current_user_id();
        $restaurant_id = $_GET['restaurant_id'] ?? null;

        $can_manage = MRDS_Gestion_Restaurant::get_instance()->user_can_manage_restaurant($user_id, $restaurant_id);
        if (!$can_manage) {
            return '<p>ERROR_PERMISSION_DENIED.</p>';
        }

        if (!is_user_logged_in()) {
            return '<p>Vous devez être connecté pour gérer vos remises.</p>';
        }

        ob_start();

        // Charge la template depuis /templates/
        $template = plugin_dir_path(__FILE__) . 'templates/remise-manager-template.php';
        if (file_exists($template)) {
            // Variables disponibles dans la template :
            $type_remise_terms = get_terms([
                'taxonomy' => 'type_remise',
                'hide_empty' => false,
            ]);

            include $template;
        } else {
            echo "<p>Erreur : Template non trouvée.</p>";
        }

        return ob_get_clean();
    }



    /**
     * Activation plugin
     */
    public static function activate()
    {
        $instance = self::get_instance();
        $instance->register_post_types();
        $instance->register_taxonomies();
        flush_rewrite_rules();
    }

    /**
     * Désactivation plugin
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Enregistrement du CPT "remise"
     */
    public function register_post_types()
    {

        // CPT Remise
        $labels_remise = [
            'name' => __('Remises', 'restaurant-remises'),
            'singular_name' => __('Remise', 'restaurant-remises'),
            'add_new' => __('Ajouter une remise', 'restaurant-remises'),
            'add_new_item' => __('Ajouter une nouvelle remise', 'restaurant-remises'),
            'edit_item' => __('Modifier la remise', 'restaurant-remises'),
            'new_item' => __('Nouvelle remise', 'restaurant-remises'),
            'view_item' => __('Voir la remise', 'restaurant-remises'),
            'search_items' => __('Rechercher des remises', 'restaurant-remises'),
            'not_found' => __('Aucune remise trouvée', 'restaurant-remises'),
            'not_found_in_trash' => __('Aucune remise dans la corbeille', 'restaurant-remises'),
            'menu_name' => __('Remises', 'restaurant-remises'),
        ];

        $args_remise = [
            'labels' => $labels_remise,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => ['title'],
            'menu_position' => 25,
            'menu_icon' => 'dashicons-tickets-alt',
            'has_archive' => false,
            'show_in_rest' => true,
        ];

        register_post_type($this->remise_post_type, $args_remise);
    }

    /**
     * Enregistrement de la taxonomy "type_remise"
     * (optionnelle si tu préfères gérer le type via ACF Select)
     */
    public function register_taxonomies()
    {

        $labels_type_remise = [
            'name' => __('Types de remise', 'restaurant-remises'),
            'singular_name' => __('Type de remise', 'restaurant-remises'),
            'search_items' => __('Rechercher des types de remise', 'restaurant-remises'),
            'all_items' => __('Tous les types de remise', 'restaurant-remises'),
            'edit_item' => __('Modifier le type de remise', 'restaurant-remises'),
            'add_new_item' => __('Ajouter un type de remise', 'restaurant-remises'),
            'menu_name' => __('Types de remise', 'restaurant-remises'),
        ];

        $args_type_remise = [
            'hierarchical' => false,
            'labels' => $labels_type_remise,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'query_var' => true,
        ];

        register_taxonomy(
            $this->type_remise_taxonomy,
            [$this->remise_post_type],
            $args_type_remise
        );
    }

    /**
     * Récupérer le texte de la remise active applicable pour un restaurant donné
     *
     * @param int         $restaurant_id  ID du restaurant
     * @param string|null $date           Date de réservation (dd/mm/YYYY ou YYYY-mm-dd).
     *                                    Si fournie, filtre par jour de la semaine ET par plage date_debut/date_fin.
     * @return string                     Texte de la remise applicable, ou '' si aucune
     */
    public function get_applicable_remises_for_restaurant($restaurant_id, $date = null)
    {
        // Récupérer les remises actives liées au restaurant
        $query_args = [
            'post_type' => $this->remise_post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'     => 'remise_active',
                    'value'   => '1',
                    'compare' => '='
                ],
                [
                    'key'     => 'restaurant_concerne',
                    'value'   => '"' . intval($restaurant_id) . '"',
                    'compare' => 'LIKE',
                ],
            ],
        ];

        $remises = get_posts($query_args);

        if (empty($remises)) {
            return '';
        }

        // Filtres par jour ET par plage de dates si une date est fournie
        if (!empty($date)) {

            // Normaliser la date en YYYY-mm-dd (accepte dd/mm/YYYY et YYYY-mm-dd)
            if (strpos($date, '/') !== false) {
                $parts = explode('/', $date);
                if (count($parts) === 3) {
                    $date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }
            }

            $timestamp_resa = strtotime($date);

            // Correspondance PHP date('N') → code jour ACF
            $day_map = [
                1 => 'mon',
                2 => 'tue',
                3 => 'wed',
                4 => 'thu',
                5 => 'fri',
                6 => 'sat',
                7 => 'sun',
            ];

            $day_num  = (int) date('N', $timestamp_resa);
            $day_code = $day_map[$day_num] ?? '';

            $remises = array_values(array_filter($remises, function ($remise) use ($day_code, $timestamp_resa) {

                // --- Filtre 1 : jour de la semaine ---
                $jours = get_field('jours_semaine', $remise->ID);
                if (!empty($jours) && is_array($jours)) {
                    if (!in_array($day_code, $jours, true)) {
                        return false;
                    }
                }

                // --- Filtre 2 : plage date_debut / date_fin ---
                $date_debut_acf = get_field('date_debut', $remise->ID);
                $date_fin_acf   = get_field('date_fin', $remise->ID);

                if (!empty($date_debut_acf)) {
                    $parts = explode('/', $date_debut_acf);
                    if (count($parts) === 3) {
                        $ts_debut = strtotime($parts[2] . '-' . $parts[1] . '-' . $parts[0]);
                        if ($timestamp_resa < $ts_debut) {
                            return false;
                        }
                    }
                }

                if (!empty($date_fin_acf)) {
                    $parts = explode('/', $date_fin_acf);
                    if (count($parts) === 3) {
                        $ts_fin = strtotime($parts[2] . '-' . $parts[1] . '-' . $parts[0]);
                        if ($timestamp_resa > $ts_fin) {
                            return false;
                        }
                    }
                }

                return true;
            }));
        }

        // Retourner le texte de la première remise applicable (comme avant)
        foreach ($remises as $remise) {
            return $this->generate_remise_text($remise->ID);
        }

        return '';
    }


    /**
     * Génère un texte lisible de la remise à partir des champs ACF
     * @param int $remise_id
     * @return string
     */
    public function generate_remise_text($remise_id)
    {

        if (!$remise_id)
            return '';

        // ====== Récup données ======

        $type_de_remise = get_field('type_de_remise', $remise_id); // ID du terme
        $type_label = $type_de_remise ? get_term($type_de_remise)->name : '';

        $valeur = get_field('valeur_de_la_remise', $remise_id);
        $valeur_max = get_field('valeur_max_remise', $remise_id);

        $date_debut = get_field('date_debut', $remise_id);
        $date_fin = get_field('date_fin', $remise_id);

        $jours = get_field('jours_semaine', $remise_id); // array: mon,tue,...
        $services = get_field('services', $remise_id); // array: dejeuner/diner
        $min_couv = get_field('nombre_minimum_de_couverts', $remise_id);
        $max_couv = get_field('nombre_maximum_de_couverts', $remise_id);

        $montant_min = get_field('montant_minimum_commande', $remise_id);

        $scope = get_field('scope_remise', $remise_id);
        $cats = get_field('categories_produits', $remise_id);
        $prods = get_field('produits_cibles', $remise_id);
        $menu = get_field('menu_concerne', $remise_id);

        // ====== Construction du texte ======

        $parts = [];

        // Type + valeur (toujours un pourcentage)
        if ($valeur !== '' && $valeur !== null) {
            $parts[] = "-{$valeur}% de remise";
        }

        // Scope
        if ($scope === 'whole_order') {
            $parts[] = "sur l’addition";
        } elseif ($scope === 'product_categories' && $cats) {
            $parts[] = "sur les catégories : $cats";
        } elseif ($scope === 'specific_products' && $prods) {
            $parts[] = "sur les produits : $prods";
        } elseif ($scope === 'menu_only' && $menu) {
            $parts[] = "sur le menu « $menu »";
        }

        // Montant max
        if ($valeur_max) {
            $parts[] = "(jusqu’à {$valeur_max}€ maximum)";
        }

        // Dates
        if ($date_debut && $date_fin) {
            $parts[] = "du $date_debut au $date_fin";
        } elseif ($date_debut) {
            $parts[] = "à partir du $date_debut";
        }

        // Jours semaine
        if ($jours && is_array($jours)) {
            $jours_txt = $this->convert_days_to_text($jours);
            $parts[] = "valable $jours_txt";
        }
        if ($services && is_array($services)) {
            $labels = [];
            if (in_array('dejeuner', $services, true)) $labels[] = 'au déjeuner';
            if (in_array('diner', $services, true)) $labels[] = 'au dîner';

            if ($labels) {
                $parts[] = implode(' et ', $labels);
            }
        }
        // Couverts
        if ($min_couv && $max_couv) {
            $parts[] = "pour $min_couv à $max_couv couverts";
        } elseif ($min_couv) {
            $parts[] = "à partir de $min_couv couverts";
        }

        // Montant min
        if ($montant_min) {
            $parts[] = "pour un minimum de {$montant_min}€ de commande";
        }

        return implode(' ', $parts) . '.';
    }

    /**
     * Convertit les codes jours (mon,tue...) en texte FR
     */
    public function convert_days_to_text($jours)
    {
        // ordre + labels FR
        $order = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $map = [
            'mon' => 'lundi',
            'tue' => 'mardi',
            'wed' => 'mercredi',
            'thu' => 'jeudi',
            'fri' => 'vendredi',
            'sat' => 'samedi',
            'sun' => 'dimanche',
        ];

        if (!is_array($jours) || empty($jours)) {
            return '';
        }

        // On garde les jours dans le bon ordre et on nettoie
        $codes = [];
        foreach ($order as $code) {
            if (in_array($code, $jours, true) && isset($map[$code])) {
                $codes[] = $code;
            }
        }

        if (empty($codes)) {
            return '';
        }

        // Construire les "runs" de jours consécutifs
        $runs = [];
        $current_run = [$codes[0]];

        for ($i = 1; $i < count($codes); $i++) {
            $prev_index = array_search($codes[$i - 1], $order, true);
            $curr_index = array_search($codes[$i], $order, true);

            if ($curr_index === $prev_index + 1) {
                // consécutif → même run
                $current_run[] = $codes[$i];
            } else {
                // nouveau run
                $runs[] = $current_run;
                $current_run = [$codes[$i]];
            }
        }
        $runs[] = $current_run;

        // Transformer chaque run en morceau de phrase
        $segments = [];

        foreach ($runs as $run) {
            $labels = array_map(function ($code) use ($map) {
                return $map[$code];
            }, $run);

            $count = count($labels);

            if ($count >= 3) {
                // intervalle : du lundi au mercredi
                $segments[] = 'du ' . $labels[0] . ' au ' . $labels[$count - 1];
            } elseif ($count === 2) {
                // deux jours consécutifs : les lundi et mardi
                $segments[] = 'les ' . $labels[0] . ' et ' . $labels[1];
            } elseif ($count === 1) {
                // jour isolé : le dimanche
                $segments[] = 'le ' . $labels[0];
            }
        }

        if (empty($segments)) {
            return '';
        }

        // Assembler les segments
        if (count($segments) === 1) {
            // un seul bloc
            return $segments[0];
        }

        if (count($segments) === 2) {
            // deux blocs : "du lundi au mercredi et le dimanche"
            return $segments[0] . ' et ' . $segments[1];
        }

        // 3 blocs ou plus : "du lundi au mercredi, les vendredi et samedi, et le dimanche"
        $last = array_pop($segments);
        $first = implode(', ', $segments);
        return $first . ', et ' . $last;
    }

    public function reset_swap_active_status_remise(WP_REST_Request $request)
    {
        $params = $request->get_json_params();

        $id_remise     = isset($params['id'])            ? (int) $params['id']            : null;
        $restaurant_id = isset($params['restaurant_id']) ? (int) $params['restaurant_id'] : null;

        if (!$id_remise || !$restaurant_id) {
            return new WP_Error('missing_params', 'Paramètres manquants.', ['status' => 400]);
        }

        $post = get_post($id_remise);
        if (!$post || $post->post_type !== 'remise') {
            return new WP_Error('not_found', 'Remise introuvable.', ['status' => 404]);
        }

        // Simple toggle
        $current = (bool) get_field('remise_active', $id_remise);
        update_field('remise_active', !$current, $id_remise);

        return [
            'success' => true,
            'remise_active' => !$current,
        ];
    }
}

MRDS_Remises_management::get_instance();