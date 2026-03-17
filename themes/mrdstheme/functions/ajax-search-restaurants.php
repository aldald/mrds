<?php

/**
 * AJAX Search Restaurants
 * 
 * @package mrdstheme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handler AJAX pour la recherche de restaurants
 */
function mrdstheme_ajax_search_restaurants()
{
    // Récupérer les filtres
    $envies_raw = isset($_POST['envies']) ? (array) $_POST['envies'] : [];

    $envies_restaurant_tag = [];
    $envies_type_cuisine = [];

    foreach ($envies_raw as $item) {
        $item = (string) $item;

        if (strpos($item, ':') === false) {
            continue;
        }

        [$tax, $id] = explode(':', $item, 2);
        $tax = trim($tax);
        $id  = (int) $id;

        if ($id <= 0) {
            continue;
        }

        if ($tax === 'restaurant_tag') {
            $envies_restaurant_tag[] = $id;
        } elseif ($tax === 'type_cuisine') {
            $envies_type_cuisine[] = $id;
        }
    }

    $envies_restaurant_tag = array_values(array_unique($envies_restaurant_tag));
    $envies_type_cuisine   = array_values(array_unique($envies_type_cuisine));
    $arrondissements = isset($_POST['arrondissements']) ? array_map('strval', $_POST['arrondissements']) : [];
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';




    // ========================================
    // ÉTAPE 2 : Construire la requête restaurants
    // ========================================
    $args = [
        'post_type' => 'restaurant',  // UNIQUEMENT les restaurants
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order'   => 'ASC'
    ];

    // Filtre par IDs de restaurants (si filtre remise actif)
    if (!empty($restaurant_ids_with_remise)) {
        $args['post__in'] = $restaurant_ids_with_remise;
    }

    // ========================================
    // ÉTAPE 3 : Exécuter la requête et filtrer en PHP
    // ========================================
    $query = new WP_Query($args);
    $restaurants = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $restaurant_id = get_the_ID();

            // DOUBLE VÉRIFICATION : S'assurer que c'est un restaurant
            if (get_post_type($restaurant_id) !== 'restaurant') {
                continue;
            }

            // Adresse (groupe ACF)
            $adresse = get_field('adresse', $restaurant_id);
            // FILTRE RECHERCHE LIBRE
            if (!empty($search)) {
                $title = strtolower(get_the_title($restaurant_id));
                $rue   = strtolower($adresse['adresse_rue'] ?? '');
                $ville = strtolower($adresse['ville'] ?? '');
                $term  = strtolower($search);

                if (
                    strpos($title, $term) === false &&
                    strpos($rue,   $term) === false &&
                    strpos($ville, $term) === false
                ) {
                    continue;
                }
            }
            $arrondissement = isset($adresse['arrondissement']) ? strval($adresse['arrondissement']) : '';

            // ========================================
            // FILTRE ARRONDISSEMENT
            // ========================================
            if (!empty($arrondissements)) {
                if (!in_array($arrondissement, $arrondissements)) {
                    continue; // Skip ce restaurant
                }
            }


            // ========================================
            // FILTRE TAGS (ENVIES) - Via champs ACF
            // restaurant_tag (tags_restaurant) OU type_cuisine (type_de_cuisine)
            // ========================================
            if (!empty($envies_restaurant_tag) || !empty($envies_type_cuisine)) {

                // A) restaurant_tag via ACF tags_restaurant
                $restaurant_tag_ids = [];
                $restaurant_tags = get_field('tags_restaurant', $restaurant_id);

                if (!empty($restaurant_tags)) {
                    if (is_array($restaurant_tags)) {
                        foreach ($restaurant_tags as $tag) {
                            $restaurant_tag_ids[] = is_object($tag) ? (int) $tag->term_id : (int) $tag;
                        }
                    } else {
                        $restaurant_tag_ids[] = is_object($restaurant_tags) ? (int) $restaurant_tags->term_id : (int) $restaurant_tags;
                    }
                }

                // -------- TAGS restaurant_tag --------
                $restaurant_tag_ids = [];
                $restaurant_tags = get_field('tags_restaurant', $restaurant_id);

                if (!empty($restaurant_tags)) {
                    if (is_array($restaurant_tags)) {
                        foreach ($restaurant_tags as $tag) {
                            $restaurant_tag_ids[] = is_object($tag) ? (int) $tag->term_id : (int) $tag;
                        }
                    } else {
                        $restaurant_tag_ids[] = is_object($restaurant_tags) ? (int) $restaurant_tags->term_id : (int) $restaurant_tags;
                    }
                }

                // -------- CUISINES type_cuisine --------
                $restaurant_cuisine_ids = [];
                $restaurant_cuisines = get_field('type_de_cuisine', $restaurant_id);

                if (!empty($restaurant_cuisines)) {
                    if (is_array($restaurant_cuisines)) {
                        foreach ($restaurant_cuisines as $cuisine) {
                            $restaurant_cuisine_ids[] = is_object($cuisine) ? (int) $cuisine->term_id : (int) $cuisine;
                        }
                    } else {
                        $restaurant_cuisine_ids[] = is_object($restaurant_cuisines) ? (int) $restaurant_cuisines->term_id : (int) $restaurant_cuisines;
                    }
                }

                // -------- MATCH LOGIC: AND entre groupes --------
                $require_tag     = !empty($envies_restaurant_tag);
                $require_cuisine = !empty($envies_type_cuisine);

                // Si un filtre n'est pas sélectionné, on le considère OK par défaut
                $match_restaurant_tag = !$require_tag;
                $match_type_cuisine   = !$require_cuisine;

                if ($require_tag) {
                    $match_restaurant_tag = false;
                    foreach ($envies_restaurant_tag as $envie_id) {
                        if (in_array($envie_id, $restaurant_tag_ids, true)) {
                            $match_restaurant_tag = true;
                            break;
                        }
                    }
                }

                if ($require_cuisine) {
                    $match_type_cuisine = false;
                    foreach ($envies_type_cuisine as $cuisine_id) {
                        if (in_array($cuisine_id, $restaurant_cuisine_ids, true)) {
                            $match_type_cuisine = true;
                            break;
                        }
                    }
                }

                // ✅ AND : si tags sélectionnés ET cuisines sélectionnées, il faut matcher les deux
                if (!$match_restaurant_tag || !$match_type_cuisine) {
                    continue; // Skip ce restaurant
                }
            }


            // ========================================
            // CONSTRUIRE LES DONNÉES DU RESTAURANT
            // ========================================

            // Image
            $image = get_the_post_thumbnail_url($restaurant_id, 'medium');
            if (!$image) {
                $image = site_url('/wp-content/uploads/woocommerce-placeholder.webp');
            }

            // Location
            $location = mrdstheme_build_address($adresse);

            // Citation
            $citation = get_field('citation_de_restaurant', $restaurant_id);
            $quote = mrds_limit_text($citation['description'] ?? '', 120);
            $chef = $citation['auteur'] ?? '';

            // Tags (noms pour affichage)
            $tags = [];
            $restaurant_tags = get_field('tags_restaurant', $restaurant_id);
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

            // Cuisines (noms pour affichage)
            $cuisines = [];
            $type_cuisine_tags = get_field('type_de_cuisine', $restaurant_id);

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

            $cuisines = array_values(array_unique(array_filter($cuisines)));


            // Coordonnées pour la carte
            $lat = get_post_meta($restaurant_id, '_mrds_latitude', true);
            $lng = get_post_meta($restaurant_id, '_mrds_longitude', true);



            $restaurants[] = [
                'id'             => $restaurant_id,
                'title'          => html_entity_decode(get_the_title($restaurant_id), ENT_QUOTES, 'UTF-8'),
                'link'           => get_permalink($restaurant_id),
                'image'          => $image,
                'location'       => $location,
                'arrondissement' => $arrondissement,
                'quote'          => $quote,
                'chef'           => $chef,
                'tags'           => $tags,
                'cuisines'       => $cuisines,
                'lat'            => $lat ? floatval($lat) : null,
                'lng'            => $lng ? floatval($lng) : null,
                'remise'         => mrdstheme_get_restaurant_remise_text($restaurant_id),
            ];
        }
    }
    wp_reset_postdata();

    $per_page    = 8;
    $paged       = max(1, intval($_POST['paged'] ?? 1));
    $total       = count($restaurants);
    $total_pages = $total > 0 ? (int) ceil($total / $per_page) : 0;

    // S'assurer que la page demandée ne dépasse pas le total
    $paged = min($paged, max(1, $total_pages));

    // Découper pour la page courante
    $offset      = ($paged - 1) * $per_page;
    $restaurants = array_slice($restaurants, $offset, $per_page);

    // ========================================
    // ÉTAPE 4 : Retourner les résultats
    // ========================================
    wp_send_json_success([
        'restaurants' => $restaurants,
        'count'       => $total,
        'total_pages' => $total_pages,
        'current_page' => $paged,
        'message'     => $total > 0 ? '' : 'Aucun restaurant trouvé avec ces critères.'
    ]);
}
add_action('wp_ajax_mrds_search_restaurants', 'mrdstheme_ajax_search_restaurants');
add_action('wp_ajax_nopriv_mrds_search_restaurants', 'mrdstheme_ajax_search_restaurants');


