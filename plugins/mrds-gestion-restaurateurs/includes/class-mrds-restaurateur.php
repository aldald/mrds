<?php
/**
 * MRDS Gestion Restaurateurs - Classe principale
 *
 * Gère la création et la gestion des restaurateurs par les super_restaurateurs
 *
 * @package mrds-gestion-restaurateurs
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Gestion_Restaurateurs
{
    /**
     * Instance unique (Singleton)
     */
    protected static $instance = null;

    /**
     * Meta keys
     */
    const META_CREATED_BY = '_mrds_created_by';
    const META_CREATED_AT = '_mrds_created_at';
    const META_PHONE = '_mrds_phone';

    /**
     * Récupère l'instance unique
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur privé
     */
    private function __construct()
    {
        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Shortcode
        add_shortcode('mrds_restaurateur_manager', [$this, 'render_restaurateur_manager']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * ========================================
     * ENQUEUE ASSETS
     * ========================================
     */
    public function enqueue_assets()
    {
        // Seulement si connecté
        if (!is_user_logged_in()) {
            return;
        }

        // Vérifier les permissions
        $user = wp_get_current_user();
        if (!in_array('administrator', (array) $user->roles, true) &&
            !in_array('super_restaurateur', (array) $user->roles, true)) {
            return;
        }

        // JavaScript
        wp_register_script(
            'mrds-restaurateur-manager',
            MRDS_RESTAURATEURS_PLUGIN_URL . 'assets/js/restaurateur-manager.js',
            [],
            MRDS_RESTAURATEURS_VERSION,
            true
        );

        // Passer les données au JS
        wp_localize_script(
            'mrds-restaurateur-manager',
            'MRDSRestaurateurConfig',
            [
                'restUrl' => esc_url_raw(rest_url('mrds/v1/restaurateurs')),
                'nonce' => wp_create_nonce('wp_rest'),
                'isAdmin' => current_user_can('administrator'),
            ]
        );

        wp_enqueue_script('mrds-restaurateur-manager');

        // CSS
        wp_enqueue_style(
            'mrds-restaurateur-manager',
            MRDS_RESTAURATEURS_PLUGIN_URL . 'assets/css/restaurateur-manager.css',
            [],
            MRDS_RESTAURATEURS_VERSION
        );
    }

    /**
     * ========================================
     * REST API ROUTES
     * ========================================
     */
    public function register_rest_routes()
    {
        // Liste + Création
        register_rest_route(
            'mrds/v1',
            '/restaurateurs',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'rest_list_restaurateurs'],
                    'permission_callback' => [$this, 'permission_manage_restaurateurs'],
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'rest_create_restaurateur'],
                    'permission_callback' => [$this, 'permission_manage_restaurateurs'],
                ],
            ]
        );

        // Modification d'un restaurateur
        register_rest_route(
            'mrds/v1',
            '/restaurateurs/(?P<id>\d+)',
            [
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'rest_update_restaurateur'],
                    'permission_callback' => [$this, 'permission_edit_restaurateur'],
                ],
            ]
        );
    }

    /**
     * Permission : peut gérer les restaurateurs
     */
    public function permission_manage_restaurateurs()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        return in_array('administrator', (array) $user->roles, true) ||
               in_array('super_restaurateur', (array) $user->roles, true);
    }

    /**
     * Permission : peut modifier ce restaurateur
     */
    public function permission_edit_restaurateur(WP_REST_Request $request)
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $restaurateur_id = (int) $request['id'];
        $current_user_id = get_current_user_id();
        $current_user = wp_get_current_user();

        // Admin peut tout modifier
        if (in_array('administrator', (array) $current_user->roles, true)) {
            return true;
        }

        // Super_restaurateur peut modifier seulement ses restaurateurs
        if (in_array('super_restaurateur', (array) $current_user->roles, true)) {
            $created_by = get_user_meta($restaurateur_id, self::META_CREATED_BY, true);
            return (int) $created_by === $current_user_id;
        }

        return false;
    }

    /**
     * ========================================
     * REST : LISTE DES RESTAURATEURS
     * ========================================
     */
    public function rest_list_restaurateurs(WP_REST_Request $request)
    {
        $current_user_id = get_current_user_id();
        $restaurateurs = $this->get_restaurateurs_for_user($current_user_id);

        $data = [];

        foreach ($restaurateurs as $user) {
            $data[] = $this->format_restaurateur_data($user);
        }

        return rest_ensure_response($data);
    }

    /**
     * ========================================
     * REST : CRÉER UN RESTAURATEUR
     * ========================================
     */
    public function rest_create_restaurateur(WP_REST_Request $request)
    {
        $params = $request->get_json_params();

        // Validation des champs requis
        $first_name = isset($params['first_name']) ? sanitize_text_field($params['first_name']) : '';
        $last_name = isset($params['last_name']) ? sanitize_text_field($params['last_name']) : '';
        $email = isset($params['email']) ? sanitize_email($params['email']) : '';
        $phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';

        if (empty($first_name)) {
            return new WP_Error('missing_first_name', 'Le prénom est obligatoire.', ['status' => 400]);
        }

        if (empty($last_name)) {
            return new WP_Error('missing_last_name', 'Le nom est obligatoire.', ['status' => 400]);
        }

        if (empty($email)) {
            return new WP_Error('missing_email', 'L\'email est obligatoire.', ['status' => 400]);
        }

        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'L\'adresse email n\'est pas valide.', ['status' => 400]);
        }

        if (email_exists($email)) {
            return new WP_Error('email_exists', 'Cette adresse email est déjà utilisée.', ['status' => 400]);
        }

        // Générer un username unique
        $username = $this->generate_unique_username($first_name, $last_name);

        // Générer un mot de passe automatique
        $password = wp_generate_password(12, true, false);

        // Créer l'utilisateur
        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => 'restaurateur',
        ]);

        if (is_wp_error($user_id)) {
            return new WP_Error('user_creation_failed', 'Erreur lors de la création du compte.', ['status' => 500]);
        }

        // Sauvegarder les meta
        $current_user_id = get_current_user_id();
        update_user_meta($user_id, self::META_CREATED_BY, $current_user_id);
        update_user_meta($user_id, self::META_CREATED_AT, current_time('mysql'));
        
        if (!empty($phone)) {
            update_user_meta($user_id, self::META_PHONE, $phone);
        }

        // Envoyer l'email avec les identifiants
        $email_sent = $this->send_welcome_email($user_id, $email, $first_name, $last_name, $username, $password);

        // Récupérer l'utilisateur créé
        $user = get_user_by('id', $user_id);

        return rest_ensure_response([
            'success' => true,
            'message' => $email_sent ? 'Restaurateur créé. Un email avec les identifiants a été envoyé.' : 'Restaurateur créé. L\'email n\'a pas pu être envoyé.',
            'restaurateur' => $this->format_restaurateur_data($user),
        ]);
    }

    /**
     * ========================================
     * REST : MODIFIER UN RESTAURATEUR
     * ========================================
     */
    public function rest_update_restaurateur(WP_REST_Request $request)
    {
        $restaurateur_id = (int) $request['id'];
        $params = $request->get_json_params();

        // Vérifier que l'utilisateur existe et est un restaurateur
        $user = get_user_by('id', $restaurateur_id);
        
        if (!$user) {
            return new WP_Error('user_not_found', 'Restaurateur introuvable.', ['status' => 404]);
        }

        if (!in_array('restaurateur', (array) $user->roles, true)) {
            return new WP_Error('not_restaurateur', 'Cet utilisateur n\'est pas un restaurateur.', ['status' => 400]);
        }

        // Préparer les données à mettre à jour
        $update_data = ['ID' => $restaurateur_id];

        if (isset($params['first_name']) && !empty($params['first_name'])) {
            $update_data['first_name'] = sanitize_text_field($params['first_name']);
        }

        if (isset($params['last_name']) && !empty($params['last_name'])) {
            $update_data['last_name'] = sanitize_text_field($params['last_name']);
        }

        // Mettre à jour le display_name si prénom ou nom changé
        if (isset($update_data['first_name']) || isset($update_data['last_name'])) {
            $new_first = isset($update_data['first_name']) ? $update_data['first_name'] : $user->first_name;
            $new_last = isset($update_data['last_name']) ? $update_data['last_name'] : $user->last_name;
            $update_data['display_name'] = $new_first . ' ' . $new_last;
        }

        // Email (vérifier qu'il n'est pas déjà utilisé par un autre)
        if (isset($params['email']) && !empty($params['email'])) {
            $new_email = sanitize_email($params['email']);
            
            if (!is_email($new_email)) {
                return new WP_Error('invalid_email', 'L\'adresse email n\'est pas valide.', ['status' => 400]);
            }

            $existing_user = get_user_by('email', $new_email);
            if ($existing_user && $existing_user->ID !== $restaurateur_id) {
                return new WP_Error('email_exists', 'Cette adresse email est déjà utilisée.', ['status' => 400]);
            }

            $update_data['user_email'] = $new_email;
        }

        // Mettre à jour l'utilisateur
        $result = wp_update_user($update_data);

        if (is_wp_error($result)) {
            return new WP_Error('update_failed', 'Erreur lors de la mise à jour.', ['status' => 500]);
        }

        // Mettre à jour le téléphone
        if (isset($params['phone'])) {
            update_user_meta($restaurateur_id, self::META_PHONE, sanitize_text_field($params['phone']));
        }

        // Récupérer l'utilisateur mis à jour
        $updated_user = get_user_by('id', $restaurateur_id);

        return rest_ensure_response([
            'success' => true,
            'message' => 'Restaurateur mis à jour.',
            'restaurateur' => $this->format_restaurateur_data($updated_user),
        ]);
    }

    /**
     * ========================================
     * HELPER : RÉCUPÉRER LES RESTAURATEURS POUR UN UTILISATEUR
     * ========================================
     * 
     * Fonction publique utilisable par d'autres plugins
     * 
     * @param int|null $user_id - ID de l'utilisateur (null = current user)
     * @return array - Liste des objets WP_User
     */
    public static function get_restaurateurs_for_user($user_id = null)
    {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        $user = get_user_by('id', $user_id);

        if (!$user) {
            return [];
        }

        // Admin → tous les restaurateurs
        if (in_array('administrator', (array) $user->roles, true)) {
            return get_users([
                'role' => 'restaurateur',
                'orderby' => 'display_name',
                'order' => 'ASC',
            ]);
        }

        // Super_restaurateur → seulement les siens
        if (in_array('super_restaurateur', (array) $user->roles, true)) {
            return get_users([
                'role' => 'restaurateur',
                'meta_key' => self::META_CREATED_BY,
                'meta_value' => $user_id,
                'orderby' => 'display_name',
                'order' => 'ASC',
            ]);
        }

        return [];
    }

    /**
     * ========================================
     * SHORTCODE : AFFICHER LE GESTIONNAIRE
     * ========================================
     */
    public function render_restaurateur_manager()
    {
        // Vérifier la connexion
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning">Vous devez être connecté pour accéder à cette page.</div>';
        }

        // Vérifier les permissions
        $user = wp_get_current_user();
        if (!in_array('administrator', (array) $user->roles, true) &&
            !in_array('super_restaurateur', (array) $user->roles, true)) {
            return '<div class="alert alert-danger">Accès refusé. Vous n\'avez pas les permissions nécessaires.</div>';
        }

        // Charger le template
        ob_start();
        
        $template_path = MRDS_RESTAURATEURS_PLUGIN_DIR . 'templates/restaurateur-manager-template.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="alert alert-danger">Template introuvable.</div>';
        }

        return ob_get_clean();
    }

    /**
     * ========================================
     * HELPER : FORMATER LES DONNÉES D'UN RESTAURATEUR
     * ========================================
     */
    private function format_restaurateur_data($user)
    {
        if (!$user instanceof WP_User) {
            return null;
        }

        return [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'phone' => get_user_meta($user->ID, self::META_PHONE, true) ?: '',
            'created_by' => get_user_meta($user->ID, self::META_CREATED_BY, true) ?: '',
            'created_at' => get_user_meta($user->ID, self::META_CREATED_AT, true) ?: '',
        ];
    }

    /**
     * ========================================
     * HELPER : GÉNÉRER UN USERNAME UNIQUE
     * ========================================
     */
    private function generate_unique_username($first_name, $last_name)
    {
        // Base : prenom.nom en minuscules sans accents
        $base = sanitize_user(
            strtolower(
                $this->remove_accents($first_name) . '.' . $this->remove_accents($last_name)
            ),
            true
        );

        // Si le username existe déjà, ajouter un numéro
        $username = $base;
        $counter = 1;

        while (username_exists($username)) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * ========================================
     * HELPER : SUPPRIMER LES ACCENTS
     * ========================================
     */
    private function remove_accents($string)
    {
        return remove_accents($string);
    }

    /**
     * ========================================
     * EMAIL : ENVOYER LES IDENTIFIANTS
     * ========================================
     */
    private function send_welcome_email($user_id, $email, $first_name, $last_name, $username, $password)
    {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $login_url = home_url('/acces-membre/');

        $subject = sprintf('[%s] Vos identifiants de connexion', $site_name);

        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background: linear-gradient(135deg, #141B42 0%, #1a2255 100%);
                    color: #FFFFFF;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .content {
                    padding: 30px;
                    background: #F8F6F3;
                }
                .credentials {
                    background: #FFFFFF;
                    padding: 20px;
                    margin: 20px 0;
                    border-left: 4px solid #DA9D42;
                }
                .credentials p {
                    margin: 8px 0;
                }
                .credentials strong {
                    color: #141B42;
                }
                .button {
                    display: inline-block;
                    background: linear-gradient(135deg, #DA9D42 0%, #C4882E 100%);
                    color: #FFFFFF !important;
                    padding: 14px 30px;
                    text-decoration: none;
                    font-weight: 600;
                    margin-top: 20px;
                }
                .button:hover {
                    background: linear-gradient(135deg, #C4882E 0%, #B07A24 100%);
                }
                .footer {
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #888;
                }
                .warning {
                    background: #FFF8E1;
                    border-left: 4px solid #DA9D42;
                    padding: 15px;
                    margin: 20px 0;
                    font-size: 13px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . esc_html($site_name) . '</h1>
                </div>
                <div class="content">
                    <p>Bonjour <strong>' . esc_html($first_name) . ' ' . esc_html($last_name) . '</strong>,</p>
                    
                    <p>Votre compte restaurateur a été créé sur <strong>' . esc_html($site_name) . '</strong>.</p>
                    
                    <p>Voici vos identifiants de connexion :</p>
                    
                    <div class="credentials">
                        <p><strong>Identifiant :</strong> ' . esc_html($username) . '</p>
                        <p><strong>Email :</strong> ' . esc_html($email) . '</p>
                        <p><strong>Mot de passe :</strong> ' . esc_html($password) . '</p>
                    </div>
                    
                    <div class="warning">
                        <strong>Important :</strong> Nous vous recommandons de changer votre mot de passe après votre première connexion.
                    </div>
                    
                    <p style="text-align: center;">
                        <a href="' . esc_url($login_url) . '" class="button">Se connecter</a>
                    </p>
                </div>
                <div class="footer">
                    <p>' . esc_html($site_name) . ' - ' . esc_url($site_url) . '</p>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>
        ';

        // Headers pour email HTML
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <no-reply@' . parse_url($site_url, PHP_URL_HOST) . '>',
        ];

        // Envoyer l'email
        $sent = wp_mail($email, $subject, $message, $headers);

        // Log en cas d'erreur
        if (!$sent) {
            error_log('MRDS Restaurateurs: Échec envoi email à ' . $email);
        }

        return $sent;
    }
}


// Bootstrap
MRDS_Gestion_Restaurateurs::get_instance();
