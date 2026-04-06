<?php

/**
 * Synchronisation Taxonomies natives WP → Champs ACF hidden
 *
 * Prérequis : dans group_693144b5724ac.json, les deux champs taxonomy
 * doivent avoir "save_terms": 0 pour qu'ACF ne réécrase PAS
 * ce que la metabox native vient de sauvegarder.
 *
 * Priority 99 : on s'assure de fire après ACF (qui hook à priority 10)
 * afin que get_the_terms() retourne bien les nouvelles valeurs.
 */
function mrdstheme_sync_taxonomy_to_acf($post_id)
{
    // Uniquement pour le CPT restaurant
    if (get_post_type($post_id) !== 'restaurant') {
        return;
    }

    // Ignorer autosave et révisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Map taxonomy → clé postmeta ACF
    $map = [
        'restaurant_tag' => 'tags_restaurant',
        'type_cuisine'   => 'type_de_cuisine',
    ];

    foreach ($map as $taxonomy => $meta_key) {
        $terms = get_the_terms($post_id, $taxonomy);

        if (!empty($terms) && !is_wp_error($terms)) {
            $term_ids = wp_list_pluck($terms, 'term_id');
        } else {
            $term_ids = [];
        }

        // update_post_meta directement (pas update_field) pour éviter
        // tout re-trigger ACF sur wp_set_object_terms
        update_post_meta($post_id, $meta_key, $term_ids);
    }
}
// Priority 99 : fire bien après ACF (priority 10) pour lire les bonnes valeurs
add_action('save_post', 'mrdstheme_sync_taxonomy_to_acf', 99);