/**
 * Récupérer le texte de remise d'un restaurant
 */
function mrdstheme_get_restaurant_remise_text($restaurant_id)
{
    $remises_liees = get_field('remises_liees', $restaurant_id);
    if (empty($remises_liees) || !is_array($remises_liees)) {
        return '';
    }

    $best_remise    = null;
    $best_valeur    = -1;

    foreach ($remises_liees as $remise) {
        $remise_id = is_object($remise) ? $remise->ID : (int) $remise;

        // Ne garder que les remises actives
        if (!(bool) get_field('remise_active', $remise_id)) {
            continue;
        }

        $valeur = (float) get_field('valeur_de_la_remise', $remise_id);

        if ($valeur > $best_valeur) {
            $best_valeur  = $valeur;
            $best_remise  = $remise_id;
        }
    }

    if (!$best_remise) {
        return '';
    }

    $type_remise = get_field('type_de_remise', $best_remise);
    $valeur      = get_field('valeur_de_la_remise', $best_remise);

    if (!$type_remise || $valeur === '') {
        return '';
    }

    // IDs taxonomy type_remise : 21 = pourcentage, 23 = montant fixe
    if ((int) $type_remise === 21) {
        return '-' . $valeur . '%';
    } elseif ((int) $type_remise === 23) {
        return '-' . $valeur . '€';
    }

    $term = get_term((int) $type_remise, 'type_remise');
    return ($term && !is_wp_error($term)) ? $term->name : '';
}
