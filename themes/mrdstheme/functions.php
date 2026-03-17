<?php
function mrdstheme_enqueue_assets()
{
    // Google Fonts - Lato
    wp_enqueue_style(
        'mrdstheme-google-fonts',
        'https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap',
        [],
        null
    );

    // Google Fonts - Work Sans
    wp_enqueue_style(
        'mrdstheme-google-fonts',
        'https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600;700&display=swap',
        [],
        null
    );

    // CSS Recherche (charge après Bootstrap)
    wp_enqueue_style(
        'mrdstheme-recherche',
        get_template_directory_uri() . '/assets/css/recherche.css',
        ['bootstrap-css'],
        filemtime(get_template_directory() . '/assets/css/recherche.css')
    );

    // CSS single-restaurant (charge après recherche)
    wp_enqueue_style(
        'mrdstheme-single-restaurant',
        get_template_directory_uri() . '/assets/css/single-restaurant.css',
        ['mrdstheme-recherche'],
        filemtime(get_template_directory() . '/assets/css/single-restaurant.css')
    );

    // CSS faq
    wp_enqueue_style(
        'mrdstheme-faq',
        get_template_directory_uri() . '/assets/css/faq.css',
        ['mrdstheme-recherche'],
        filemtime(get_template_directory() . '/assets/css/faq.css')
    );

    // CSS Accès Membre
    wp_enqueue_style(
        'mrdstheme-acces-member',
        get_template_directory_uri() . '/assets/css/acces-member.css',
        ['mrdstheme-recherche'],
        filemtime(get_template_directory() . '/assets/css/acces-member.css')
    );

    // CSS menu connected
    wp_enqueue_style(
        'mrdstheme-header-user',
        get_template_directory_uri() . '/assets/css/header-user.css',
        [],
        filemtime(get_template_directory() . '/assets/css/header-user.css')
    );


    // CSS Animations
    wp_enqueue_style(
        'mrdstheme-animations',
        get_template_directory_uri() . '/assets/css/animations.css',
        ['mrdstheme-style'],
        filemtime(get_template_directory() . '/assets/css/animations.css')
    );

    // Charger le CSS personnalisé Mon Compte
    wp_enqueue_style(
        'mrds-myaccount-custom',
        get_stylesheet_directory_uri() . '/assets/css/woocommerce-myaccount.css',
        array(),
        '1.0.0'
    );


    // CSS Principal
    wp_enqueue_style(
        'mrdstheme-style',
        get_template_directory_uri() . '/assets/css/styles.css',
        ['mrdstheme-single-restaurant'],
        filemtime(get_template_directory() . '/assets/css/styles.css')
    );
    // CSS Header Sticky
    wp_enqueue_style(
        'mrdstheme-header-sticky',
        get_template_directory_uri() . '/assets/css/header-sticky.css',
        ['mrdstheme-style'],
        filemtime(get_template_directory() . '/assets/css/header-sticky.css')
    );


    // JS - Hamburger Menu
    wp_enqueue_script(
        'mrdstheme-hamburger',
        get_template_directory_uri() . '/assets/js/hamburger.js',
        [],
        filemtime(get_template_directory() . '/assets/js/hamburger.js'),
        true
    );

    // JS - Member Dropdown
    wp_enqueue_script(
        'mrdstheme-member-dropdown',
        get_template_directory_uri() . '/assets/js/member-dropdown.js',
        [],
        filemtime(get_template_directory() . '/assets/js/member-dropdown.js'),
        true
    );

    // JS Header Sticky
    wp_enqueue_script(
        'mrdstheme-header-sticky',
        get_template_directory_uri() . '/assets/js/header-sticky.js',
        [], // Pas de dépendances
        filemtime(get_template_directory() . '/assets/js/header-sticky.js'),
        true // En footer
    );

    // JS Accès Membre
    wp_enqueue_script(
        'mrdstheme-acces-member',
        get_template_directory_uri() . '/assets/js/acces-member.js',
        [],
        filemtime(get_template_directory() . '/assets/js/acces-member.js'),
        true
    );

    // menu connected
    wp_enqueue_script(
        'mrdstheme-header-user',
        get_template_directory_uri() . '/assets/js/header-user.js',
        [],
        filemtime(get_template_directory() . '/assets/js/header-user.js'),
        true
    );

    // JS - carnet adresses
    wp_enqueue_script(
        'mrdstheme-carnet-adresses',
        get_template_directory_uri() . '/assets/js/carnet-adresses.js',
        [],
        filemtime(get_template_directory() . '/assets/js/carnet-adresses.js'),
        true
    );

    // JS - Search restaurant home
    wp_enqueue_script(
        'mrdstheme-search-Restaurant-home',
        get_template_directory_uri() . '/assets/js/search-restaurant-home.js',
        [],
        filemtime(get_template_directory() . '/assets/js/search-restaurant-home.js'),
        true
    );

    // JS Animations
    wp_enqueue_script(
        'mrdstheme-animations',
        get_template_directory_uri() . '/assets/js/animations.js',
        [],
        filemtime(get_template_directory() . '/assets/js/animations.js'),
        true
    );

    // JS - Script principal
    wp_enqueue_script(
        'mrdstheme-script',
        get_template_directory_uri() . '/assets/js/script.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/script.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'mrdstheme_enqueue_assets');


/**
 * Localize Search Script
 */
function mrdstheme_localize_search_script()
{
    wp_localize_script('mrdstheme-search-home', 'MRDS_Search_Config', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'mrdstheme_localize_search_script', 20);


/**
 * Localize Carnet Adresses Script
 */
function mrdstheme_localize_carnet_script()
{
    if (is_page_template('templates/carnet-adresses/page-carnet-adresses-visiteurs.php') || is_page('carnet-adresses')) {
        wp_localize_script('mrdstheme-carnet-adresses', 'MRDS_Search_Config', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'mrdstheme_localize_carnet_script', 20);

// Enregistrer les emplacements de menus
register_nav_menus([
    'menu_left'     => __('Menu Gauche', 'mrdstheme'),
    'menu_right'    => __('Menu Droite', 'mrdstheme'),
    'mobile_menu'   => __('Menu Mobile', 'mrdstheme'),
    'acces_member'  => __('Accès Membre', 'mrdstheme'),
    'menu_footer'   => __('Menu Footer', 'mrdstheme'),
    'menu_left_restaurateur'       => __( 'Menu Gauche – Restaurateur', 'mrdstheme' ),
    'menu_left_restaurateur_mobile'=> __( 'Menu Mobile – Restaurateur', 'mrdstheme' ),

]);

// Support menus
add_theme_support('menus');

// Support site logo
add_theme_support('custom-logo');

// Support posts thumbnails
add_theme_support('post-thumbnails');

// Support widgets
add_theme_support('widgets');

/* ================================
   METABOX CHOIX DU HEADER
================================ */

// Ajouter la metabox
function mrdstheme_add_header_metabox()
{
    add_meta_box(
        'mrdstheme_header_choice',
        'Choix du Header',
        'mrdstheme_header_metabox_callback',
        ['page', 'post'], // Disponible sur pages et articles
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'mrdstheme_add_header_metabox');

// Afficher le contenu de la metabox
function mrdstheme_header_metabox_callback($post)
{
    // Sécurité
    wp_nonce_field('mrdstheme_header_nonce_action', 'mrdstheme_header_nonce');

    // Récupérer la valeur actuelle
    $header_choice = get_post_meta($post->ID, '_mrdstheme_header_choice', true);

    // Si pas de valeur, défaut = transparent
    if (empty($header_choice)) {
        $header_choice = 'transparent';
    }
?>
    <p>
        <label>
            <input type="radio" name="mrdstheme_header_choice" value="transparent" <?php checked($header_choice, 'transparent'); ?>>
            Header Transparent (avec lignes et arc)
        </label>
    </p>
    <p>
        <label>
            <input type="radio" name="mrdstheme_header_choice" value="blue" <?php checked($header_choice, 'blue'); ?>>
            Header Bleu #141B42 (compact)
        </label>
    </p>
<?php
}

// Sauvegarder le choix
function mrdstheme_save_header_metabox($post_id)
{
    // Vérifier le nonce
    if (!isset($_POST['mrdstheme_header_nonce']) || !wp_verify_nonce($_POST['mrdstheme_header_nonce'], 'mrdstheme_header_nonce_action')) {
        return;
    }

    // Vérifier les permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Vérifier l'autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Sauvegarder
    if (isset($_POST['mrdstheme_header_choice'])) {
        update_post_meta($post_id, '_mrdstheme_header_choice', sanitize_text_field($_POST['mrdstheme_header_choice']));
    }
}
add_action('save_post', 'mrdstheme_save_header_metabox');

/* ================================
   FONCTION POUR CHARGER LE BON HEADER
================================ */

function mrdstheme_get_header()
{
    // Récupérer le choix du header
    $header_choice = get_post_meta(get_the_ID(), '_mrdstheme_header_choice', true);

    // Si pas de choix, défaut = transparent pour la home, bleu pour le reste
    if (empty($header_choice)) {
        $header_choice = is_front_page() ? 'transparent' : 'blue';
    }

    // Charger le bon header depuis templates/headers/
    get_template_part('templates/headers/header', $header_choice);
}

/**
 * ACF JSON - Sauvegarde et chargement local des configurations
 */

// Chemin de sauvegarde des fichiers JSON ACF
function mrdstheme_acf_json_save_point($path)
{
    return get_stylesheet_directory() . '/acf-json';
}
add_filter('acf/settings/save_json', 'mrdstheme_acf_json_save_point');

// Chemin de chargement des fichiers JSON ACF
function mrdstheme_acf_json_load_point($paths)
{
    unset($paths[0]);
    $paths[] = get_stylesheet_directory() . '/acf-json';
    return $paths;
}
add_filter('acf/settings/load_json', 'mrdstheme_acf_json_load_point');



// Force le template pour la page reserver
add_filter('template_include', function ($template) {
    if (is_page('reserver')) {
        $custom_template = get_stylesheet_directory() . '/page-reserver.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}, 99);


/**
 * ========================================
 * MENU WOOCOMMERCE - PERSONNALISÉ PAR RÔLE
 * ========================================
 */

/**
 * Personnaliser le menu "Mon compte" WooCommerce
 */
add_filter('woocommerce_account_menu_items', 'custom_account_menu_items');
function custom_account_menu_items($items)
{
    // Supprimer les éléments non utilisés
    unset($items['dashboard']);
    unset($items['orders']);
    unset($items['downloads']);
    unset($items['edit-address']);

    // Récupérer l'utilisateur
    $user = wp_get_current_user();

    // Créer un nouveau menu selon le rôle
    if (in_array('restaurateur', (array) $user->roles) || in_array('super_restaurateur', (array) $user->roles)) {
        $new_items = [
            'gestion-reservations' => 'Gestion des réservations',
        ];
    } else {
        $new_items = [
            'mes-reservations' => 'Mes réservations',
        ];
    }

    // Ajouter les nouveaux items au début + garder le reste
    return array_merge($new_items, $items);
}

/**
 * Définir l'URL pour les nouveaux endpoints personnalisés
 */
add_filter('woocommerce_get_endpoint_url', 'custom_account_endpoint_url', 10, 4);
function custom_account_endpoint_url($url, $endpoint, $value, $permalink)
{
    if ($endpoint === 'mes-reservations') {
        return home_url('/mes-reservations/');
    }

    if ($endpoint === 'gestion-reservations') {
        return home_url('/gestion-reservations/');
    }

    return $url;
}



add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'mrds_reservation') return;
?>
    <style>
        /* ================================
           COLONNES - LARGEURS
        ================================ */
        .column-title {
            width: 260px !important;
        }

        .column-restaurant {
            width: 140px !important;
        }

        .column-member {
            width: 180px !important;
        }

        .column-date_time {
            width: 160px !important;
        }

        .column-guests {
            width: 80px !important;
            text-align: center !important;
        }

        .column-status {
            width: 120px !important;
        }

        .column-date {
            width: 130px !important;
        }

        /* ================================
           LIGNES - ESPACEMENT
        ================================ */
        #the-list tr {
            height: 64px;
        }

        #the-list td,
        #the-list th {
            vertical-align: middle !important;
            padding: 10px 12px !important;
        }

        /* ================================
           COLONNE TITRE
        ================================ */
        .column-title a {
            font-weight: 700;
            font-size: 13px;
            line-height: 1.5;
            color: #1d2327;
        }

        .column-title .row-actions {
            font-size: 11px;
        }

        /* ================================
           COLONNE RESTAURANT
        ================================ */
        .column-restaurant a {
            font-weight: 600;
            color: #2271b1;
        }

        /* ================================
           COLONNE MEMBRE
        ================================ */
        .column-member a {
            font-weight: 600;
            display: block;
            line-height: 1.4;
        }

        .column-member small {
            color: #646970;
            font-size: 11px;
        }

        /* ================================
           COLONNE DATE & HEURE
        ================================ */
        .column-date_time strong {
            display: block;
            font-size: 13px;
            color: #1d2327;
        }

        .column-date_time br+* {
            color: #646970;
            font-size: 12px;
        }

        /* ================================
           COLONNE COUVERTS
        ================================ */
        .column-guests {
            font-size: 14px;
            font-weight: 700;
            color: #1d2327;
            text-align: center !important;
        }

        /* ================================
           BADGES STATUT
        ================================ */
        .mrds-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .mrds-status-pending {
            background: #fef3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }

        .mrds-status-confirmed {
            background: #d1f5e0;
            color: #166534;
            border: 1px solid #22c55e;
        }

        .mrds-status-refused {
            background: #fde8e8;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .mrds-status-cancelled {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #9ca3af;
        }

        .mrds-status-completed {
            background: #dbeafe;
            color: #1e3a8a;
            border: 1px solid #3b82f6;
        }

        .mrds-status-no-show {
            background: #fdf4ff;
            color: #6b21a8;
            border: 1px solid #a855f7;
        }

        /* ================================
           ZEBRA STRIPES
        ================================ */
        #the-list tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        #the-list tr:hover td {
            background-color: #f0f6fc !important;
        }
    </style>
<?php
});



/**
 * Supprimer les onglets inutiles du menu Mon Compte WooCommerce
 */
add_filter('woocommerce_account_menu_items', function ($items) {
    unset($items['payment-methods']); // Moyens de paiement
    unset($items['subscriptions']);   // Abonnements (WooCommerce Subscriptions)
    return $items;
});


add_filter('the_title', function ($title, $id = null) {
    $pages_sans_titre = [
        'gestion-restaurant',
        'mrds-gestion-restaurateurs',
        'statistiques',
        'gestion-reservations',
    ];

    if (is_page($pages_sans_titre) && in_the_loop()) {
        return '';
    }
    return $title;
}, 10, 2);



require_once get_template_directory() . '/functions/shortcodes-buttons.php';
require_once get_template_directory() . '/functions/bootstrap.php';
require_once get_template_directory() . '/functions/breadcrumb.php';
require_once get_template_directory() . '/functions/snippets.php';
require_once get_template_directory() . '/functions/map-component.php';
require_once get_template_directory() . '/functions/class-mrds-auth.php';
require_once get_template_directory() . '/functions/header-user.php';
require_once get_template_directory() . '/functions/ajax-search-restaurants.php';
require_once get_template_directory() . '/functions/helpers.php';
