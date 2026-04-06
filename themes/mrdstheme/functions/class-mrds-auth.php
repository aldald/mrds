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

        // Vérifier si l'email a déjà été envoyé
        $email_sent = get_user_meta($user_id, '_mrds_welcome_email_sent', true);
        if ($email_sent === 'yes') return;

        // Récupérer le mot de passe temporaire
        $password = get_user_meta($user_id, '_mrds_temp_password', true);
        if (empty($password)) return;

        // Récupérer les infos utilisateur
        $user = get_userdata($user_id);
        $email = $user->user_email;
        $prenom = $user->first_name;
        $nom = $user->last_name;
        $username = $user->user_login;

        // Envoyer l'email
        $sent = $this->send_welcome_email($email, $prenom, $nom, $username, $password);

        if ($sent) {
            // Marquer comme envoyé et supprimer le mot de passe temporaire
            update_user_meta($user_id, '_mrds_welcome_email_sent', 'yes');
            delete_user_meta($user_id, '_mrds_temp_password');
        }
    }

    /**
     * Envoyer email de bienvenue avec mot de passe
     */
    private function send_welcome_email($email, $prenom, $nom, $username, $password)
    {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $login_url = home_url('/le-carnet-dadresses/');
        $header_image  = get_option('mrds_email_header_image', '');
        $contact_email = get_option('mrds_email_support_email', get_option('admin_email'));
        $country       = get_option('mrds_email_footer_country', 'France');
        $brand_color   = get_option('mrds_email_brand_color', '#DA9D42');


        $subject = "Bienvenue chez {$site_name} - Vos identifiants de connexion";

        $message = "
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='margin: 0; padding: 0; background-color: #f7f7f7; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif;'>
    <table cellpadding='0' cellspacing='0' border='0' width='100%' style='background-color: #f7f7f7;'>
        <tr>
            <td align='center' style='padding: 40px 20px;'>
                <table cellpadding='0' cellspacing='0' border='0' width='600' style='max-width: 600px; background-color: #ffffff; border-radius: 4px; overflow: hidden;'>
                    
<!-- Header Logo - Image pleine largeur -->
<tr>
    <td align='center' style='padding: 0; margin: 0; line-height: 0;'>
<img src='{$header_image}' alt='Mes Ronds de Serviette' width='600' style='width: 100%; height: auto; display: block;'>

    </td>
</tr>
                    
                    <!-- Contenu -->
                    <tr>
                        <td style='padding: 40px; font-family: \"Helvetica Neue\", Helvetica, Roboto, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #636363;'>
                            <p>Bonjour <strong>{$prenom} {$nom}</strong>,</p>
                            
                            <p>Bienvenue chez Mes ronds de serviette ! Votre compte a été créé avec succès.</p>
                            
                            <table cellpadding='0' cellspacing='0' border='0' width='100%' style='margin: 25px 0;'>
                                <tr>
                                    <td style='background-color: #f8f8f8; border-left: 4px solid {$brand_color}; padding: 20px;'>
                                        <p style='margin: 0 0 10px 0; font-size: 14px;'><strong>Vos identifiants de connexion :</strong></p>
                                        <p style='margin: 5px 0; font-size: 14px;'>Email : <strong>{$email}</strong></p>
                                        <p style='margin: 5px 0; font-size: 14px;'>Nom d'utilisateur : <strong>{$username}</strong></p>
                                        <p style='margin: 5px 0; font-size: 14px;'>Mot de passe : <strong>{$password}</strong></p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p><em>Nous vous conseillons de changer votre mot de passe après votre première connexion.</em></p>
                            
                            <table cellpadding='0' cellspacing='0' border='0' style='margin: 30px 0;'>
                                <tr>
<td align='center' bgcolor='{$brand_color}'>
                                        <a href='{$login_url}' style='display: inline-block; padding: 15px 30px; font-size: 14px; color: #ffffff; text-decoration: none; font-weight: bold;'>Se connecter</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p>À très bientôt dans nos restaurants partenaires !</p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style='background-color: #3c3c3c; padding: 30px 40px; text-align: center;'>
                            <p style='margin: 0 0 15px 0; font-size: 13px; color: #b8b8b8;'>
                                Contactez-nous à l'adresse <a href='mailto:{$contact_email}' style='color: {$brand_color}; text-decoration: none;'>{$contact_email}</a>, si vous avez besoin d'aide avec votre adhésion.
                            </p>
                            <p style='margin: 0; font-size: 13px; color: #b8b8b8;'>
<strong style='color: #ffffff;'>{$site_name}</strong><br>{$country}
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
";

        // Headers pour email HTML
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <no-reply@' . parse_url($site_url, PHP_URL_HOST) . '>'
        ];

        // Envoyer l'email
        $sent = wp_mail($email, $subject, $message, $headers);

        // Log si erreur (optionnel)
        if (!$sent) {
            error_log('MRDS Auth: Échec envoi email à ' . $email);
        }

        return $sent;
    }
}

// Initialiser
MRDS_Auth::get_instance();
