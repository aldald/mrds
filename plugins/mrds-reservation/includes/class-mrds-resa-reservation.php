<?php

/**
 * Classe MRDS_Resa_Reservation
 * 
 * Gère la logique métier des réservations
 * 
 * @package MRDS_Reservation
 * @author Coccinet
 * 
 * MODIFICATION : Système de validation par le restaurateur
 * - Statut initial = pending (au lieu de confirmed)
 * - Email membre = "en attente de confirmation"
 * - Email gestionnaires = notification simple avec lien back-office
 * - Changement de statut dans le back-office → email automatique au membre
 */

if (!defined('ABSPATH')) {
    exit;
}

class MRDS_Resa_Reservation
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
        // Email au membre (en attente)
        add_action('mrds_resa_created', [$this, 'send_pending_email_to_member'], 10, 2);

        // Email aux gestionnaires (notification simple)
        add_action('mrds_resa_created', [$this, 'send_notification_email_to_restaurant'], 10, 2);

        // Hook sur le changement de statut (back-office)
        add_action('updated_post_meta', [$this, 'on_status_change'], 10, 4);
    }

    public function create_reservation($data)
    {
        // ========================================
        // CONVERTIR LA DATE DU FORMAT FRANÇAIS
        // ========================================
        if (!empty($data['date']) && strpos($data['date'], '/') !== false) {
            $date_parts = explode('/', $data['date']);
            if (count($date_parts) === 3) {
                // dd/mm/YYYY -> YYYY-mm-dd
                $data['date'] = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
            }
        }
        // ========================================

        // Valider les données
        $validation = $this->validate_reservation_data($data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Vérifier la règle 1 réservation par restaurant par an
        // $can_book = $this->can_user_book($data['user_id'], $data['restaurant_id']);
        // if (is_wp_error($can_book)) {
        //     return $can_book;
        // }

        // Vérifier les horaires du restaurant
        $hours_valid = $this->validate_restaurant_hours($data['restaurant_id'], $data['date'], $data['time']);
        if (is_wp_error($hours_valid)) {
            return $hours_valid;
        }

        // Créer le post
        $restaurant = get_post($data['restaurant_id']);
        $user = get_userdata($data['user_id']);

        $title = sprintf(
            '%s - %s %s - %s',
            $restaurant->post_title,
            $user->first_name,
            $user->last_name,
            date_i18n('d/m/Y', strtotime($data['date']))
        );

        $reservation_id = wp_insert_post([
            'post_type' => MRDS_Resa_Post_Type::POST_TYPE,
            'post_title' => $title,
            'post_status' => 'publish',
            'post_author' => $data['user_id'],
        ]);

        if (is_wp_error($reservation_id)) {
            return $reservation_id;
        }

        // Sauvegarder les meta données
        update_post_meta($reservation_id, '_mrds_user_id', $data['user_id']);
        update_post_meta($reservation_id, '_mrds_restaurant_id', $data['restaurant_id']);
        update_post_meta($reservation_id, '_mrds_date', $data['date']);
        update_post_meta($reservation_id, '_mrds_time', $data['time']);
        update_post_meta($reservation_id, '_mrds_guests', $data['guests']);
        update_post_meta($reservation_id, '_mrds_phone', $data['phone']);
        update_post_meta($reservation_id, '_mrds_email', $data['email']);
        update_post_meta($reservation_id, '_mrds_occasion', $data['occasion'] ?? '');
        update_post_meta($reservation_id, '_mrds_allergies', $data['allergies'] ?? '');
        update_post_meta($reservation_id, '_mrds_preferences', $data['preferences'] ?? '');

        // ========================================
        // MODIFICATION : Statut = PENDING (en attente de validation)
        // ========================================
        update_post_meta($reservation_id, '_mrds_status', MRDS_Resa_Post_Type::STATUS_PENDING);

        update_post_meta($reservation_id, '_mrds_created_at', current_time('mysql'));

        // Déclencher l'action post-création (emails)
        try {
            do_action('mrds_resa_created', $reservation_id, $data);
        } catch (Exception $e) {
            error_log('MRDS Email Error: ' . $e->getMessage());
        }

        return $reservation_id;
    }

    /**
     * Valider les données de réservation
     */
    public function validate_reservation_data($data)
    {
        $errors = new WP_Error();

        // Champs obligatoires
        $required = ['user_id', 'restaurant_id', 'date', 'time', 'guests', 'phone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors->add('missing_field', sprintf(__('Le champ %s est obligatoire.', 'mrds-reservation'), $field));
            }
        }

        // Vérifier l'utilisateur
        if (!empty($data['user_id'])) {
            $user = get_userdata($data['user_id']);
            if (!$user) {
                $errors->add('invalid_user', __('Utilisateur invalide.', 'mrds-reservation'));
            }
        }

        // Vérifier le restaurant
        if (!empty($data['restaurant_id'])) {
            $restaurant = get_post($data['restaurant_id']);
            if (!$restaurant || $restaurant->post_type !== 'restaurant') {
                $errors->add('invalid_restaurant', __('Restaurant invalide.', 'mrds-reservation'));
            }
        }

        // Vérifier la date (pas dans le passé)
        if (!empty($data['date'])) {
            // Convertir la date du format français dd/mm/YYYY vers YYYY-mm-dd
            $date_parts = explode('/', $data['date']);
            if (count($date_parts) === 3) {
                $formatted_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0]; // YYYY-mm-dd
            } else {
                $formatted_date = $data['date'];
            }
            $reservation_date = strtotime($formatted_date);
            $today = strtotime(date('Y-m-d'));
            if ($reservation_date < $today) {
                $errors->add('past_date', __('La date de réservation ne peut pas être dans le passé.', 'mrds-reservation'));
            }
        }

        // Vérifier le nombre de personnes
        if (!empty($data['guests'])) {
            $guests = intval($data['guests']);
            if ($guests < 1 || $guests > 20) {
                $errors->add('invalid_guests', __('Le nombre de personnes doit être entre 1 et 20.', 'mrds-reservation'));
            }
        }

        // Vérifier le téléphone
        if (!empty($data['phone'])) {
            $phone = preg_replace('/[^0-9+]/', '', $data['phone']);
            if (strlen($phone) < 10) {
                $errors->add('invalid_phone', __('Numéro de téléphone invalide.', 'mrds-reservation'));
            }
        }

        // Vérifier l'email si fourni
        if (!empty($data['email']) && !is_email($data['email'])) {
            $errors->add('invalid_email', __('Adresse email invalide.', 'mrds-reservation'));
        }

        if ($errors->has_errors()) {
            return $errors;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut réserver (règle 1/an par restaurant)
     */
    public function can_user_book($user_id, $restaurant_id)
    {
        $one_year_ago = date('Y-m-d', strtotime('-1 year'));

        $args = [
            'post_type' => 'mrds_reservation',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_mrds_user_id',
                    'value' => $user_id,
                    'compare' => '='
                ],
                [
                    'key' => '_mrds_restaurant_id',
                    'value' => $restaurant_id,
                    'compare' => '='
                ],
                [
                    'key' => '_mrds_status',
                    'value' => [MRDS_Resa_Post_Type::STATUS_CONFIRMED, MRDS_Resa_Post_Type::STATUS_PENDING, MRDS_Resa_Post_Type::STATUS_COMPLETED],
                    'compare' => 'IN'
                ],
                [
                    'key' => '_mrds_date',
                    'value' => $one_year_ago,
                    'compare' => '>=',
                    'type' => 'DATE'
                ]
            ]
        ];

        $existing = new WP_Query($args);

        if ($existing->have_posts()) {
            $resa = $existing->posts[0];
            $resa_date = get_post_meta($resa->ID, '_mrds_date', true);
            $next_available = date('d/m/Y', strtotime($resa_date . ' +1 year'));

            return new WP_Error(
                'already_booked',
                sprintf(
                    __('Vous avez déjà une réservation dans ce restaurant. Vous pourrez réserver à nouveau à partir du %s.', 'mrds-reservation'),
                    $next_available
                )
            );
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur a déjà réservé dans ce restaurant
     */
    public function user_has_booked_restaurant($user_id, $restaurant_id)
    {
        $can_book = $this->can_user_book($user_id, $restaurant_id);
        return is_wp_error($can_book);
    }

    /**
     * Valider les horaires du restaurant
     */
    public function validate_restaurant_hours($restaurant_id, $date, $time)
    {
        // Récupérer les horaires ACF
        $horaires = get_field('horaires', $restaurant_id);

        if (empty($horaires)) {
            // Pas d'horaires définis = toujours ouvert
            return true;
        }

        // Déterminer le jour de la semaine
        $day_of_week = date('N', strtotime($date)); // 1=Lundi, 7=Dimanche
        $day_map = [
            1 => 'L',
            2 => 'Mar',
            3 => 'Mer',
            4 => 'J',
            5 => 'V',
            6 => 'S',
            7 => 'D'
        ];
        $day_code = $day_map[$day_of_week];

        // Déterminer la période selon l'heure
        $hour = intval(substr($time, 0, 2));
        if ($hour >= 8 && $hour < 12) {
            $periode = 'Matin';
        } elseif ($hour >= 12 && $hour < 15) {
            $periode = 'Midi';
        } else {
            $periode = 'Soir';
        }

        // Vérifier si ouvert ce jour à cette période
        foreach ($horaires as $horaire) {
            if ($horaire['periode'] === $periode) {
                $jours = $horaire['jours'] ?? [];
                if (in_array($day_code, $jours)) {
                    return true;
                }
            }
        }

        return new WP_Error(
            'restaurant_closed',
            __('Le restaurant est fermé à cette date/heure.', 'mrds-reservation')
        );
    }

    /**
     * Obtenir les périodes disponibles pour une date
     */
    public function get_available_periods($restaurant_id, $date)
    {
        $horaires = get_field('horaires', $restaurant_id);
        $available = [];

        if (empty($horaires)) {
            // Pas d'horaires = tout ouvert
            return ['Matin' => true, 'Midi' => true, 'Soir' => true];
        }

        // Jour de la semaine
        $day_of_week = date('N', strtotime($date));
        $day_map = [
            1 => 'L',
            2 => 'Mar',
            3 => 'Mer',
            4 => 'J',
            5 => 'V',
            6 => 'S',
            7 => 'D'
        ];
        $day_code = $day_map[$day_of_week];

        foreach ($horaires as $horaire) {
            $periode = $horaire['periode'];
            $jours = $horaire['jours'] ?? [];
            $available[$periode] = in_array($day_code, $jours);
        }

        return $available;
    }

    /**
     * Obtenir les créneaux horaires pour une période
     */
    public function get_time_slots($periode)
    {
        $slots = [];

        switch ($periode) {
            case 'Matin':
                for ($h = 8; $h <= 11; $h++) {
                    $slots[] = sprintf('%02d:00', $h);
                    $slots[] = sprintf('%02d:15', $h);
                    $slots[] = sprintf('%02d:30', $h);
                    $slots[] = sprintf('%02d:45', $h);
                }
                break;

            case 'Midi':
                for ($h = 12; $h <= 14; $h++) {
                    $slots[] = sprintf('%02d:00', $h);
                    $slots[] = sprintf('%02d:15', $h);
                    $slots[] = sprintf('%02d:30', $h);
                    $slots[] = sprintf('%02d:45', $h);
                }
                break;

            case 'Soir':
                for ($h = 19; $h <= 22; $h++) {
                    $slots[] = sprintf('%02d:00', $h);
                    $slots[] = sprintf('%02d:15', $h);
                    $slots[] = sprintf('%02d:30', $h);
                    $slots[] = sprintf('%02d:45', $h);
                }
                break;
        }

        return $slots;
    }

    /**
     * Obtenir les jours de fermeture d'un restaurant
     */
    public function get_closed_days($restaurant_id)
    {
        $horaires = get_field('horaires', $restaurant_id);

        if (empty($horaires)) {
            return []; // Aucun jour fermé
        }

        $day_map = [
            'L' => 1,
            'Mar' => 2,
            'Mer' => 3,
            'J' => 4,
            'V' => 5,
            'S' => 6,
            'D' => 7
        ];

        // Collecter tous les jours ouverts
        $open_days = [];
        foreach ($horaires as $horaire) {
            $jours = $horaire['jours'] ?? [];
            foreach ($jours as $jour) {
                if (isset($day_map[$jour])) {
                    $open_days[$day_map[$jour]] = true;
                }
            }
        }

        // Retourner les jours fermés
        $closed_days = [];
        for ($d = 1; $d <= 7; $d++) {
            if (!isset($open_days[$d])) {
                $closed_days[] = $d;
            }
        }

        return $closed_days;
    }

    /**
     * Obtenir les réservations à venir d'un utilisateur
     */
    public function get_user_upcoming_reservations($user_id, $limit = -1)
    {
        $today = date('Y-m-d');

        $query = new WP_Query([
            'post_type' => MRDS_Resa_Post_Type::POST_TYPE,
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_mrds_user_id', 'value' => $user_id],
                ['key' => '_mrds_date', 'value' => $today, 'compare' => '>=', 'type' => 'DATE'],
                [
                    'key' => '_mrds_status',
                    'value' => [MRDS_Resa_Post_Type::STATUS_CANCELLED, MRDS_Resa_Post_Type::STATUS_REFUSED],
                    'compare' => 'NOT IN'
                ],
            ],
            'meta_key' => '_mrds_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ]);

        return $this->format_reservations($query->posts);
    }

    /**
     * Obtenir les réservations passées d'un utilisateur
     */
    public function get_user_past_reservations($user_id, $limit = -1)
    {
        $today = date('Y-m-d');

        $query = new WP_Query([
            'post_type' => MRDS_Resa_Post_Type::POST_TYPE,
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_mrds_user_id', 'value' => $user_id],
                ['key' => '_mrds_date', 'value' => $today, 'compare' => '<', 'type' => 'DATE'],
                ['key' => '_mrds_status', 'value' => MRDS_Resa_Post_Type::STATUS_CANCELLED, 'compare' => '!='],
            ],
            'meta_key' => '_mrds_date',
            'orderby' => 'meta_value',
            'order' => 'DESC',
        ]);

        return $this->format_reservations($query->posts);
    }

    /**
     * Formater les réservations pour l'affichage
     */
    private function format_reservations($posts)
    {
        $reservations = [];

        foreach ($posts as $post) {
            $restaurant_id = get_post_meta($post->ID, '_mrds_restaurant_id', true);
            $restaurant = get_post($restaurant_id);

            if (!$restaurant) continue;

            $adresse = get_field('adresse', $restaurant_id);
            $arrondissement = $adresse['arrondissement'] ?? '';
            $location = $arrondissement ? 'Paris ' . $arrondissement . ($arrondissement == 1 ? 'er' : 'e') : '';

            $tags = [];
            $tags_ids = get_field('tags_restaurant', $restaurant_id);
            if ($tags_ids) {
                foreach ($tags_ids as $tag_id) {
                    $term = get_term($tag_id);
                    if ($term && !is_wp_error($term)) {
                        $tags[] = $term->name;
                    }
                }
            }

            $citation = get_field('citation_de_restaurant', $restaurant_id);

            $resa_date = get_post_meta($post->ID, '_mrds_date', true);

            $reservations[] = [
                'id' => $post->ID,
                'restaurant_id' => $restaurant_id,
                'restaurant_name' => $restaurant->post_title,
                'restaurant_link' => get_permalink($restaurant_id),
                'restaurant_image' => get_the_post_thumbnail_url($restaurant_id, 'medium'),
                'location' => $location,
                'tags' => $tags,
                'citation' => $citation['description'] ?? '',
                'citation_auteur' => $citation['auteur'] ?? '',
                'date' => $resa_date,
                'date_formatted' => date_i18n('l j F', strtotime($resa_date)),
                'time' => get_post_meta($post->ID, '_mrds_time', true),
                'time_formatted' => str_replace(':', 'h', get_post_meta($post->ID, '_mrds_time', true)),
                'guests' => get_post_meta($post->ID, '_mrds_guests', true),
                'status' => get_post_meta($post->ID, '_mrds_status', true),
                'remise' => $this->get_restaurant_remise_for_email($restaurant_id, $resa_date),
            ];
        }

        return $reservations;
    }

    // ========================================
    // EMAILS
    // ========================================

    /**
     * Construire l'adresse du restaurant pour les emails (sans arrondissement)
     */
    private function get_restaurant_address_for_email($restaurant_id)
    {
        $adresse = get_field('adresse', $restaurant_id);
        if (empty($adresse)) return '';

        $rue        = $adresse['adresse_rue'] ?? '';
        $complement = $adresse["complement_d'adresse"] ?? '';
        $cp         = $adresse['code_postal'] ?? '';
        $ville      = $adresse['ville'] ?? '';

        $lines = array_filter([$rue, $complement, trim($cp . ' ' . $ville)]);
        return implode(', ', $lines);
    }

    /**
     * Récupérer le texte de remise applicable (si le plugin de remises est actif)
     */
    private function get_restaurant_remise_for_email($restaurant_id, $date)
    {
        if (!class_exists('MRDS_Remises_management')) {
            return '';
        }

        $remise_text = MRDS_Remises_management::get_instance()
            ->get_applicable_remises_for_restaurant($restaurant_id, $date);

        if (empty($remise_text)) {
            return '';
        }

        // get_applicable_remises_for_restaurant() retourne une string directement
        if (is_string($remise_text)) {
            return $remise_text;
        }

        // Fallback sécuritaire : si jamais la méthode évolue et retourne un array
        if (function_exists('mrdstheme_get_restaurant_remise_text')) {
            return mrdstheme_get_restaurant_remise_text($restaurant_id, $date);
        }

        return '';
    }

    /**
     * Email au membre : Réservation en attente de confirmation
     */
    public function send_pending_email_to_member($reservation_id, $data)
    {
        $user = get_userdata($data['user_id']);
        $restaurant = get_post($data['restaurant_id']);
        if (!$user || !$restaurant) return;

        $to = !empty($data['email']) ? $data['email'] : $user->user_email;
        $subject = sprintf(__('Réservation en attente - %s', 'mrds-reservation'), $restaurant->post_title);

        MRDS_Resa_Email_Manager::get_instance()->send($to, $subject, 'pending-member', [
            'first_name'      => $user->first_name ?: $user->display_name,
            'restaurant_name' => $restaurant->post_title,
            'restaurant_address' => $this->get_restaurant_address_for_email($data['restaurant_id']),
            'remise'          => $this->get_restaurant_remise_for_email($data['restaurant_id'], $data['date']),
            'date_label'      => date_i18n('l j F Y', strtotime($data['date'])),
            'time'            => $data['time'],
            'guests'          => $data['guests'],
            'occasion'        => $data['occasion'] ?? '',
            'allergies'       => $data['allergies'] ?? '',
            'preferences'     => $data['preferences'] ?? '',
        ]);
    }


    /**
     * Email aux gestionnaires : Notification simple avec lien back-office
     */
    public function send_notification_email_to_restaurant($reservation_id, $data)
    {
        $restaurant = get_post($data['restaurant_id']);
        $user = get_userdata($data['user_id']);
        if (!$restaurant || !$user) return;

        $gestionnaires = get_field('restaurant_restaurateurs', $data['restaurant_id']);

        if (empty($gestionnaires) || !is_array($gestionnaires)) {
            $owner = get_field('restaurant_owner', $data['restaurant_id']);
            if ($owner && isset($owner->user_email)) {
                $gestionnaires = [$owner];
            } else {
                error_log('MRDS Reservation: Aucun gestionnaire trouvé pour le restaurant ' . $data['restaurant_id']);
                return;
            }
        }

        $edit_link = home_url('/gestion-reservations/');
        $subject = sprintf(__('Nouvelle demande de réservation - %s', 'mrds-reservation'), $restaurant->post_title);

        $vars = [
            'restaurant_name'    => $restaurant->post_title,
            'restaurant_address' => $this->get_restaurant_address_for_email($data['restaurant_id']),
            'remise'             => $this->get_restaurant_remise_for_email($data['restaurant_id'], $data['date']),
            'client_name'        => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'phone'              => $data['phone'] ?? '',
            'email'              => $data['email'] ?? ($user->user_email ?? ''),
            'date_label'         => date_i18n('l j F Y', strtotime($data['date'])),
            'time'               => $data['time'] ?? '',
            'guests'             => $data['guests'] ?? '',
            'occasion'           => $data['occasion'] ?? '',
            'allergies'          => $data['allergies'] ?? '',
            'preferences'        => $data['preferences'] ?? '',
            'edit_link'          => $edit_link,
        ];

        $email_manager = MRDS_Resa_Email_Manager::get_instance();

        foreach ($gestionnaires as $gestionnaire) {
            if (!isset($gestionnaire->user_email) || !is_email($gestionnaire->user_email)) continue;
            $email_manager->send($gestionnaire->user_email, $subject, 'notification-restaurant', $vars);
        }
    }


    // ========================================
    // HOOK : Changement de statut dans le back-office
    // ========================================

    /**
     * Détecter le changement de statut et envoyer l'email correspondant
     */
    public function on_status_change($meta_id, $post_id, $meta_key, $meta_value)
    {
        // Vérifier que c'est bien le champ statut
        if ($meta_key !== '_mrds_status') {
            return;
        }

        // Vérifier que c'est une réservation
        $post = get_post($post_id);
        if (!$post || $post->post_type !== MRDS_Resa_Post_Type::POST_TYPE) {
            return;
        }

        // Récupérer l'ancien statut (avant mise à jour)
        // Note: updated_post_meta est appelé APRÈS la mise à jour, donc on doit vérifier différemment
        // On utilise un transient pour éviter les doublons
        $transient_key = 'mrds_status_email_sent_' . $post_id;
        if (get_transient($transient_key)) {
            return; // Email déjà envoyé récemment
        }

        // Envoyer l'email selon le nouveau statut
        if ($meta_value === MRDS_Resa_Post_Type::STATUS_CONFIRMED) {
            $this->send_confirmed_email_to_member($post_id);
            set_transient($transient_key, true, 60); // Éviter doublons pendant 60 secondes

        } elseif ($meta_value === MRDS_Resa_Post_Type::STATUS_REFUSED) {
            $this->send_refused_email_to_member($post_id);
            set_transient($transient_key, true, 60);
        }
    }

    /**
     * Email au membre : Réservation confirmée
     */
    private function send_confirmed_email_to_member($reservation_id)
    {
        $user_id       = get_post_meta($reservation_id, '_mrds_user_id', true);
        $restaurant_id = get_post_meta($reservation_id, '_mrds_restaurant_id', true);
        $date          = get_post_meta($reservation_id, '_mrds_date', true);
        $time          = get_post_meta($reservation_id, '_mrds_time', true);
        $guests        = get_post_meta($reservation_id, '_mrds_guests', true);
        $email         = get_post_meta($reservation_id, '_mrds_email', true);
        $occasion      = get_post_meta($reservation_id, '_mrds_occasion', true);
        $allergies     = get_post_meta($reservation_id, '_mrds_allergies', true);
        $preferences   = get_post_meta($reservation_id, '_mrds_preferences', true);

        $user       = get_userdata($user_id);
        $restaurant = get_post($restaurant_id);
        if (!$user || !$restaurant) return;

        $to      = $email ?: $user->user_email;
        $subject = sprintf(__('Réservation confirmée - %s', 'mrds-reservation'), $restaurant->post_title);

        MRDS_Resa_Email_Manager::get_instance()->send($to, $subject, 'confirmed-member', [
            'first_name'         => $user->first_name ?: $user->display_name,
            'restaurant_name'    => $restaurant->post_title,
            'restaurant_address' => $this->get_restaurant_address_for_email($restaurant_id),
            'remise'             => $this->get_restaurant_remise_for_email($restaurant_id, $date),
            'date_label'         => date_i18n('l j F Y', strtotime($date)),
            'time'               => $time,
            'guests'             => $guests,
            'occasion'           => $occasion,
            'allergies'          => $allergies,
            'preferences'        => $preferences,
        ]);
    }

    /**
     * Email au membre : Réservation refusée
     */
    private function send_refused_email_to_member($reservation_id)
    {
        $user_id       = get_post_meta($reservation_id, '_mrds_user_id', true);
        $restaurant_id = get_post_meta($reservation_id, '_mrds_restaurant_id', true);
        $date          = get_post_meta($reservation_id, '_mrds_date', true);
        $time          = get_post_meta($reservation_id, '_mrds_time', true);
        $guests        = get_post_meta($reservation_id, '_mrds_guests', true);
        $email         = get_post_meta($reservation_id, '_mrds_email', true);
        $occasion      = get_post_meta($reservation_id, '_mrds_occasion', true);
        $allergies     = get_post_meta($reservation_id, '_mrds_allergies', true);
        $preferences   = get_post_meta($reservation_id, '_mrds_preferences', true);

        $user       = get_userdata($user_id);
        $restaurant = get_post($restaurant_id);
        if (!$user || !$restaurant) return;

        $to      = $email ?: $user->user_email;
        $subject = sprintf(__('Réservation non disponible - %s', 'mrds-reservation'), $restaurant->post_title);

        MRDS_Resa_Email_Manager::get_instance()->send($to, $subject, 'refused-member', [
            'first_name'         => $user->first_name ?: $user->display_name,
            'restaurant_name'    => $restaurant->post_title,
            'restaurant_address' => $this->get_restaurant_address_for_email($restaurant_id),
            'remise'             => $this->get_restaurant_remise_for_email($restaurant_id, $date),
            'date_label'         => date_i18n('l j F Y', strtotime($date)),
            'time'               => $time,
            'guests'             => $guests,
            'occasion'           => $occasion,
            'allergies'          => $allergies,
            'preferences'        => $preferences,
            'carnet_url'         => home_url('/le-carnet-dadresses/'),
        ]);
    }
}
