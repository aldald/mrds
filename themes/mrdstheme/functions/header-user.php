<?php

/**
 * MRDS Header User - Gestion de l'affichage utilisateur dans le header
 * 
 * Remplace le menu droit par les infos utilisateur si connecté
 * 
 * @package mrdstheme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Afficher le menu droit OU les infos utilisateur (DESKTOP)
 */
function mrdstheme_nav_right()
{

    if (is_user_logged_in()) {
        // CONNECTÉ - Afficher Prénom + Avatar ou Nom Restaurant + Image
        mrdstheme_nav_right_connecte();
    } else {
        // NON CONNECTÉ - Afficher le menu normal
        wp_nav_menu([
            'theme_location' => 'menu_right',
            'container'      => false,
            'menu_class'     => 'menu-list',
            'fallback_cb'    => false,
        ]);
    }
}

/**
 * Afficher le dropdown mobile OU les infos utilisateur (MOBILE)
 */
function mrdstheme_user_dropdown_mobile()
{

    if (is_user_logged_in()) {
        // CONNECTÉ
        mrdstheme_mobile_connecte();
    } else {
        // NON CONNECTÉ - Afficher le dropdown normal
?>
        <div class="user-dropdown">
            <button class="user-btn" aria-label="Mon compte" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M4 20c0-4 4-6 8-6s8 2 8 6" />
                </svg>
            </button>
            <div class="user-dropdown-menu">
                <?php
                wp_nav_menu([
                    'theme_location' => 'acces_member',
                    'container'      => false,
                    'menu_class'     => 'user-menu-list',
                    'fallback_cb'    => false,
                ]);
                ?>
            </div>
        </div>
    <?php
    }
}

/**
 * Menu droit - Utilisateur connecté (Desktop)
 */
function mrdstheme_nav_right_connecte()
{
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;

    // Vérifier si restaurateur
    $is_restaurateur = in_array('super_restaurateur', $user_roles) || in_array('restaurateur', $user_roles);

    if ($is_restaurateur) {
        // RESTAURATEUR
        $restaurant = mrdstheme_get_user_restaurant($current_user->ID);
        $display_name = $current_user->first_name ?: $current_user->display_name;
        $image_url = $restaurant ? get_the_post_thumbnail_url($restaurant->ID, 'thumbnail') : '';

        if (!$image_url) {
            $image_url = get_template_directory_uri() . '/assets/images/placeholder-restaurant.png';
        }
    ?>
        <ul class="menu-list menu-user-logged">
            <li class="menu-item menu-item-user">
                <a href="#" class="user-menu-link">
                    <span class="user-name"><?php echo esc_html($display_name); ?></span>
                    <span class="user-avatar user-avatar-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="">
                    </span>
                </a>
                <ul class="sub-menu user-submenu">
                    <li><a href="<?php echo get_permalink(186); ?>">Mes restaurants</a></li>
                    <li><a href="<?php echo wp_logout_url(home_url()); ?>">Déconnexion</a></li>
                </ul>
            </li>
        </ul>
    <?php
    } else {
        // MEMBRE
        $prenom = $current_user->first_name ?: $current_user->display_name;
        $nom = $current_user->last_name ?: '';
        $initiales = mrdstheme_get_initiales($prenom, $nom);
    ?>
        <ul class="menu-list menu-user-logged">
            <li class="menu-item menu-item-user">
                <a href="#" class="user-menu-link">
                    <span class="user-name"><?php echo esc_html($prenom); ?></span>
                    <span class="user-avatar user-initiales"><?php echo esc_html($initiales); ?></span>
                </a>
                <ul class="sub-menu user-submenu">
                    <li><a href="<?php echo wp_logout_url(home_url()); ?>">Déconnexion</a></li>
                </ul>
            </li>
        </ul>
    <?php
    }
}

/**
 * Menu mobile - Utilisateur connecté
 */
