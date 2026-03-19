<?php

/**
 * Composant Carte MapLibre GL Centralisé
 * Style bleu foncé personnalisé comme la maquette
 * 
 * Utilisation :
 * 1. Shortcode : [mrds_map id="map-home" height="500px"]
 * 2. PHP : mrdstheme_render_map(['id' => 'map-home'])
 * 3. Helper : mrdstheme_map_home() ou mrdstheme_map_carnet()
 */

// ========================================
// ENQUEUE MAPLIBRE GL + SCRIPTS
// ========================================

function mrdstheme_enqueue_map_assets()
{
    // MapLibre GL CSS
    wp_enqueue_style(
        'maplibre-css',
        'https://unpkg.com/maplibre-gl@4.1.2/dist/maplibre-gl.css',
        [],
        '4.1.2'
    );

    // CSS personnalisé pour la carte
    wp_enqueue_style(
        'mrdstheme-map-css',
        get_template_directory_uri() . '/assets/css/map-leaflet.css',
        ['maplibre-css'],
        filemtime(get_template_directory() . '/assets/css/map-leaflet.css')
    );

    // MapLibre GL JS
    wp_enqueue_script(
        'maplibre-js',
        'https://unpkg.com/maplibre-gl@4.1.2/dist/maplibre-gl.js',
        [],
        '4.1.2',
        true
    );

    // Notre script de carte centralisé
    wp_enqueue_script(
        'mrdstheme-map-js',
        get_template_directory_uri() . '/assets/js/map-leaflet.js',
        ['maplibre-js'],
        filemtime(get_template_directory() . '/assets/js/map-leaflet.js'),
        true
    );

    // Passer les données à JavaScript
    wp_localize_script('mrdstheme-map-js', 'MRDS_Map_Config', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mrds_map_nonce'),
        'theme_url' => get_template_directory_uri(),
        'default_center' => [2.3522, 48.8566], // [lng, lat] pour MapLibre
        'default_zoom' => 14,
        'colors' => [
            'primary' => '#141B42',
            'accent' => '#DA9D42',
            'light' => '#FFFFFF',
        ]
    ]);
}
add_action('wp_enqueue_scripts', 'mrdstheme_enqueue_map_assets');


// ========================================
// SHORTCODE [mrds_map]
// ========================================

function mrdstheme_map_shortcode($atts)
{
    $atts = shortcode_atts([
        'id'            => 'mrds-map-' . uniqid(),
        'class'         => '',
        'height'        => '500px',
        'width'         => '100%',
        'zoom'          => 14,
        'center_lat'    => 48.8566,
        'center_lng'    => 2.3522,
        'show_controls' => 'false',
        'restaurants'   => 'all',
        'clickable'     => 'true',
    ], $atts, 'mrds_map');

    return mrdstheme_render_map($atts);
}
add_shortcode('mrds_map', 'mrdstheme_map_shortcode');


// ========================================
// FONCTION PHP RENDER MAP
// ========================================

function mrdstheme_render_map($args = [])
{
    $defaults = [
        'id'            => 'mrds-map-' . uniqid(),
        'class'         => '',
        'height'        => '500px',
        'width'         => '100%',
        'zoom'          => 14,
        'center_lat'    => 48.8566,
        'center_lng'    => 2.3522,
        'show_controls' => false,
        'restaurants'   => 'all',
        'clickable'     => true,
    ];

    $args = wp_parse_args($args, $defaults);

    $show_controls = filter_var($args['show_controls'], FILTER_VALIDATE_BOOLEAN);
    $clickable = filter_var($args['clickable'], FILTER_VALIDATE_BOOLEAN);

    // Récupérer les restaurants
    $restaurants_data = mrdstheme_get_restaurants_for_map($args['restaurants']);

    $classes = ['mrds-map-container'];
    if (!empty($args['class'])) {
        $classes[] = esc_attr($args['class']);
    }

    ob_start();
?>
    <div class="<?php echo implode(' ', $classes); ?>"
        style="height: <?php echo esc_attr($args['height']); ?>; width: <?php echo esc_attr($args['width']); ?>;">

        <div id="<?php echo esc_attr($args['id']); ?>"
            class="mrds-map"
            data-zoom="<?php echo esc_attr($args['zoom']); ?>"
            data-center-lat="<?php echo esc_attr($args['center_lat']); ?>"
            data-center-lng="<?php echo esc_attr($args['center_lng']); ?>"
            data-clickable="<?php echo $clickable ? 'true' : 'false'; ?>"
            data-restaurants='<?php echo json_encode($restaurants_data, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>


        </div>

    </div>
<?php
    return ob_get_clean();
}


