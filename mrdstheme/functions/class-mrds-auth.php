<?php

/**
 * MRDS Authentication
 * 
 * @package mrdstheme
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Auth
{

    // ID du produit d'abonnement
    const PRODUCT_ID = 66;

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
        // AJAX Login
        add_action('wp_ajax_mrds_login', [$this, 'ajax_login']);
        add_action('wp_ajax_nopriv_mrds_login', [$this, 'ajax_login']);

        // AJAX Register
        add_action('wp_ajax_mrds_register', [$this, 'ajax_register']);
        add_action('wp_ajax_nopriv_mrds_register', [$this, 'ajax_register']);

        // Variables JS
        add_action('wp_enqueue_scripts', [$this, 'localize_scripts'], 20);

        // Envoyer email APRÈS paiement réussi
        add_action('woocommerce_payment_complete', [$this, 'send_welcome_email_after_payment']);
        add_action('woocommerce_order_status_completed', [$this, 'send_welcome_email_after_payment']);

        //add_action('template_redirect', [$this, 'redirect_mon_compte']);
    }

    /**
     * Variables pour JavaScript
     */
    public function localize_scripts()
    {
        wp_localize_script('mrdstheme-acces-member', 'MRDS_Auth', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'checkout_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/'),
        ]);
    }

    /**
     * CONNEXION
     */

    public function ajax_login()
    {
        // Vérifier nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mrds_login_nonce')) {
            wp_send_json_error(['message' => 'Session expirée. Rafraîchissez la page.']);
        }

        $email = sanitize_text_field($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';

        // Validation simple
        if (empty($email) || empty($password)) {
            wp_send_json_error(['message' => 'Veuillez remplir tous les champs.']);
        }

        // Trouver l'utilisateur par email
        $user = get_user_by('email', $email);
        if (!$user) {
            $user = get_user_by('login', $email);
        }

        if (!$user) {
            wp_send_json_error(['message' => 'Aucun compte trouvé avec cet identifiant.']);
        }

        // Connexion
        $credentials = [
            'user_login'    => $user->user_login,
            'user_password' => $password,
            'remember'      => $remember
        ];

        $result = wp_signon($credentials, is_ssl());

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'Mot de passe incorrect.']);
        }

        // Déterminer l'URL de redirection selon le rôle
        $redirect_url = $this->get_login_redirect_url($result);

        wp_send_json_success([
            'message' => 'Connexion réussie !',
            'redirect' => $redirect_url
        ]);
    }

    public function redirect_mon_compte()
    {
        if (is_page('mon-compte')) {
            wp_redirect(home_url('/le-carnet-dadresses/'), 301);
            exit;
        }
    }

    /**
     * Déterminer l'URL de redirection après connexion selon le rôle
     * 
     * @param WP_User $user L'utilisateur connecté
     * @return string URL de redirection
     */
    private function get_login_redirect_url($user)
    {
        $user_roles = $user->roles;

        // Restaurateur ou Super Restaurateur → Gestion réservations
        if (in_array('restaurateur', $user_roles) || in_array('super_restaurateur', $user_roles)) {
            return home_url('/gestion-reservations/');
        }

        // Admin, Subscriber, Customer (abonné) → Carnet d'adresses
        if (
            in_array('administrator', $user_roles) ||
            in_array('subscriber', $user_roles) ||
            in_array('customer', $user_roles)
        ) {
            return home_url('/le-carnet-dadresses/');
        }

        // Par défaut → Page d'accueil
        return home_url('/');
    }

    /**
     * INSCRIPTION
     */
    public function ajax_register()
    {
        // Vérifier nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mrds_register_nonce')) {
            wp_send_json_error(['message' => 'Session expirée. Rafraîchissez la page.']);
        }

        // Récupérer les données
        $email = sanitize_email($_POST['email']);
        $nom = sanitize_text_field($_POST['nom']);
        $prenom = sanitize_text_field($_POST['prenom']);
        $telephone = sanitize_text_field($_POST['telephone'] ?? '');
        $adresse = sanitize_text_field($_POST['adresse'] ?? '');
        $ville = sanitize_text_field($_POST['ville'] ?? '');
        $codepostal = sanitize_text_field($_POST['codepostal'] ?? '');
        $pays = sanitize_text_field($_POST['pays'] ?? 'FR');

        // Validations
        if (empty($email) || empty($nom) || empty($prenom)) {
            wp_send_json_error(['message' => 'Veuillez remplir tous les champs obligatoires.']);
        }

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Adresse e-mail invalide.']);
        }

        if (email_exists($email)) {
            wp_send_json_error(['message' => 'Cette adresse e-mail est déjà utilisée.']);
        }

        // Générer un mot de passe automatique
        $password = wp_generate_password(12, true, false);

        // Créer le compte
        $username = sanitize_user(strstr($email, '@', true));
        $counter = 1;
        while (username_exists($username)) {
            $username = sanitize_user(strstr($email, '@', true)) . $counter;
            $counter++;
        }

        $user_id = wp_insert_user([
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'first_name'   => $prenom,
            'last_name'    => $nom,
            'display_name' => $prenom . ' ' . $nom,
            'role'         => 'customer'
        ]);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => 'Erreur lors de la création du compte.']);
        }

        // Sauvegarder le mot de passe temporairement (pour l'envoyer après paiement)
        update_user_meta($user_id, '_mrds_temp_password', $password);
        update_user_meta($user_id, '_mrds_welcome_email_sent', 'no');

        // Sauvegarder infos facturation WooCommerce
        update_user_meta($user_id, 'billing_first_name', $prenom);
        update_user_meta($user_id, 'billing_last_name', $nom);
        update_user_meta($user_id, 'billing_email', $email);
        update_user_meta($user_id, 'billing_phone', $telephone);
        update_user_meta($user_id, 'billing_address_1', $adresse);
        update_user_meta($user_id, 'billing_city', $ville);
        update_user_meta($user_id, 'billing_postcode', $codepostal);
        update_user_meta($user_id, 'billing_country', $pays);

        // Connecter l'utilisateur
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // Ajouter le produit au panier
        if (function_exists('WC')) {
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart(self::PRODUCT_ID);
        }

        // Retourner URL checkout
        wp_send_json_success([
            'message' => 'Inscription réussie !',
            'redirect' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/')
        ]);
    }

    /**
     * Envoyer email de bienvenue APRÈS paiement réussi
     */
    public function send_welcome_email_after_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $user_id = $order->get_user_id();
        if (!$user_id) return;

        $email_sent = get_user_meta($user_id, '_mrds_welcome_email_sent', true);
        if ($email_sent === 'yes') return;

        $password = get_user_meta($user_id, '_mrds_temp_password', true);
        if (empty($password)) return;

        $user = get_userdata($user_id);

        $email_manager = MRDS_Resa_Email_Manager::get_instance();
        $sent = $email_manager->send(
            $user->user_email,
            'Bienvenue chez ' . get_bloginfo('name') . ' - Vos identifiants de connexion',
            'welcome-member',
            [
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'username'   => $user->user_login,
                'password'   => $password,
                'email'      => $user->user_email,
                'login_url'  => home_url('/le-carnet-dadresses/'),
            ]
        );

        if ($sent) {
            update_user_meta($user_id, '_mrds_welcome_email_sent', 'yes');
            delete_user_meta($user_id, '_mrds_temp_password');
        }
    }


}

// Initialiser
MRDS_Auth::get_instance();
