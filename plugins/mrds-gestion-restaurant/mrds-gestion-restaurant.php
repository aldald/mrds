<?php

/**
 * Plugin Name: MRDS - Gestion Restaurant
 * Description: Gestion des restaurants + rôles Restaurateur / Super-Restaurateur.
 * Author: Coccinet
 * Version: 0.3.0
 * Text Domain: mrds-gestion-restaurant
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Gestion_Restaurant
{

    protected static $instance = null;

    protected $restaurant_post_type = 'restaurant';

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);

        register_activation_hook(__FILE__, ['MRDS_Gestion_Restaurant', 'activate']);
        register_deactivation_hook(__FILE__, ['MRDS_Gestion_Restaurant', 'deactivate']);

        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_shortcode('mrds_restaurant_manager', [$this, 'render_restaurant_manager']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        add_filter('manage_edit-restaurant_columns', [$this, 'add_administre_par_column']);
        add_action('manage_restaurant_posts_custom_column', [$this, 'render_administre_par_column'], 10, 2);

        add_filter('manage_edit-restaurant_sortable_columns', [$this, 'make_administre_par_column_sortable']);
        add_action('pre_get_posts', [$this, 'sort_administre_par_column']);

        // Auto-activer les remises liées lors de la sauvegarde d'un restaurant
        add_action('acf/save_post', [$this, 'auto_activate_remises_liees'], 20);
    }

    public static function activate()
    {
        $instance = self::get_instance();
        $instance->register_post_types();
        $instance->register_taxonomies();
        $instance->register_roles();
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Auto-activer les remises liées lors de la sauvegarde WP-Admin d'un restaurant.
     * Gère aussi la sync inverse : met à jour restaurant_concerne et _mrds_previous_restaurants
     * sur chaque remise ajoutée ou retirée du champ remises_liees.
     *
     * @param int $post_id ID du post sauvegardé
     */
    public function auto_activate_remises_liees($post_id)
    {
        // Uniquement pour le CPT restaurant
        if (get_post_type($post_id) !== $this->restaurant_post_type) {
            return;
        }

        // Remises actuellement liées (après sauvegarde ACF)
        $remises_liees = get_field('remises_liees', $post_id);
        $remises_liees = is_array($remises_liees) ? $remises_liees : [];

        $nouveaux_ids = array_values(array_filter(array_map(function ($r) {
            return is_object($r) ? (int) $r->ID : (int) $r;
        }, $remises_liees)));

        // Remises qui ont ce restaurant dans restaurant_concerne en base (source de vérité)
        $anciens_ids = get_posts([
            'post_type'      => 'remise',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [[
                'key'     => 'restaurant_concerne',
                'value'   => '"' . (int) $post_id . '"',
                'compare' => 'LIKE',
            ]],
        ]);
        $anciens_ids = array_map('intval', (array) $anciens_ids);

        // 1. Remises retirées → retirer ce restaurant de leur restaurant_concerne
        $retirees = array_diff($anciens_ids, $nouveaux_ids);
        foreach ($retirees as $remise_id) {
            $resto_concerne = get_field('restaurant_concerne', $remise_id);
            $resto_concerne = is_array($resto_concerne) ? $resto_concerne : [];
            $ids_resto = array_map(fn($r) => is_object($r) ? (int) $r->ID : (int) $r, $resto_concerne);
            $ids_resto = array_values(array_filter($ids_resto, fn($i) => $i !== (int) $post_id));
            update_field('restaurant_concerne', $ids_resto, $remise_id);

            // Sync _mrds_previous_restaurants sur la remise
            $prev = get_post_meta($remise_id, '_mrds_previous_restaurants', true);
            $prev = is_array($prev) ? $prev : [];
            $prev = array_values(array_filter($prev, fn($i) => $i !== (int) $post_id));
            update_post_meta($remise_id, '_mrds_previous_restaurants', $prev);
        }

        // 2. Remises ajoutées ou déjà présentes → activer + sync restaurant_concerne
        foreach ($nouveaux_ids as $remise_id) {
            // Activer la remise si inactive
            if (!get_field('remise_active', $remise_id)) {
                update_field('remise_active', true, $remise_id);
            }

            // Ajouter ce restaurant dans restaurant_concerne sur la remise si absent
            $resto_concerne = get_field('restaurant_concerne', $remise_id);
            $resto_concerne = is_array($resto_concerne) ? $resto_concerne : [];
            $ids_resto = array_map(fn($r) => is_object($r) ? (int) $r->ID : (int) $r, $resto_concerne);
            if (!in_array((int) $post_id, $ids_resto, true)) {
                $ids_resto[] = (int) $post_id;
                update_field('restaurant_concerne', $ids_resto, $remise_id);
            }

            // Sync _mrds_previous_restaurants sur la remise
            $prev = get_post_meta($remise_id, '_mrds_previous_restaurants', true);
            $prev = is_array($prev) ? $prev : [];
            if (!in_array((int) $post_id, $prev, true)) {
                $prev[] = (int) $post_id;
                update_post_meta($remise_id, '_mrds_previous_restaurants', $prev);
            }
        }
    }

    public function make_administre_par_column_sortable($columns)
    {
        $columns['restaurant_owner'] = 'restaurant_owner';
        return $columns;
    }

    public function sort_administre_par_column($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');
        if ('restaurant_owner' === $orderby) {
            $query->set('meta_key', 'restaurant_owner');
            $query->set('orderby', 'meta_value');
        }
    }

    public function enqueue_assets()
    {
        if (!is_user_logged_in()) {
            return;
        }

        wp_register_script(
            'mrds-restaurant-manager',
            plugin_dir_url(__FILE__) . 'assets/js/restaurant-manager.js',
            ['wp-api-fetch'],
            '0.3.0',
            true
        );

        wp_localize_script(
            'mrds-restaurant-manager',
            'MRDSRestaurantConfig',
            [
                'restUrl' => esc_url_raw(rest_url('mrds/v1/restaurants')),
                'nonce' => wp_create_nonce('wp_rest'),
            ]
        );

        wp_enqueue_script('mrds-restaurant-manager');

        wp_add_inline_style(
            'wp-block-library',
            '.mrds-badge{display:inline-block;padding:.15rem .4rem;border-radius:.25rem;font-size:.75rem;background:#f1f3f5;margin-right:.25rem;}'
        );

        wp_enqueue_style(
            'mrds-resa-form',
            plugin_dir_url(__FILE__) . 'assets/css/gestion-restaurant.css',
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/gestion-restaurant.css')
        );

        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            array(),
            '6.5.1'
        );
    }

    public function register_post_types()
    {
        $labels = [
            'name' => __('Restaurants', 'mrds-gestion-restaurant'),
            'singular_name' => __('Restaurant', 'mrds-gestion-restaurant'),
            'add_new' => __('Ajouter un restaurant', 'mrds-gestion-restaurant'),
            'add_new_item' => __('Ajouter un nouveau restaurant', 'mrds-gestion-restaurant'),
            'edit_item' => __('Modifier le restaurant', 'mrds-gestion-restaurant'),
            'new_item' => __('Nouveau restaurant', 'mrds-gestion-restaurant'),
            'view_item' => __('Voir le restaurant', 'mrds-gestion-restaurant'),
            'search_items' => __('Rechercher des restaurants', 'mrds-gestion-restaurant'),
            'not_found' => __('Aucun restaurant trouvé', 'mrds-gestion-restaurant'),
            'not_found_in_trash' => __('Aucun restaurant dans la corbeille', 'mrds-gestion-restaurant'),
            'menu_name' => __('Restaurants', 'mrds-gestion-restaurant'),
        ];

        $caps = [
            'edit_post' => 'edit_restaurant',
            'read_post' => 'read_restaurant',
            'delete_post' => 'delete_restaurant',
            'edit_posts' => 'edit_restaurants',
            'edit_others_posts' => 'edit_others_restaurants',
            'publish_posts' => 'publish_restaurants',
            'read_private_posts' => 'read_private_restaurants',
            'delete_posts' => 'delete_restaurants',
            'delete_private_posts' => 'delete_private_restaurants',
            'delete_published_posts' => 'delete_published_restaurants',
            'delete_others_posts' => 'delete_others_restaurants',
            'edit_private_posts' => 'edit_private_restaurants',
            'edit_published_posts' => 'edit_published_restaurants',
            'create_posts' => 'publish_restaurants',
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-store',
            'supports' => ['title', 'editor', 'thumbnail'],
            'has_archive' => false,
            'rewrite' => ['slug' => 'restaurant'],
            'capability_type' => ['restaurant', 'restaurants'],
            'map_meta_cap' => true,
            'capabilities' => $caps,
            'show_in_rest' => true,
        ];

        register_post_type($this->restaurant_post_type, $args);
    }

    public function canUser($capability)
    {
        return current_user_can($capability);
    }

    public function register_taxonomies()
    {
        $labels_cuisine = [
            'name' => __('Type de cuisine', 'mrds-gestion-restaurant'),
            'singular_name' => __('Type de cuisine', 'mrds-gestion-restaurant'),
            'search_items' => __('Rechercher un type de cuisine', 'mrds-gestion-restaurant'),
            'all_items' => __('Tous les types de cuisine', 'mrds-gestion-restaurant'),
            'edit_item' => __('Modifier le type de cuisine', 'mrds-gestion-restaurant'),
            'update_item' => __('Mettre à jour', 'mrds-gestion-restaurant'),
            'add_new_item' => __('Ajouter un type de cuisine', 'mrds-gestion-restaurant'),
            'menu_name' => __('Type de cuisine', 'mrds-gestion-restaurant'),
        ];

        register_taxonomy(
            'type_cuisine',
            [$this->restaurant_post_type],
            [
                'labels' => $labels_cuisine,
                'hierarchical' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'cuisine'],
                'show_in_rest' => true,
            ]
        );

        $labels_tags = [
            'name' => __('Tag restaurant', 'mrds-gestion-restaurant'),
            'singular_name' => __('Tag restaurant', 'mrds-gestion-restaurant'),
            'search_items' => __('Rechercher un tag', 'mrds-gestion-restaurant'),
            'all_items' => __('Tous les tags', 'mrds-gestion-restaurant'),
            'edit_item' => __('Modifier le tag', 'mrds-gestion-restaurant'),
            'update_item' => __('Mettre à jour', 'mrds-gestion-restaurant'),
            'add_new_item' => __('Ajouter un tag', 'mrds-gestion-restaurant'),
            'menu_name' => __('Tag restaurant', 'mrds-gestion-restaurant'),
        ];

        register_taxonomy(
            'restaurant_tag',
            [$this->restaurant_post_type],
            [
                'labels' => $labels_tags,
                'hierarchical' => false,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'restaurant-tag'],
                'show_in_rest' => true,
            ]
        );
    }

    public function register_roles()
    {
        $common_caps = [
            'read' => true,
        ];

        $restaurateur_caps = array_merge($common_caps, [
            'read_restaurant' => true,
            'read_private_restaurants' => true,
            'edit_restaurant' => true,
            'edit_restaurants' => true,
            'edit_published_restaurants' => true,
            'upload_files' => true,

            'publish_restaurants' => false,
            'manage_own_restaurants' => true,
            'manage_own_restaurant_remises' => true,
        ]);

        $super_restaurateur_caps = array_merge($restaurateur_caps, [
            'publish_restaurants' => true,
            'delete_restaurant' => true,
            'delete_restaurants' => true,
            'delete_others_restaurants' => true,
            'edit_others_restaurants' => true,
            'delete_published_restaurants' => true,
            'manage_restaurant_network' => true,
        ]);

        add_role(
            'restaurateur',
            __('Restaurateur', 'mrds-gestion-restaurant'),
            $restaurateur_caps
        );

        add_role(
            'super_restaurateur',
            __('Super-Restaurateur', 'mrds-gestion-restaurant'),
            $super_restaurateur_caps
        );

        $admin = get_role('administrator');

        if ($admin) {
            $admin_caps = [
                'read_restaurant',
                'read_private_restaurants',
                'edit_restaurant',
                'edit_restaurants',
                'edit_others_restaurants',
                'edit_published_restaurants',
                'publish_restaurants',
                'delete_restaurant',
                'delete_restaurants',
                'delete_others_restaurants',
                'delete_published_restaurants',
                'manage_own_restaurants',
                'manage_own_restaurant_remises',
                'manage_restaurant_network',
            ];

            foreach ($admin_caps as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    public function register_rest_routes()
    {
        register_rest_route(
            'mrds/v1',
            '/restaurants',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'rest_list_restaurants'],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'rest_create_restaurant'],
                    'permission_callback' => function () {
                        return is_user_logged_in() && current_user_can('publish_restaurants');
                    },
                ],
            ]
        );

        register_rest_route(
            'mrds/v1',
            '/restaurants/(?P<id>\d+)',
            [
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'rest_update_restaurant'],
                    'permission_callback' => function (WP_REST_Request $request) {
                        $id = (int) $request['id'];
                        $user = get_current_user_id();
                        if (current_user_can('administrator')) {
                            return true;
                        }
                        return $this->user_can_manage_restaurant($user, $id);
                    },
                ],
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [$this, 'rest_delete_restaurant'],
                    'permission_callback' => function (WP_REST_Request $request) {
                        $id = (int) $request['id'];
                        $user = get_current_user_id();
                        if (current_user_can('administrator')) {
                            return true;
                        }
                        return $this->user_can_manage_restaurant($user, $id) && current_user_can('delete_restaurant', $id);
                    },
                ],
            ]
        );

        // Endpoint pour upload image principale (thumbnail)
        register_rest_route(
            'mrds/v1',
            '/restaurants/(?P<id>\d+)/image',
            [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'rest_upload_image'],
                'permission_callback' => function (WP_REST_Request $request) {
                    $id = (int) $request['id'];
                    $user = get_current_user_id();
                    if (current_user_can('administrator')) {
                        return true;
                    }
                    return $this->user_can_manage_restaurant($user, $id);
                },
            ]
        );

        // Endpoint pour upload galerie (max 3 images)
        register_rest_route(
            'mrds/v1',
            '/restaurants/(?P<id>\d+)/gallery',
            [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'rest_upload_gallery'],
                'permission_callback' => function (WP_REST_Request $request) {
                    $id = (int) $request['id'];
                    $user = get_current_user_id();
                    if (current_user_can('administrator')) {
                        return true;
                    }
                    return $this->user_can_manage_restaurant($user, $id);
                },
            ]
        );

        // Endpoint pour supprimer une image de la galerie
        register_rest_route(
            'mrds/v1',
            '/restaurants/(?P<id>\d+)/gallery/(?P<image_id>\d+)',
            [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'rest_delete_gallery_image'],
                'permission_callback' => function (WP_REST_Request $request) {
                    $id = (int) $request['id'];
                    $user = get_current_user_id();
                    if (current_user_can('administrator')) {
                        return true;
                    }
                    return $this->user_can_manage_restaurant($user, $id);
                },
            ]
        );
    }

    /**
     * Upload image principale (thumbnail)
     */
    public function rest_upload_image(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        
        $post = get_post($id);
        if (!$post || $post->post_type !== $this->restaurant_post_type) {
            return new WP_Error('mrds_invalid_restaurant', 'Restaurant introuvable.', ['status' => 404]);
        }

        if (empty($_FILES['image'])) {
            return new WP_Error('mrds_no_file', 'Aucun fichier envoye.', ['status' => 400]);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('image', $id);

        if (is_wp_error($attachment_id)) {
            return new WP_Error('mrds_upload_failed', $attachment_id->get_error_message(), ['status' => 500]);
        }

        set_post_thumbnail($id, $attachment_id);

        return [
            'success'       => true,
            'attachment_id' => $attachment_id,
            'url'           => wp_get_attachment_image_url($attachment_id, 'medium'),
        ];
    }

    /**
     * Upload image dans la galerie (max 3)
     */
    public function rest_upload_gallery(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        
        $post = get_post($id);
        if (!$post || $post->post_type !== $this->restaurant_post_type) {
            return new WP_Error('mrds_invalid_restaurant', 'Restaurant introuvable.', ['status' => 404]);
        }

        if (empty($_FILES['image'])) {
            return new WP_Error('mrds_no_file', 'Aucun fichier envoye.', ['status' => 400]);
        }

        // Recuperer la galerie actuelle et convertir en IDs
        $current_gallery_raw = get_field('gallerie', $id);
        $current_gallery = [];
        if (is_array($current_gallery_raw)) {
            foreach ($current_gallery_raw as $gal_item) {
                if (is_array($gal_item) && isset($gal_item['ID'])) {
                    $current_gallery[] = (int) $gal_item['ID'];
                } elseif (is_array($gal_item) && isset($gal_item['id'])) {
                    $current_gallery[] = (int) $gal_item['id'];
                } elseif (is_numeric($gal_item)) {
                    $current_gallery[] = (int) $gal_item;
                }
            }
        }

        // Verifier le max 3
        if (count($current_gallery) >= 3) {
            return new WP_Error('mrds_gallery_full', 'La galerie est limitee a 3 images.', ['status' => 400]);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('image', $id);

        if (is_wp_error($attachment_id)) {
            return new WP_Error('mrds_upload_failed', $attachment_id->get_error_message(), ['status' => 500]);
        }

        // Ajouter a la galerie
        $current_gallery[] = $attachment_id;
        update_field('gallerie', $current_gallery, $id);

        return [
            'success'       => true,
            'attachment_id' => $attachment_id,
            'url'           => wp_get_attachment_image_url($attachment_id, 'medium'),
            'gallery_count' => count($current_gallery),
        ];
    }

    /**
     * Supprimer une image de la galerie
     */
    public function rest_delete_gallery_image(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $image_id = (int) $request['image_id'];
        
        $post = get_post($id);
        if (!$post || $post->post_type !== $this->restaurant_post_type) {
            return new WP_Error('mrds_invalid_restaurant', 'Restaurant introuvable.', ['status' => 404]);
        }

        $current_gallery = get_field('gallerie', $id);
        if (!is_array($current_gallery)) {
            $current_gallery = [];
        }

        // Retirer l image de la galerie (gere les formats ID et Array)
        $new_gallery = [];
        foreach ($current_gallery as $gal_item) {
            $item_id = 0;
            if (is_array($gal_item)) {
                $item_id = isset($gal_item['ID']) ? (int) $gal_item['ID'] : (isset($gal_item['id']) ? (int) $gal_item['id'] : 0);
            } elseif (is_numeric($gal_item)) {
                $item_id = (int) $gal_item;
            }
            
            if ($item_id !== $image_id) {
                $new_gallery[] = $item_id > 0 ? $item_id : $gal_item;
            }
        }

        update_field('gallerie', array_values($new_gallery), $id);

        return [
            'success'       => true,
            'gallery_count' => count($new_gallery),
        ];
    }

    public function rest_list_restaurants(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();

        if (current_user_can('administrator')) {
            $args = [
                'post_type' => $this->restaurant_post_type,
                'posts_per_page' => -1,
                'post_status' => ['publish', 'draft'],
            ];
        } else {
            $allowed_ids = $this->get_user_restaurants($user_id);
            if (empty($allowed_ids)) {
                return [];
            }
            $args = [
                'post_type' => $this->restaurant_post_type,
                'posts_per_page' => -1,
                'post_status' => ['publish', 'draft'],
                'post__in' => $allowed_ids,
            ];
        }

        $posts = get_posts($args);
        $data = [];

        foreach ($posts as $post) {
            $id = $post->ID;

            // Image principale (thumbnail)
            $thumbnail_id  = get_post_thumbnail_id($id);
            $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : null;

            // Galerie (gere tous les formats ACF: ID, URL, ou Array)
            $gallery_raw = get_field('gallerie', $id);
            $gallery = [];
            if (is_array($gallery_raw) && !empty($gallery_raw)) {
                foreach ($gallery_raw as $gal_item) {
                    // Si c'est un tableau (format "Image Array")
                    if (is_array($gal_item)) {
                        $gallery[] = [
                            'id'  => isset($gal_item['ID']) ? $gal_item['ID'] : (isset($gal_item['id']) ? $gal_item['id'] : 0),
                            'url' => isset($gal_item['sizes']['medium']) ? $gal_item['sizes']['medium'] : (isset($gal_item['url']) ? $gal_item['url'] : ''),
                        ];
                    }
                    // Si c'est un ID (format "Image ID")
                    elseif (is_numeric($gal_item)) {
                        $gallery[] = [
                            'id'  => (int) $gal_item,
                            'url' => wp_get_attachment_image_url((int) $gal_item, 'medium'),
                        ];
                    }
                    // Si c'est une URL (format "Image URL")
                    elseif (is_string($gal_item) && filter_var($gal_item, FILTER_VALIDATE_URL)) {
                        $gallery[] = [
                            'id'  => 0,
                            'url' => $gal_item,
                        ];
                    }
                }
            }

            $adresse = get_field('field_693144b5c6b70', $id);
            $adresse_rue = isset($adresse['adresse_rue']) ? $adresse['adresse_rue'] : '';
            $code_postal = isset($adresse['code_postal']) ? $adresse['code_postal'] : '';
            $ville = isset($adresse['ville']) ? $adresse['ville'] : '';
            $arrondissement = isset($adresse['arrondissement']) ? $adresse['arrondissement'] : '';

            $telephone = get_field('telephone', $id);
            $site_web = get_field('site_web', $id);

            $type_cuisine_ids = get_field('type_de_cuisine', $id);
            $tags_ids = get_field('tags_restaurant', $id);

            $description_menu = get_field('description_menu', $id);
            $exemple_plats = get_field('exemple_de_plats', $id);

            $citation = get_field('citation_de_restaurant', $id);

            $owner = get_field('restaurant_owner', $id);
            $restaurateurs = get_field('restaurant_restaurateurs', $id);

            $tarifs = get_field('tarifs', $id);
            $horaires = get_field('horaires', $id);

            $type_cuisine_labels = [];
            if ($type_cuisine_ids) {
                $ids = is_array($type_cuisine_ids) ? $type_cuisine_ids : [$type_cuisine_ids];
                foreach ($ids as $tid) {
                    $term = get_term((int) $tid, 'type_cuisine');
                    if ($term && !is_wp_error($term)) {
                        $type_cuisine_labels[] = $term->name;
                    }
                }
            }

            $tags_labels = [];
            if ($tags_ids) {
                $ids = is_array($tags_ids) ? $tags_ids : [$tags_ids];
                foreach ($ids as $tid) {
                    $term = get_term((int) $tid, 'restaurant_tag');
                    if ($term && !is_wp_error($term)) {
                        $tags_labels[] = $term->name;
                    }
                }
            }

            $owner_data = null;
            if ($owner instanceof WP_User) {
                $owner_data = [
                    'id' => $owner->ID,
                    'display_name' => $owner->ID == get_current_user_id() ? 'Moi' : $owner->display_name,
                    'email' => $owner->user_email,
                ];
            }

            $restaurateurs_data = [];
            if (is_array($restaurateurs)) {
                foreach ($restaurateurs as $u) {
                    if ($u instanceof WP_User) {
                        $restaurateurs_data[] = [
                            'id' => $u->ID,
                            'display_name' => $u->display_name,
                            'email' => $u->user_email,
                        ];
                    }
                }
            }

            $data[] = [
                'id' => $id,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'lien_restaurant' => get_permalink($id),
                'lien_remises' => get_permalink(144) . '?restaurant_id=' . $id,
                'adresse_rue' => $adresse_rue,
                'code_postal' => $code_postal,
                'ville' => $ville,
                'arrondissement' => $arrondissement,

                'telephone' => $telephone,
                'site_web' => $site_web,

                'type_cuisine' => $type_cuisine_ids,
                'type_cuisine_labels' => $type_cuisine_labels,
                'tags_restaurant' => $tags_ids,
                'tags_labels' => $tags_labels,

                'description_menu' => $description_menu,
                'exemple_de_plats' => $exemple_plats,
                'citation' => $citation,
                'tarifs' => $tarifs,
                'horaires' => $horaires,

                'owner' => $owner_data,
                'restaurateurs' => $restaurateurs_data,

                'thumbnail_id'  => $thumbnail_id,
                'thumbnail_url' => $thumbnail_url,
                'gallery'       => $gallery,
            ];
        }

        return $data;
    }

    public function rest_create_restaurant(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        $title = isset($params['title']) ? sanitize_text_field($params['title']) : '';

        if (!$title) {
            return new WP_Error('mrds_no_title', 'Le titre est obligatoire.', ['status' => 400]);
        }

        $current_user = get_current_user_id();

        $post_id = wp_insert_post([
            'post_type' => $this->restaurant_post_type,
            'post_title' => $title,
            'post_status' => 'publish',
            'post_author' => $current_user,
        ], true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        if (empty($params['restaurant_owner']) && current_user_can('super_restaurateur')) {
            $params['restaurant_owner'] = $current_user;
        }

        $this->save_restaurant_meta_from_params($post_id, $params);

        return $this->rest_list_single_restaurant($post_id);
    }

    public function rest_update_restaurant(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $params = $request->get_json_params();

        $post = get_post($id);
        if (!$post || $post->post_type !== $this->restaurant_post_type) {
            return new WP_Error('mrds_invalid_restaurant', 'Restaurant introuvable.', ['status' => 404]);
        }

        if (isset($params['title']) && $params['title'] !== '') {
            wp_update_post([
                'ID' => $id,
                'post_title' => sanitize_text_field($params['title']),
            ]);
        }

        $this->save_restaurant_meta_from_params($id, $params);

        return $this->rest_list_single_restaurant($id);
    }

    public function rest_delete_restaurant(WP_REST_Request $request)
    {
        $id = (int) $request['id'];

        $post = get_post($id);
        if (!$post || $post->post_type !== $this->restaurant_post_type) {
            return new WP_Error('mrds_invalid_restaurant', 'Restaurant introuvable.', ['status' => 404]);
        }

        $result = wp_trash_post($id);
        if (!$result) {
            return new WP_Error('mrds_delete_failed', 'Impossible de supprimer ce restaurant.', ['status' => 500]);
        }

        return ['success' => true];
    }

    protected function rest_list_single_restaurant($id)
    {
        $request = new WP_REST_Request('GET', '/mrds/v1/restaurants');
        $data = $this->rest_list_restaurants($request);
        foreach ($data as $item) {
            if ((int) $item['id'] === (int) $id) {
                return $item;
            }
        }
        return new WP_Error('mrds_not_found', 'Restaurant introuvable apres mise a jour.', ['status' => 404]);
    }

    protected function save_restaurant_meta_from_params($post_id, $params)
    {
        $adresse = [];

        if (isset($params['adresse_rue'])) {
            $adresse['adresse_rue'] = sanitize_text_field($params['adresse_rue']);
        }
        if (isset($params['adresse_complement'])) {
            $adresse["complement_d'adresse"] = sanitize_text_field($params['adresse_complement']);
        }
        if (isset($params['code_postal'])) {
            $adresse['code_postal'] = sanitize_text_field($params['code_postal']);
        }
        if (isset($params['ville'])) {
            $adresse['ville'] = sanitize_text_field($params['ville']);
        }
        if (isset($params['arrondissement'])) {
            $adresse['arrondissement'] = sanitize_text_field($params['arrondissement']);
        }

        if (!empty($adresse)) {
            update_field('field_693144b5c6b70', $adresse, $post_id);
        }

        if (isset($params['telephone'])) {
            update_field('telephone', sanitize_text_field($params['telephone']), $post_id);
        }

        if (isset($params['site_web'])) {
            update_field('site_web', esc_url_raw($params['site_web']), $post_id);
        }

        if (isset($params['type_de_cuisine'])) {
            $ids = $params['type_de_cuisine'];
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            $ids = array_filter(array_map('intval', $ids));
            update_field('type_de_cuisine', $ids, $post_id);
            wp_set_object_terms($post_id, $ids, 'type_cuisine', false);
        }

        if (isset($params['tags_restaurant'])) {
            $ids = $params['tags_restaurant'];
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            $ids = array_filter(array_map('intval', $ids));
            update_field('tags_restaurant', $ids, $post_id);
            wp_set_object_terms($post_id, $ids, 'restaurant_tag', false);
        }

        $citation = [];
        if (isset($params['citation_description'])) {
            $citation['description'] = sanitize_textarea_field($params['citation_description']);
        }
        if (isset($params['citation_auteur'])) {
            $citation['auteur'] = sanitize_text_field($params['citation_auteur']);
        }
        if (!empty($citation)) {
            update_field('citation_de_restaurant', $citation, $post_id);
        }

        if (isset($params['description_menu'])) {
            update_field('description_menu', sanitize_textarea_field($params['description_menu']), $post_id);
        }
        if (isset($params['exemple_de_plats'])) {
            update_field('exemple_de_plats', sanitize_textarea_field($params['exemple_de_plats']), $post_id);
        }

        if (isset($params['tarifs']) && is_array($params['tarifs'])) {
            $rows = [];
            foreach ($params['tarifs'] as $row) {
                if (empty($row['nom_de_menu']) && empty($row['prix'])) {
                    continue;
                }
                $rows[] = [
                    'nom_de_menu' => isset($row['nom_de_menu']) ? sanitize_text_field($row['nom_de_menu']) : '',
                    'prix' => isset($row['prix']) ? (float) $row['prix'] : '',
                ];
            }
            update_field('tarifs', $rows, $post_id);
        }

        if (isset($params['horaires']) && is_array($params['horaires'])) {
            $rows = [];
            foreach ($params['horaires'] as $row) {
                if (empty($row['periode']) && empty($row['jours'])) {
                    continue;
                }
                $jours = isset($row['jours']) && is_array($row['jours'])
                    ? array_map('sanitize_text_field', $row['jours'])
                    : [];
                $rows[] = [
                    'periode' => isset($row['periode']) ? sanitize_text_field($row['periode']) : '',
                    'jours' => $jours,
                ];
            }
            update_field('horaires', $rows, $post_id);
        }

        if (isset($params['restaurant_owner'])) {
            $uid = (int) $params['restaurant_owner'];
            if ($uid > 0) {
                update_field('restaurant_owner', $uid, $post_id);
            }
        }

        if (isset($params['restaurant_restaurateurs']) && is_array($params['restaurant_restaurateurs'])) {
            $ids = array_filter(array_map('intval', $params['restaurant_restaurateurs']));
            update_field('restaurant_restaurateurs', $ids, $post_id);
        }
    }

    public function add_administre_par_column($columns)
    {
        $new_columns = [];

        foreach ($columns as $key => $label) {
            if ($key === 'date') {
                $new_columns['restaurant_owner'] = __('Administre par', 'mrds-gestion-restaurant');
            }
            $new_columns[$key] = $label;
        }

        if (!isset($new_columns['restaurant_owner'])) {
            $new_columns['restaurant_owner'] = __('Administre par', 'mrds-gestion-restaurant');
        }

        return $new_columns;
    }

    public function render_administre_par_column($column, $post_id)
    {
        if ('restaurant_owner' !== $column) {
            return;
        }

        $owner = get_field('restaurant_owner', $post_id);

        if ($owner instanceof WP_User) {
            echo esc_html($owner->display_name);
            if ($owner->user_email) {
                echo '<br><small>' . esc_html($owner->user_email) . '</small>';
            }
        } elseif (is_numeric($owner)) {
            $user = get_user_by('id', (int) $owner);
            if ($user) {
                echo esc_html($user->display_name);
                if ($user->user_email) {
                    echo '<br><small>' . esc_html($user->user_email) . '</small>';
                }
            } else {
                echo '<em>' . esc_html__('Non defini', 'mrds-gestion-restaurant') . '</em>';
            }
        } else {
            echo '<em>' . esc_html__('Non defini', 'mrds-gestion-restaurant') . '</em>';
        }
    }

public function render_restaurant_manager()
{
    // Si non connecté → message convivial avec bouton connexion
    if (!is_user_logged_in()) {
        ob_start();
        ?>
        <style>
            .mrds-access-denied {
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 40px 20px;
                min-height: 40vh;
            }
            .mrds-access-denied-box {
                background-color: #ffffff;
                border-radius: 0;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                padding: 40px;
                width: 100%;
                max-width: 450px;
                text-align: center;
            }
            .mrds-access-denied-box h2 {
                color: #141B42;
                font-size: 24px;
                font-weight: 600;
                margin: 0 0 15px;
            }
            .mrds-access-denied-box p {
                color: #636363;
                font-size: 14px;
                line-height: 1.6;
                margin: 0 0 25px;
            }
            .mrds-access-denied-box .mrds-btn {
                display: inline-block;
                background-color: #DA9D42;
                color: #ffffff !important;
                border: none;
                border-radius: 0;
                padding: 14px 30px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                transition: background-color 0.3s ease;
            }
            .mrds-access-denied-box .mrds-btn:hover {
                background-color: #c98c3a;
            }
        </style>
        <div class="mrds-access-denied">
            <div class="mrds-access-denied-box">
                <h2><?php esc_html_e('Connexion requise', 'mrds-gestion-restaurant'); ?></h2>
                <p><?php esc_html_e('Vous devez être connecté pour accéder à la gestion des restaurants.', 'mrds-gestion-restaurant'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="mrds-btn">
                    <?php esc_html_e('Se connecter', 'mrds-gestion-restaurant'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    $user = wp_get_current_user();

    // Si pas le bon rôle → message accès refusé
    if (
        !in_array('administrator', (array) $user->roles, true) &&
        !in_array('super_restaurateur', (array) $user->roles, true) &&
        !in_array('restaurateur', (array) $user->roles, true)
    ) {
        ob_start();
        ?>
        <style>
            .mrds-access-denied {
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 40px 20px;
                min-height: 40vh;
            }
            .mrds-access-denied-box {
                background-color: #ffffff;
                border-radius: 0;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                padding: 40px;
                width: 100%;
                max-width: 450px;
                text-align: center;
            }
            .mrds-access-denied-box h2 {
                color: #141B42;
                font-size: 24px;
                font-weight: 600;
                margin: 0 0 15px;
            }
            .mrds-access-denied-box p {
                color: #636363;
                font-size: 14px;
                line-height: 1.6;
                margin: 0 0 25px;
            }
            .mrds-access-denied-box .mrds-btn {
                display: inline-block;
                background-color: #DA9D42;
                color: #ffffff !important;
                border: none;
                border-radius: 0;
                padding: 14px 30px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                transition: background-color 0.3s ease;
            }
            .mrds-access-denied-box .mrds-btn:hover {
                background-color: #c98c3a;
            }
        </style>
        <div class="mrds-access-denied">
            <div class="mrds-access-denied-box">
                <h2><?php esc_html_e('Accès refusé', 'mrds-gestion-restaurant'); ?></h2>
                <p><?php esc_html_e('Vous n\'avez pas les droits nécessaires pour accéder à cette page. Cette section est réservée aux restaurateurs.', 'mrds-gestion-restaurant'); ?></p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="mrds-btn">
                    <?php esc_html_e('Retour à l\'accueil', 'mrds-gestion-restaurant'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    ob_start();

    $types_cuisine = get_terms([
        'taxonomy' => 'type_cuisine',
        'hide_empty' => false,
    ]);

    $tags_restaurant = get_terms([
        'taxonomy' => 'restaurant_tag',
        'hide_empty' => false,
    ]);

    $owners = get_users([
        'role' => 'super_restaurateur',
        'fields' => ['ID', 'display_name', 'user_email'],
    ]);

    $restaurateurs = MRDS_Gestion_Restaurateurs::get_instance()->get_restaurateurs_for_user();

    $template = plugin_dir_path(__FILE__) . 'templates/restaurant-manager-template.php';

    if (file_exists($template)) {
        include $template;
    } else {
        echo '<p>Template restaurant-manager-template.php introuvable.</p>';
    }

    return ob_get_clean();
}

    public function get_user_restaurants($user_id)
    {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return [];
        }

        $roles = (array) $user->roles;
        $restaurants = [];

        if (in_array('super_restaurateur', $roles, true)) {
            $posts = get_posts([
                'post_type' => $this->restaurant_post_type,
                'post_status' => 'any',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'restaurant_owner',
                        'value' => (int) $user_id,
                    ],
                ],
                'fields' => 'ids',
            ]);

            $restaurants = array_merge($restaurants, $posts);
        }

        if (in_array('restaurateur', $roles, true) || in_array('super_restaurateur', $roles, true)) {
            $posts2 = get_posts([
                'post_type' => $this->restaurant_post_type,
                'post_status' => 'any',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'restaurant_restaurateurs',
                        'value' => '"' . (int) $user_id . '"',
                        'compare' => 'LIKE',
                    ],
                ],
                'fields' => 'ids',
            ]);

            $restaurants = array_merge($restaurants, $posts2);
        }

        $restaurants = array_values(array_unique(array_map('intval', $restaurants)));

        return $restaurants;
    }

    public function user_can_manage_restaurant($user_id, $restaurant_id)
    {
        $allowed = $this->get_user_restaurants($user_id);
        return in_array((int) $restaurant_id, $allowed, true);
    }

    public function get_restaurant_filter_options($post_type = 'restaurant')
    {
        global $wpdb;

        $sql = $wpdb->prepare("
                SELECT DISTINCT pm.meta_key, pm.meta_value
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_value != ''
                AND p.post_type = %s
                AND p.post_status = 'publish'
                ORDER BY pm.meta_key ASC, pm.meta_value ASC
            ", $post_type);

        $results = $wpdb->get_results($sql);
        $grouped = [];

        foreach ($results as $row) {
            $grouped[$row->meta_key][] = $row->meta_value;
        }
        return $grouped;
    }
}

// Bootstrap
MRDS_Gestion_Restaurant::get_instance();