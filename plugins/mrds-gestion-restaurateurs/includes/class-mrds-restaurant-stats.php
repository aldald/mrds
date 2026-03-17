<?php
/**
 * MRDS Restaurant Stats - Classe de statistiques
 *
 * @package mrds-gestion-restaurateurs
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Restaurant_Stats
{
    protected static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_shortcode('mrds_restaurant_stats', [$this, 'render_stats']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * ENQUEUE ASSETS
     */
    public function enqueue_assets()
    {
        if (!is_user_logged_in()) {
            return;
        }

        wp_register_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        wp_register_script(
            'mrds-restaurant-stats',
            MRDS_RESTAURATEURS_PLUGIN_URL . 'assets/js/restaurant-stats.js',
            ['chartjs'],
            MRDS_RESTAURATEURS_VERSION,
            true
        );

        wp_localize_script('mrds-restaurant-stats', 'MRDSStatsConfig', [
            'restUrl' => esc_url_raw(rest_url('mrds/v1/stats')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        wp_register_style(
            'mrds-restaurant-stats',
            MRDS_RESTAURATEURS_PLUGIN_URL . 'assets/css/restaurant-stats.css',
            [],
            MRDS_RESTAURATEURS_VERSION
        );
    }

    /**
     * REST API
     */
    public function register_rest_routes()
    {
        register_rest_route('mrds/v1', '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_stats'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);
    }

    public function rest_get_stats()
    {
        $user_id = get_current_user_id();
        return rest_ensure_response($this->get_all_stats_for_user($user_id));
    }

    /**
     * COMPTEUR RÉSERVATIONS
     */
    public function get_reservations_count($restaurant_id, $period = 'month')
    {
        $dates = $this->get_date_range($period);

        $args = [
            'post_type' => 'mrds_reservation',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => '_mrds_restaurant_id', 'value' => $restaurant_id],
                ['key' => '_mrds_date', 'value' => $dates['start'], 'compare' => '>=', 'type' => 'DATE'],
                ['key' => '_mrds_date', 'value' => $dates['end'], 'compare' => '<=', 'type' => 'DATE'],
            ],
            'fields' => 'ids',
        ];

        $query = new WP_Query($args);
        $guests = 0;

        foreach ($query->posts as $id) {
            $guests += (int) get_post_meta($id, '_mrds_guests', true);
        }

        wp_reset_postdata();

        return ['reservations' => $query->found_posts, 'guests' => $guests];
    }

    /**
     * RÉSERVATIONS PAR MOIS
     */
    public function get_reservations_by_month($restaurant_id, $months = 6)
    {
        $data = [];
        $now = new DateTime();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = (clone $now)->modify("-{$i} months");
            $start = $date->format('Y-m-01');
            $end = $date->format('Y-m-t');

            $args = [
                'post_type' => 'mrds_reservation',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => '_mrds_restaurant_id', 'value' => $restaurant_id],
                    ['key' => '_mrds_date', 'value' => $start, 'compare' => '>=', 'type' => 'DATE'],
                    ['key' => '_mrds_date', 'value' => $end, 'compare' => '<=', 'type' => 'DATE'],
                ],
                'fields' => 'ids',
            ];

            $query = new WP_Query($args);
            $data[] = [
                'month' => $date->format('M Y'),
                'month_short' => $date->format('M'),
                'count' => $query->found_posts,
            ];
            wp_reset_postdata();
        }

        return $data;
    }

    /**
     * JOURS POPULAIRES
     */
    public function get_popular_days($restaurant_id)
    {
        $days = ['Lundi' => 0, 'Mardi' => 0, 'Mercredi' => 0, 'Jeudi' => 0, 'Vendredi' => 0, 'Samedi' => 0, 'Dimanche' => 0];
        $map = ['Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche'];

        $args = [
            'post_type' => 'mrds_reservation',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [['key' => '_mrds_restaurant_id', 'value' => $restaurant_id]],
        ];

        $query = new WP_Query($args);

        while ($query->have_posts()) {
            $query->the_post();
            $date = get_post_meta(get_the_ID(), '_mrds_date', true);
            if ($date) {
                $day = $map[date('l', strtotime($date))] ?? '';
                if (isset($days[$day])) $days[$day]++;
            }
        }
        wp_reset_postdata();

        arsort($days);
        $total = array_sum($days);
        $result = [];

        foreach ($days as $day => $count) {
            $result[] = [
                'day' => $day,
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100) : 0,
            ];
        }

        return $result;
    }

    /**
     * STATS AGRÉGÉES POUR UN USER
     */
    public function get_all_stats_for_user($user_id)
    {
        $restaurant_ids = $this->get_user_restaurant_ids($user_id);

        if (empty($restaurant_ids)) {
            return [
                'restaurants' => [],
                'totals' => ['reservations_month' => 0, 'guests_month' => 0, 'reservations_prev_month' => 0, 'evolution_percent' => 0],
                'by_month' => [],
                'popular_days' => [],
            ];
        }

        $totals = ['reservations_month' => 0, 'guests_month' => 0, 'reservations_prev_month' => 0];
        $restaurants_data = [];
        $all_by_month = [];
        $all_days = [];

        foreach ($restaurant_ids as $resto_id) {
            $month = $this->get_reservations_count($resto_id, 'month');
            $prev = $this->get_reservations_count($resto_id, 'prev_month');

            $totals['reservations_month'] += $month['reservations'];
            $totals['guests_month'] += $month['guests'];
            $totals['reservations_prev_month'] += $prev['reservations'];

            $restaurants_data[] = [
                'id' => $resto_id,
                'name' => get_the_title($resto_id),
                'reservations_month' => $month['reservations'],
                'guests_month' => $month['guests'],
            ];

            foreach ($this->get_reservations_by_month($resto_id, 6) as $idx => $m) {
                if (!isset($all_by_month[$idx])) {
                    $all_by_month[$idx] = ['month' => $m['month'], 'month_short' => $m['month_short'], 'count' => 0];
                }
                $all_by_month[$idx]['count'] += $m['count'];
            }

            foreach ($this->get_popular_days($resto_id) as $d) {
                $all_days[$d['day']] = ($all_days[$d['day']] ?? 0) + $d['count'];
            }
        }

        // Évolution
        $totals['evolution_percent'] = $totals['reservations_prev_month'] > 0
            ? round((($totals['reservations_month'] - $totals['reservations_prev_month']) / $totals['reservations_prev_month']) * 100)
            : ($totals['reservations_month'] > 0 ? 100 : 0);

        // Jours populaires
        arsort($all_days);
        $total_days = array_sum($all_days);
        $popular_days = [];
        foreach ($all_days as $day => $count) {
            $popular_days[] = ['day' => $day, 'count' => $count, 'percent' => $total_days > 0 ? round(($count / $total_days) * 100) : 0];
        }

        return [
            'restaurants' => $restaurants_data,
            'totals' => $totals,
            'by_month' => array_values($all_by_month),
            'popular_days' => array_slice($popular_days, 0, 5),
        ];
    }

    /**
     * HELPERS
     */
    private function get_date_range($period)
    {
        $now = new DateTime();
        switch ($period) {
            case 'prev_month':
                return ['start' => (clone $now)->modify('first day of last month')->format('Y-m-d'), 'end' => (clone $now)->modify('last day of last month')->format('Y-m-d')];
            default:
                return ['start' => $now->format('Y-m-01'), 'end' => $now->format('Y-m-t')];
        }
    }

    private function get_user_restaurant_ids($user_id)
    {
        $user = get_user_by('id', $user_id);
        if (!$user) return [];

        $roles = (array) $user->roles;

        if (in_array('administrator', $roles, true)) {
            return get_posts(['post_type' => 'restaurant', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
        }

        $ids = [];

        if (in_array('super_restaurateur', $roles, true)) {
            $ids = array_merge($ids, get_posts([
                'post_type' => 'restaurant',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => [['key' => 'restaurant_owner', 'value' => $user_id]],
                'fields' => 'ids',
            ]));
        }

        if (in_array('restaurateur', $roles, true) || in_array('super_restaurateur', $roles, true)) {
            $ids = array_merge($ids, get_posts([
                'post_type' => 'restaurant',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => [['key' => 'restaurant_restaurateurs', 'value' => '"' . $user_id . '"', 'compare' => 'LIKE']],
                'fields' => 'ids',
            ]));
        }

        return array_unique($ids);
    }

    /**
     * SHORTCODE
     */
    public function render_stats()
    {
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning">Vous devez être connecté.</div>';
        }

        $user = wp_get_current_user();
        if (!array_intersect(['administrator', 'super_restaurateur', 'restaurateur'], (array) $user->roles)) {
            return '<div class="alert alert-danger">Accès refusé.</div>';
        }

        wp_enqueue_script('chartjs');
        wp_enqueue_script('mrds-restaurant-stats');
        wp_enqueue_style('mrds-restaurant-stats');

        ob_start();
        include MRDS_RESTAURATEURS_PLUGIN_DIR . 'templates/restaurant-stats-template.php';
        return ob_get_clean();
    }
}