function mrdstheme_mobile_connecte()
{
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;

    $is_restaurateur = in_array('super_restaurateur', $user_roles) || in_array('restaurateur', $user_roles);

    if ($is_restaurateur) {
        $restaurant = mrdstheme_get_user_restaurant($current_user->ID);
        $image_url = $restaurant ? get_the_post_thumbnail_url($restaurant->ID, 'thumbnail') : '';
        if (!$image_url) {
            $image_url = get_template_directory_uri() . '/assets/images/placeholder-restaurant.png';
        }
    ?>
        <div class="user-dropdown">
            <button class="user-btn" aria-label="Mes restaurants" aria-expanded="false">
                <span class="user-avatar">
                    <img src="<?php echo esc_url($image_url); ?>" alt="">
                </span>
            </button>
            <div class="user-dropdown-menu">
                <ul class="user-menu-list">
                    <li><a href="<?php echo get_permalink(186); ?>">Mes restaurants</a></li>
                    <li><a href="<?php echo wp_logout_url(home_url()); ?>">Déconnexion</a></li>
                </ul>
            </div>
        </div>
    <?php
    } else {

        $prenom = $current_user->first_name ?: $current_user->display_name;
        $nom = $current_user->last_name ?: '';
        $initiales = mrdstheme_get_initiales($prenom, $nom);
    ?>
        <div class="user-dropdown">
            <button class="user-btn" aria-label="Mon compte" aria-expanded="false">
                <span class="user-avatar user-initiales"><?php echo esc_html($initiales); ?></span>
            </button>
            <div class="user-dropdown-menu">
                <ul class="user-menu-list">
                    <li><a href="<?php echo wp_logout_url(home_url()); ?>">Déconnexion</a></li>
                </ul>
            </div>
        </div>
<?php
    }
}

/**
 * Générer les initiales
 */
function mrdstheme_get_initiales($prenom, $nom)
{
    $initiales = '';

    if (!empty($prenom)) {
        $initiales .= mb_strtoupper(mb_substr($prenom, 0, 1));
    }
    if (!empty($nom)) {
        $initiales .= mb_strtoupper(mb_substr($nom, 0, 1));
    }
    if (strlen($initiales) < 2 && !empty($prenom)) {
        $initiales = mb_strtoupper(mb_substr($prenom, 0, 2));
    }
    if (empty($initiales)) {
        $initiales = 'U';
    }

    return $initiales;
}

/**
 * Récupérer le restaurant lié à un utilisateur
 */
function mrdstheme_get_user_restaurant($user_id)
{
    $args = [
        'post_type'      => 'restaurant',
        'posts_per_page' => 1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'     => 'restaurant_owner',
                'value'   => $user_id,
                'compare' => '='
            ]
        ]
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        return $query->posts[0];
    }

    return null;
}

/**
 * Afficher le menu gauche selon le rôle (DESKTOP + MOBILE)
 * 
 * @param string $menu_class     Classe CSS du menu (ex: 'menu-list' ou 'mobile-menu-list')
 * @param string $mobile_suffix  Suffix pour les emplacements mobile (ex: '_mobile')
 */
function mrdstheme_nav_left( $menu_class = 'menu-list', $mobile_suffix = '' ) {
    $is_restaurateur = false;

    if ( is_user_logged_in() ) {
        $user_roles      = wp_get_current_user()->roles;
        $is_restaurateur = in_array( 'super_restaurateur', $user_roles )
                        || in_array( 'restaurateur', $user_roles );
    }

    if ( $mobile_suffix ) {
        $location = $is_restaurateur ? 'menu_left_restaurateur_mobile' : 'mobile_menu';
    } else {
        $location = $is_restaurateur ? 'menu_left_restaurateur' : 'menu_left';
    }

    wp_nav_menu([
        'theme_location' => $location,
        'container'      => false,
        'menu_class'     => $menu_class,
        'fallback_cb'    => false,
    ]);
}