// ========================================
// DONNÉES DES RESTAURANTS (CPT + ACF)
// ========================================

function mrdstheme_get_restaurants_for_map($filter = 'all')
{

    // Si 'none', retourner vide
    if ($filter === 'none') {
        return [];
    }

    // Arguments de la requête
    $args = [
        'post_type' => 'restaurant',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ];

    // Si filtre par IDs spécifiques (un ou plusieurs)
    if (is_string($filter) && $filter !== 'all' && $filter !== 'none') {
        $args['post__in'] = array_map('intval', explode(',', $filter));
    }

    $restaurants = [];
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            // Récupérer le groupe ACF "adresse"
            $adresse = get_field('adresse', $post_id);

            if (!$adresse) {
                continue;
            }

            // Construire l'adresse complète
            $adresse_complete = mrdstheme_build_address($adresse);

            // Obtenir les coordonnées (avec cache)
            $coords = mrdstheme_get_coordinates($post_id, $adresse_complete . ', France');

            if (!$coords) {
                continue; // Pas de coordonnées, on skip
            }

            // Récupérer les autres champs
            $restaurants[] = [
                'id'             => $post_id,
                'title'          => get_the_title(),
                'lat'            => $coords['lat'],
                'lng'            => $coords['lng'],
                'address'        => $adresse_complete,
                'arrondissement' => isset($adresse['arrondissement']) ? $adresse['arrondissement'] : '',
                'type'           => get_field('type_cuisine', $post_id) ?: '',
                'reduction'      => mrdstheme_get_restaurant_remise_text($post_id),
                'chef'           => get_field('nom_chef', $post_id) ?: '',
                'image'          => get_the_post_thumbnail_url($post_id, 'medium') ?: '',
                'link'           => get_permalink($post_id),
                'tags'           => mrdstheme_get_restaurant_tags($post_id),
                'cuisines'       => mrdstheme_get_restaurant_cuisines($post_id), // ← AJOUT
            ];
        }
        wp_reset_postdata();
    }

    return $restaurants;
}

/**
 * Construire l'adresse complète à partir des champs ACF
 */
function mrdstheme_build_address($adresse)
{
    $parts = [];

    if (!empty($adresse['adresse_rue'])) {
        $parts[] = $adresse['adresse_rue'];
    }

    if (!empty($adresse['code_postal'])) {
        $parts[] = $adresse['code_postal'];
    }

    if (!empty($adresse['ville'])) {
        $parts[] = $adresse['ville'];
    }

    // Ajouter France pour meilleur géocodage
    //$parts[] = 'France';

    return implode(', ', $parts);
}

/**
 * Obtenir les coordonnées GPS (avec cache)
 */
function mrdstheme_get_coordinates($post_id, $address)
{
    // Vérifier le cache
    $cached_lat = get_post_meta($post_id, '_mrds_latitude', true);
    $cached_lng = get_post_meta($post_id, '_mrds_longitude', true);
    $cached_address = get_post_meta($post_id, '_mrds_cached_address', true);

    // Si cache valide (même adresse), retourner les coordonnées
    if ($cached_lat && $cached_lng && $cached_address === $address) {
        return [
            'lat' => floatval($cached_lat),
            'lng' => floatval($cached_lng),
        ];
    }

    // Sinon, géocoder l'adresse
    $coords = mrdstheme_geocode_address($address);

    if ($coords) {
        // Sauvegarder en cache
        update_post_meta($post_id, '_mrds_latitude', $coords['lat']);
        update_post_meta($post_id, '_mrds_longitude', $coords['lng']);
        update_post_meta($post_id, '_mrds_cached_address', $address);

        return $coords;
    }

    return null;
}

/**
 * Géocoder une adresse avec OpenStreetMap Nominatim
 */
