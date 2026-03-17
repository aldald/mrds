<?php
/**
 * Plugin Name: MRDS - Gestion Restaurateurs
 * Description: Permet aux super_restaurateurs de créer et gérer leurs propres restaurateurs + statistiques.
 * Author: Coccinet
 * Version: 1.1.0
 * Text Domain: mrds-gestion-restaurateurs
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('MRDS_RESTAURATEURS_VERSION', '1.1.0');
define('MRDS_RESTAURATEURS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MRDS_RESTAURATEURS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Chargement des classes
 */
function mrds_restaurateurs_load() {
    require_once MRDS_RESTAURATEURS_PLUGIN_DIR . 'includes/class-mrds-restaurateur.php';
    require_once MRDS_RESTAURATEURS_PLUGIN_DIR . 'includes/class-mrds-restaurant-stats.php';
    
    MRDS_Gestion_Restaurateurs::get_instance();
    MRDS_Restaurant_Stats::get_instance();
}
add_action('plugins_loaded', 'mrds_restaurateurs_load');

/**
 * Activation du plugin
 */
function mrds_restaurateurs_activate() {
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'mrds_restaurateurs_activate');

/**
 * Désactivation du plugin
 */
function mrds_restaurateurs_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'mrds_restaurateurs_deactivate');
