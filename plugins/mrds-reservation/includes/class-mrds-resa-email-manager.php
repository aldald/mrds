<?php
if (!defined('ABSPATH')) exit;

class MRDS_Resa_Email_Manager
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
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);

        // Forcer l'expéditeur à noreply pour éviter le Gravatar sur mobile
        add_filter('wp_mail_from', [$this, 'set_mail_from']);
        add_filter('wp_mail_from_name', [$this, 'set_mail_from_name']);
    }

    /**
     * Forcer l'adresse expéditeur en noreply (pas de Gravatar)
     */
    public function set_mail_from($email)
    {
        $domain = parse_url(home_url(), PHP_URL_HOST) ?: 'mesrondsdeserviette.com';
        return 'noreply@' . $domain;
    }

    /**
     * Forcer le nom expéditeur = nom du site
     */
    public function set_mail_from_name($name)
    {
        return get_bloginfo('name');
    }

    // ========================================
    // SETTINGS ADMIN
    // ========================================
    public function add_settings_page()
    {
        add_options_page(
            'Emails MRDS',
            'Emails MRDS',
            'manage_options',
            'mrds-emails-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings()
    {

        register_setting('mrds_emails_group', 'mrds_email_support_email', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default' => get_option('admin_email'),
        ]);

        register_setting('mrds_emails_group', 'mrds_email_header_image', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);

        register_setting('mrds_emails_group', 'mrds_email_brand_color', [
            'type' => 'string',
            'sanitize_callback' => function ($v) {
                $v = trim((string) $v);
                return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : '#DA9D42';
            },
            'default' => '#DA9D42',
        ]);

        register_setting('mrds_emails_group', 'mrds_email_footer_country', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'France',
        ]);

        add_settings_section(
            'mrds_emails_section',
            'Configuration des emails',
            function () {
                echo '<p>Ces infos sont utilisées dans tous les emails (header/footer).</p>';
            },
            'mrds-emails-settings'
        );

        add_settings_field('mrds_email_support_email', 'Email de support', function () {
            $v = get_option('mrds_email_support_email', get_option('admin_email'));
            echo '<input type="email" class="regular-text" name="mrds_email_support_email" value="' . esc_attr($v) . '">';
        }, 'mrds-emails-settings', 'mrds_emails_section');

        add_settings_field('mrds_email_header_image', 'Image header (URL)', function () {
            $v = get_option('mrds_email_header_image', '');
            echo '<input type="url" class="regular-text" name="mrds_email_header_image" value="' . esc_attr($v) . '" placeholder="https://.../header-email.png">';
            echo '<p class="description">Conseil : mets une URL absolue vers ton image (Media Library).</p>';
        }, 'mrds-emails-settings', 'mrds_emails_section');

        add_settings_field('mrds_email_brand_color', 'Couleur principale', function () {
            $v = get_option('mrds_email_brand_color', '#DA9D42');
            echo '<input type="text" class="regular-text" name="mrds_email_brand_color" value="' . esc_attr($v) . '" placeholder="#DA9D42">';
        }, 'mrds-emails-settings', 'mrds_emails_section');

        add_settings_field('mrds_email_footer_country', 'Texte footer (ex: pays)', function () {
            $v = get_option('mrds_email_footer_country', 'France');
            echo '<input type="text" class="regular-text" name="mrds_email_footer_country" value="' . esc_attr($v) . '">';
        }, 'mrds-emails-settings', 'mrds_emails_section');
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) return;
?>
        <div class="wrap">
            <h1>Emails MRDS</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('mrds_emails_group');
                do_settings_sections('mrds-emails-settings');
                submit_button();
                ?>
            </form>
        </div>
<?php
    }

    // ========================================
    // SETTINGS RUNTIME
    // ========================================
    public function get_settings(): array
    {

        // Support email
        $support_email = get_option('mrds_email_support_email', '');
        if (!is_email($support_email)) {
            // fallback : ancien email en dur, sinon admin_email
            $support_email = 'yasser@coccinet.com';
            if (!is_email($support_email)) {
                $support_email = get_option('admin_email');
            }
        }

        // Header image
        $header_image = get_option('mrds_email_header_image', '');
        if (empty($header_image)) {
            $header_image = 'http://mesrondsdeserviette.intbase.com/wp-content/uploads/2026/01/header-email.png';
        }

        $brand_color = get_option('mrds_email_brand_color', '#DA9D42');
        $footer_country = get_option('mrds_email_footer_country', 'France');

        return [
            'site_name'      => get_bloginfo('name'),
            'support_email'  => $support_email,
            'header_image'   => $header_image,
            'brand_color'    => $brand_color ?: '#DA9D42',
            'footer_country' => $footer_country ?: 'France',
        ];
    }


    private function templates_base_dir(): string
    {
        return defined('MRDS_RESA_PLUGIN_DIR')
            ? rtrim(MRDS_RESA_PLUGIN_DIR, '/\\') . DIRECTORY_SEPARATOR
            : plugin_dir_path(dirname(__FILE__)) . '../';
    }


    // ========================================
    // RENDER TEMPLATES
    // ========================================
    public function render(string $template, array $vars = []): string
    {

        $base = $this->templates_base_dir();
        $content_path = $base . "templates/emails/{$template}.php";
        $layout_path  = $base . "templates/emails/layout.php";

        if (!file_exists($content_path)) {
            error_log("MRDS Email: template introuvable: {$content_path}");
            return '';
        }
        if (!file_exists($layout_path)) {
            error_log("MRDS Email: layout introuvable: {$layout_path}");
            return '';
        }

        $vars = array_merge($this->get_settings(), $vars);

        // 1) Render contenu (pending-member, etc.)
        ob_start();
        extract($vars, EXTR_SKIP);
        include $content_path;
        $content = ob_get_clean();

        // 2) Render layout + contenu
        ob_start();
        extract($vars, EXTR_SKIP);
        include $layout_path; // utilise $content
        return ob_get_clean();
    }

    // ========================================
    // SEND EMAIL
    // ========================================
    public function send(string $to, string $subject, string $template, array $vars = [], array $headers = []): bool
    {

        if (!is_email($to)) {
            error_log("MRDS Email: destinataire invalide: {$to}");
            return false;
        }

        $html = $this->render($template, $vars);
        if (empty($html)) return false;

        $headers = array_merge(['Content-Type: text/html; charset=UTF-8'], $headers);

        return (bool) wp_mail($to, $subject, $html, $headers);
    }
}