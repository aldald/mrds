<?php

/**
 * Plugin Name: MRDS - Réservation
 * Plugin URI: https://mesrondsdeserviette.com
 * Description: Permet aux membres de réserver dans les restaurants partenaires (1 réservation par restaurant par an).
 * Version: 1.0.0
 * Author: Coccinet
 * Author URI: https://coccinet.com
 * Text Domain: mrds-reservation
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('MRDS_RESA_VERSION', '1.0.0');
define('MRDS_RESA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MRDS_RESA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MRDS_RESA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale du plugin
 */
final class MRDS_Reservation
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
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies()
    {
        // Charger les fichiers
        require_once MRDS_RESA_PLUGIN_DIR . 'includes/class-mrds-resa-post-type.php';
        require_once MRDS_RESA_PLUGIN_DIR . 'includes/class-mrds-resa-reservation.php';
        require_once MRDS_RESA_PLUGIN_DIR . 'includes/class-mrds-resa-ajax.php';
        require_once MRDS_RESA_PLUGIN_DIR . 'includes/class-mrds-resa-shortcodes.php';
        require_once MRDS_RESA_PLUGIN_DIR . 'includes/class-mrds-resa-template-tags.php';
        require_once MRDS_RESA_PLUGIN_DIR . 'includes/class-mrds-resa-restaurant-manager.php';
        require_once MRDS_RESA_PLUGIN_DIR . 'includes/class-mrds-resa-email-manager.php';


        // Instancier les classes
        MRDS_Resa_Post_Type::get_instance();
        MRDS_Resa_Reservation::get_instance();
        MRDS_Resa_Ajax::get_instance();
        MRDS_Resa_Shortcodes::get_instance();
        MRDS_Resa_Email_Manager::get_instance();
    }

    private function init_hooks()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }

    public function init()
    {
        load_plugin_textdomain('mrds-reservation', false, dirname(MRDS_RESA_PLUGIN_BASENAME) . '/languages');

        MRDS_Resa_Post_Type::get_instance();
        MRDS_Resa_Reservation::get_instance();
        MRDS_Resa_Ajax::get_instance();
        MRDS_Resa_Shortcodes::get_instance();
        MRDS_Resa_Email_Manager::get_instance();


        $this->maybe_create_reservation_page();
    }

    public function enqueue_scripts()
    {


        // Flatpickr CSS
        wp_enqueue_style(
            'flatpickr-css',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            [],
            '4.6.13'
        );

        wp_enqueue_style(
            'mrds-resa-form',
            MRDS_RESA_PLUGIN_URL . 'assets/css/resa-form-styles.css',
            [],
            MRDS_RESA_VERSION
        );

        // Plugin CSS
        wp_enqueue_style(
            'mrds-resa-widget',
            MRDS_RESA_PLUGIN_URL . 'assets/css/widget-reservation.css',
            [],
            MRDS_RESA_VERSION
        );



        // Flatpickr JS
        wp_enqueue_script(
            'flatpickr-js',
            'https://cdn.jsdelivr.net/npm/flatpickr',
            [],
            '4.6.13',
            true
        );

        // Flatpickr FR
        wp_enqueue_script(
            'flatpickr-fr',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js',
            ['flatpickr-js'],
            '4.6.13',
            true
        );

        // Widget inline (single restaurant)
        wp_enqueue_script(
            'mrds-resa-widget-inline',
            MRDS_RESA_PLUGIN_URL . 'assets/js/widget-reservation-inline.js',
            ['flatpickr-js'],
            MRDS_RESA_VERSION,
            true
        );

        // Widget JS
        wp_enqueue_script(
            'mrds-resa-widget',
            MRDS_RESA_PLUGIN_URL . 'assets/js/widget-reservation.js',
            ['flatpickr-js'],
            MRDS_RESA_VERSION,
            true
        );

        // Form JS
        wp_enqueue_script(
            'mrds-resa-form',
            MRDS_RESA_PLUGIN_URL . 'assets/js/form-reservation.js',
            ['flatpickr-js'],
            MRDS_RESA_VERSION,
            true
        );

        // Config JS
        wp_localize_script('mrds-resa-widget-inline', 'MRDS_Resa_Config', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mrds_resa_nonce'),
            'reservation_page' => home_url('/reserver/'),
            'i18n' => [
                'select_date' => 'Sélectionnez une date',
                'select_time' => 'Sélectionnez une heure',
                'loading' => 'Chargement...',
                'closed' => 'Fermé',
                'error' => 'Une erreur est survenue',
            ]
        ]);
    }

    public function admin_enqueue_scripts($hook)
    {
        global $post_type;
        if ($post_type === 'mrds_reservation') {
            wp_enqueue_style('mrds-resa-admin-css', MRDS_RESA_PLUGIN_URL . 'assets/css/admin-reservation.css', [], MRDS_RESA_VERSION);
        }
    }

    public function is_current_user_member()
    {
        if (!is_user_logged_in()) return false;
        $user = wp_get_current_user();
        $allowed_roles = ['customer', 'subscriber', 'administrator'];
        return !empty(array_intersect($allowed_roles, $user->roles));
    }

    public function get_reservation_page_url()
    {
        $page = get_page_by_path('reserver');
        return $page ? get_permalink($page->ID) : home_url('/reserver/');
    }

    public function maybe_create_reservation_page()
    {
        if (!get_page_by_path('reserver')) {
            wp_insert_post([
                'post_title' => 'Réserver',
                'post_name' => 'reserver',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '[mrds_reservation_form]',
            ]);
        }
    }

    public function activate()
    {
        require_once MRDS_RESA_PLUGIN_DIR . 'includes/class-mrds-resa-post-type.php';
        MRDS_Resa_Post_Type::get_instance()->register_post_type();
        $this->maybe_create_reservation_page();
        flush_rewrite_rules();
        update_option('mrds_resa_version', MRDS_RESA_VERSION);
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }
}

// Lancer le plugin
add_action('plugins_loaded', function () {
    MRDS_Reservation::get_instance();
});
