<?php

/**
 * Classe MRDS_Resa_Post_Type
 * 
 * Gère le Custom Post Type "mrds_reservation"
 * 
 * @package MRDS_Reservation
 * @author Coccinet
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Resa_Post_Type
{

    private static $instance = null;

    const POST_TYPE = 'mrds_reservation';

    // Statuts de réservation
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REFUSED = 'refused';        // NOUVEAU : Refusé par le restaurant
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_NOSHOW = 'no-show';

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Enregistrer le CPT immédiatement si init est déjà passé, sinon sur init
        if (did_action('init')) {
            $this->register_post_type();
        } else {
            add_action('init', [$this, 'register_post_type']);
        }

        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta_boxes'], 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'custom_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', [$this, 'sortable_columns']);
        add_action('pre_get_posts', [$this, 'custom_orderby']);
        add_action('restrict_manage_posts', [$this, 'add_filters']);
        add_filter('parse_query', [$this, 'filter_query']);
    }
    
    public function register_post_type()
    {
        $labels = [
            'name' => __('Réservations', 'mrds-reservation'),
            'singular_name' => __('Réservation', 'mrds-reservation'),
            'menu_name' => __('Réservations', 'mrds-reservation'),
            'add_new' => __('Ajouter', 'mrds-reservation'),
            'add_new_item' => __('Ajouter une réservation', 'mrds-reservation'),
            'edit_item' => __('Modifier la réservation', 'mrds-reservation'),
            'new_item' => __('Nouvelle réservation', 'mrds-reservation'),
            'view_item' => __('Voir la réservation', 'mrds-reservation'),
            'search_items' => __('Rechercher', 'mrds-reservation'),
            'not_found' => __('Aucune réservation trouvée', 'mrds-reservation'),
            'not_found_in_trash' => __('Aucune réservation dans la corbeille', 'mrds-reservation'),
            'all_items' => __('Toutes les réservations', 'mrds-reservation'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => false,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title'],
            'show_in_rest' => false,
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'mrds_resa_details',
            __('Détails de la réservation', 'mrds-reservation'),
            [$this, 'render_details_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'mrds_resa_member',
            __('Informations du membre', 'mrds-reservation'),
            [$this, 'render_member_meta_box'],
            self::POST_TYPE,
            'side',
            'default'
        );

        add_meta_box(
            'mrds_resa_status',
            __('Statut', 'mrds-reservation'),
            [$this, 'render_status_meta_box'],
            self::POST_TYPE,
            'side',
            'high'
        );
    }

    public function render_details_meta_box($post)
    {
        wp_nonce_field('mrds_resa_details', 'mrds_resa_details_nonce');

        $restaurant_id = get_post_meta($post->ID, '_mrds_restaurant_id', true);
        $date = get_post_meta($post->ID, '_mrds_date', true);
        $time = get_post_meta($post->ID, '_mrds_time', true);
        $guests = get_post_meta($post->ID, '_mrds_guests', true);
        $phone = get_post_meta($post->ID, '_mrds_phone', true);
        $email = get_post_meta($post->ID, '_mrds_email', true);
        $occasion = get_post_meta($post->ID, '_mrds_occasion', true);
        $allergies = get_post_meta($post->ID, '_mrds_allergies', true);
        $preferences = get_post_meta($post->ID, '_mrds_preferences', true);

        $restaurants = get_posts([
            'post_type' => 'restaurant',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
?>
        <table class="form-table">
            <tr>
                <th><label for="mrds_restaurant_id"><?php _e('Restaurant', 'mrds-reservation'); ?></label></th>
                <td>
                    <select name="mrds_restaurant_id" id="mrds_restaurant_id" class="regular-text">
                        <option value=""><?php _e('Sélectionner un restaurant', 'mrds-reservation'); ?></option>
                        <?php foreach ($restaurants as $restaurant) : ?>
                            <option value="<?php echo esc_attr($restaurant->ID); ?>" <?php selected($restaurant_id, $restaurant->ID); ?>>
                                <?php echo esc_html($restaurant->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="mrds_date"><?php _e('Date', 'mrds-reservation'); ?></label></th>
                <td><input type="date" name="mrds_date" id="mrds_date" value="<?php echo esc_attr($date); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="mrds_time"><?php _e('Heure', 'mrds-reservation'); ?></label></th>
                <td><input type="time" name="mrds_time" id="mrds_time" value="<?php echo esc_attr($time); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="mrds_guests"><?php _e('Nombre de personnes', 'mrds-reservation'); ?></label></th>
                <td><input type="number" name="mrds_guests" id="mrds_guests" value="<?php echo esc_attr($guests); ?>" min="1" max="20" class="small-text"></td>
            </tr>
            <tr>
                <th><label for="mrds_phone"><?php _e('Téléphone', 'mrds-reservation'); ?></label></th>
                <td><input type="tel" name="mrds_phone" id="mrds_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="mrds_email"><?php _e('Email', 'mrds-reservation'); ?></label></th>
                <td><input type="email" name="mrds_email" id="mrds_email" value="<?php echo esc_attr($email); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="mrds_occasion"><?php _e('Occasion', 'mrds-reservation'); ?></label></th>
                <td>
                    <select name="mrds_occasion" id="mrds_occasion" class="regular-text">
                        <option value=""><?php _e('Aucune', 'mrds-reservation'); ?></option>
                        <option value="anniversaire" <?php selected($occasion, 'anniversaire'); ?>><?php _e('Anniversaire', 'mrds-reservation'); ?></option>
                        <option value="anniversaire_mariage" <?php selected($occasion, 'anniversaire_mariage'); ?>><?php _e('Anniversaire de mariage', 'mrds-reservation'); ?></option>
                        <option value="saint_valentin" <?php selected($occasion, 'saint_valentin'); ?>><?php _e('Saint-Valentin', 'mrds-reservation'); ?></option>
                        <option value="affaires" <?php selected($occasion, 'affaires'); ?>><?php _e('Repas d\'affaires', 'mrds-reservation'); ?></option>
                        <option value="autre" <?php selected($occasion, 'autre'); ?>><?php _e('Autre célébration', 'mrds-reservation'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="mrds_allergies"><?php _e('Allergies', 'mrds-reservation'); ?></label></th>
                <td><textarea name="mrds_allergies" id="mrds_allergies" class="large-text" rows="2"><?php echo esc_textarea($allergies); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="mrds_preferences"><?php _e('Préférences', 'mrds-reservation'); ?></label></th>
                <td><textarea name="mrds_preferences" id="mrds_preferences" class="large-text" rows="2"><?php echo esc_textarea($preferences); ?></textarea></td>
            </tr>
        </table>
<?php
    }

    public function render_member_meta_box($post)
    {
        $user_id = get_post_meta($post->ID, '_mrds_user_id', true);
        $user = $user_id ? get_userdata($user_id) : null;
?>
        <p>
            <label for="mrds_user_id"><strong><?php _e('Membre :', 'mrds-reservation'); ?></strong></label><br>
            <?php if ($user) : ?>
                <a href="<?php echo get_edit_user_link($user_id); ?>"><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></a><br>
                <small><?php echo esc_html($user->user_email); ?></small>
            <?php else : ?>
                <em><?php _e('Aucun membre associé', 'mrds-reservation'); ?></em>
            <?php endif; ?>
        </p>
        <input type="hidden" name="mrds_user_id" value="<?php echo esc_attr($user_id); ?>">
<?php
    }

    public function render_status_meta_box($post)
    {
        $status = get_post_meta($post->ID, '_mrds_status', true) ?: self::STATUS_PENDING;
        $statuses = $this->get_statuses();
?>
        <p>
            <select name="mrds_status" id="mrds_status" style="width: 100%;">
                <?php foreach ($statuses as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
<?php
    }

    public function save_meta_boxes($post_id, $post)
    {
        if (!isset($_POST['mrds_resa_details_nonce']) || !wp_verify_nonce($_POST['mrds_resa_details_nonce'], 'mrds_resa_details')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $fields = [
            'mrds_restaurant_id' => '_mrds_restaurant_id',
            'mrds_user_id' => '_mrds_user_id',
            'mrds_date' => '_mrds_date',
            'mrds_time' => '_mrds_time',
            'mrds_guests' => '_mrds_guests',
            'mrds_phone' => '_mrds_phone',
            'mrds_email' => '_mrds_email',
            'mrds_occasion' => '_mrds_occasion',
            'mrds_allergies' => '_mrds_allergies',
            'mrds_preferences' => '_mrds_preferences',
            'mrds_status' => '_mrds_status',
        ];

        foreach ($fields as $post_key => $meta_key) {
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
            }
        }

        // Mettre à jour le titre
        $restaurant_id = get_post_meta($post_id, '_mrds_restaurant_id', true);
        $user_id = get_post_meta($post_id, '_mrds_user_id', true);
        $date = get_post_meta($post_id, '_mrds_date', true);

        if ($restaurant_id && $user_id && $date) {
            $restaurant = get_post($restaurant_id);
            $user = get_userdata($user_id);
            if ($restaurant && $user) {
                $title = sprintf('%s - %s %s - %s', $restaurant->post_title, $user->first_name, $user->last_name, date_i18n('d/m/Y', strtotime($date)));
                remove_action('save_post_' . self::POST_TYPE, [$this, 'save_meta_boxes'], 10);
                wp_update_post(['ID' => $post_id, 'post_title' => $title]);
                add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta_boxes'], 10, 2);
            }
        }
    }

    public function get_statuses()
    {
        return [
            self::STATUS_PENDING => __('En attente', 'mrds-reservation'),
            self::STATUS_CONFIRMED => __('Confirmée', 'mrds-reservation'),
            self::STATUS_REFUSED => __('Refusée', 'mrds-reservation'),      // NOUVEAU
            self::STATUS_CANCELLED => __('Annulée', 'mrds-reservation'),
            self::STATUS_COMPLETED => __('Effectuée', 'mrds-reservation'),
            self::STATUS_NOSHOW => __('Absent', 'mrds-reservation'),
        ];
    }

    public function get_status_label($status)
    {
        $statuses = $this->get_statuses();
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }

    public function custom_columns($columns)
    {
        return [
            'cb' => $columns['cb'],
            'title' => __('Réservation', 'mrds-reservation'),
            'restaurant' => __('Restaurant', 'mrds-reservation'),
            'member' => __('Membre', 'mrds-reservation'),
            'date_time' => __('Date & Heure', 'mrds-reservation'),
            'guests' => __('Couverts', 'mrds-reservation'),
            'status' => __('Statut', 'mrds-reservation'),
            'date' => $columns['date'],
        ];
    }

    public function custom_column_content($column, $post_id)
    {
        switch ($column) {
            case 'restaurant':
                $restaurant_id = get_post_meta($post_id, '_mrds_restaurant_id', true);
                if ($restaurant_id) {
                    $restaurant = get_post($restaurant_id);
                    if ($restaurant) {
                        echo '<a href="' . get_edit_post_link($restaurant_id) . '">' . esc_html($restaurant->post_title) . '</a>';
                    }
                }
                break;

            case 'member':
                $user_id = get_post_meta($post_id, '_mrds_user_id', true);
                if ($user_id) {
                    $user = get_userdata($user_id);
                    if ($user) {
                        echo '<a href="' . get_edit_user_link($user_id) . '">' . esc_html($user->first_name . ' ' . $user->last_name) . '</a><br>';
                        echo '<small>' . esc_html($user->user_email) . '</small>';
                    }
                }
                break;

            case 'date_time':
                $date = get_post_meta($post_id, '_mrds_date', true);
                $time = get_post_meta($post_id, '_mrds_time', true);
                if ($date) {
                    echo '<strong>' . date_i18n('l j F Y', strtotime($date)) . '</strong>';
                    if ($time) echo '<br>' . esc_html($time);
                }
                break;

            case 'guests':
                $guests = get_post_meta($post_id, '_mrds_guests', true);
                echo $guests ? esc_html($guests) : '-';
                break;

            case 'status':
                $status = get_post_meta($post_id, '_mrds_status', true) ?: self::STATUS_PENDING;
                echo '<span class="mrds-status mrds-status-' . esc_attr($status) . '">' . esc_html($this->get_status_label($status)) . '</span>';
                break;
        }
    }

    public function sortable_columns($columns)
    {
        $columns['date_time'] = 'reservation_date';
        $columns['status'] = 'status';
        return $columns;
    }

    public function custom_orderby($query)
    {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== self::POST_TYPE) {
            return;
        }
        $orderby = $query->get('orderby');
        if ($orderby === 'reservation_date') {
            $query->set('meta_key', '_mrds_date');
            $query->set('orderby', 'meta_value');
        } elseif ($orderby === 'status') {
            $query->set('meta_key', '_mrds_status');
            $query->set('orderby', 'meta_value');
        }
    }

    public function add_filters()
    {
        global $typenow;
        if ($typenow !== self::POST_TYPE) return;

        // Filtre restaurant
        $restaurants = get_posts(['post_type' => 'restaurant', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
        $selected_restaurant = $_GET['restaurant_filter'] ?? '';
        echo '<select name="restaurant_filter"><option value="">' . __('Tous les restaurants', 'mrds-reservation') . '</option>';
        foreach ($restaurants as $r) {
            printf('<option value="%s" %s>%s</option>', $r->ID, selected($selected_restaurant, $r->ID, false), esc_html($r->post_title));
        }
        echo '</select>';

        // Filtre statut
        $selected_status = $_GET['status_filter'] ?? '';
        echo '<select name="status_filter"><option value="">' . __('Tous les statuts', 'mrds-reservation') . '</option>';
        foreach ($this->get_statuses() as $value => $label) {
            printf('<option value="%s" %s>%s</option>', $value, selected($selected_status, $value, false), esc_html($label));
        }
        echo '</select>';
    }

    public function filter_query($query)
    {
        global $pagenow, $typenow;
        if ($pagenow !== 'edit.php' || $typenow !== self::POST_TYPE || !$query->is_main_query()) return;

        $meta_query = [];
        if (!empty($_GET['restaurant_filter'])) {
            $meta_query[] = ['key' => '_mrds_restaurant_id', 'value' => sanitize_text_field($_GET['restaurant_filter'])];
        }
        if (!empty($_GET['status_filter'])) {
            $meta_query[] = ['key' => '_mrds_status', 'value' => sanitize_text_field($_GET['status_filter'])];
        }
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }
}