function mrdstheme_geocode_address($address)
{
    // Construire l'URL de l'API Nominatim
    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $address,
        'format' => 'json',
        'limit' => 1,
    ]);

    // Faire la requête (avec User-Agent requis par Nominatim)
    $response = wp_remote_get($url, [
        'headers' => [
            'User-Agent' => 'MesRondsDeServiette/1.0 (WordPress Theme)',
        ],
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        error_log('MRDS Geocoding error: ' . $response->get_error_message());
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        return [
            'lat' => floatval($data[0]['lat']),
            'lng' => floatval($data[0]['lon']),
        ];
    }

    error_log('MRDS Geocoding: No results for address: ' . $address);
    return null;
}

/**
 * Récupérer les tags/envies du restaurant
 */
function mrdstheme_get_restaurant_tags($post_id)
{
    $tags = [];
    $restaurant_tags = get_field('tags_restaurant', $post_id);

    if ($restaurant_tags && is_array($restaurant_tags)) {
        foreach ($restaurant_tags as $tag) {
            if (is_object($tag)) {
                $tags[] = $tag->name;
            } else {
                $term = get_term($tag, 'restaurant_tag');
                if ($term && !is_wp_error($term)) {
                    $tags[] = $term->name;
                }
            }
        }
    }

    return $tags;
}

function mrdstheme_get_restaurant_cuisines($post_id)
{
    $cuisines = [];
    $type_cuisine_tags = get_field('type_de_cuisine', $post_id);

    if (!empty($type_cuisine_tags)) {
        if (is_array($type_cuisine_tags)) {
            foreach ($type_cuisine_tags as $cuisine) {
                if (is_object($cuisine) && !empty($cuisine->name)) {
                    $cuisines[] = $cuisine->name;
                } else {
                    $term = get_term((int) $cuisine, 'type_cuisine');
                    if ($term && !is_wp_error($term)) {
                        $cuisines[] = $term->name;
                    }
                }
            }
        } else {
            if (is_object($type_cuisine_tags) && !empty($type_cuisine_tags->name)) {
                $cuisines[] = $type_cuisine_tags->name;
            } else {
                $term = get_term((int) $type_cuisine_tags, 'type_cuisine');
                if ($term && !is_wp_error($term)) {
                    $cuisines[] = $term->name;
                }
            }
        }
    }

    return array_values(array_unique(array_filter($cuisines)));
}


// ========================================
// ENDPOINT AJAX
// ========================================

function mrdstheme_ajax_get_restaurants()
{
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mrds_map_nonce')) {
        wp_send_json_error('Nonce invalide');
    }

    $filters = [
        'arrondissement' => sanitize_text_field($_POST['arrondissement'] ?? ''),
        'type' => sanitize_text_field($_POST['type'] ?? ''),
        'reduction' => sanitize_text_field($_POST['reduction'] ?? ''),
        'envie' => sanitize_text_field($_POST['envie'] ?? ''),
    ];

    // Récupérer tous les restaurants
    $restaurants = mrdstheme_get_restaurants_for_map('all');

    // Appliquer les filtres
    if (!empty($filters['arrondissement'])) {
        $restaurants = array_filter($restaurants, function ($r) use ($filters) {
            return stripos($r['arrondissement'], $filters['arrondissement']) !== false;
        });
    }

    if (!empty($filters['type'])) {
        $restaurants = array_filter($restaurants, function ($r) use ($filters) {
            return stripos($r['type'], $filters['type']) !== false;
        });
    }

    if (!empty($filters['reduction'])) {
        $restaurants = array_filter($restaurants, function ($r) use ($filters) {
            return $r['reduction'] === $filters['reduction'];
        });
    }

    if (!empty($filters['envie'])) {
        $restaurants = array_filter($restaurants, function ($r) use ($filters) {
            return in_array($filters['envie'], $r['tags']);
        });
    }

    wp_send_json_success(array_values($restaurants));
}
add_action('wp_ajax_mrds_get_restaurants', 'mrdstheme_ajax_get_restaurants');
add_action('wp_ajax_nopriv_mrds_get_restaurants', 'mrdstheme_ajax_get_restaurants');


// ========================================
// HELPERS RAPIDES
// ========================================

function mrdstheme_map_home()
{
    echo mrdstheme_render_map([
        'id' => 'map-home',
        'height' => '100%',
        'zoom' => 14,
        'show_controls' => false,
    ]);
}

function mrdstheme_map_carnet()
{
    echo mrdstheme_render_map([
        'id' => 'map-carnet',
        'height' => '500px',
        'zoom' => 14,
        'show_controls' => true,
    ]);
}
