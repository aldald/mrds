<?php
/**
 * Breadcrumb / Fil d'Ariane
 * 
 * Fonction pour afficher le fil d'Ariane
 * 
 * Utilisation : mrdstheme_breadcrumb();
 */

function mrdstheme_breadcrumb() {
    // Séparateur
    $separator = '<span class="breadcrumb-separator">›</span>';
    
    // Début du fil d'Ariane
    echo '<nav class="breadcrumb-nav" aria-label="Fil d\'Ariane">';
    echo '<div class="container">';
    echo '<ul class="breadcrumb-list">';
    
    // Single Post (CPT ou Post classique)
    if (is_singular()) {
        $post_type = get_post_type();
        
        // Articles de blog (post type par défaut)
        if ($post_type === 'post') {
            echo '<li class="breadcrumb-item">';
            echo '<a href="' . get_permalink(get_option('page_for_posts')) . '">Blog</a>';
            echo '</li>';
            echo '<li class="breadcrumb-separator">' . $separator . '</li>';
            echo '<li class="breadcrumb-item current">';
            echo '<span>' . get_the_title() . '</span>';
            echo '</li>';
        }
        // Pages
        elseif ($post_type === 'page') {
            // Si la page a un parent
            $parent_id = wp_get_post_parent_id(get_the_ID());
            if ($parent_id) {
                $parents = [];
                while ($parent_id) {
                    $parents[] = $parent_id;
                    $parent_id = wp_get_post_parent_id($parent_id);
                }
                $parents = array_reverse($parents);
                
                foreach ($parents as $parent) {
                    echo '<li class="breadcrumb-item">';
                    echo '<a href="' . get_permalink($parent) . '">' . get_the_title($parent) . '</a>';
                    echo '</li>';
                    echo '<li class="breadcrumb-separator">' . $separator . '</li>';
                }
            }
            
            echo '<li class="breadcrumb-item current">';
            echo '<span>' . get_the_title() . '</span>';
            echo '</li>';
        }
        // Custom Post Types (restaurant, etc.)
        else {
            $post_type_object = get_post_type_object($post_type);
            
            if ($post_type_object) {
                // Récupérer le label du CPT (ex: "Restaurants")
                $cpt_label = $post_type_object->labels->name;
                
                // URL de l'archive du CPT
                $cpt_archive_link = get_post_type_archive_link($post_type);
                
                // Si pas d'archive, créer un lien manuel pour certains CPT
                if (!$cpt_archive_link) {
                    // Lien manuel pour le CPT restaurant
                    if ($post_type === 'restaurant') {
                        $cpt_archive_link = home_url('/le-carnet-dadresses/');
                    }
                }
                
                // Afficher le parent CPT (avec lien si disponible)
                echo '<li class="breadcrumb-item">';
                if ($cpt_archive_link) {
                    echo '<a href="' . esc_url($cpt_archive_link) . '">' . esc_html($cpt_label) . '</a>';
                } else {
                    echo '<span>' . esc_html($cpt_label) . '</span>';
                }
                echo '</li>';
                echo '<li class="breadcrumb-separator">' . $separator . '</li>';
                
                // Titre du post actuel
                echo '<li class="breadcrumb-item current">';
                echo '<span>' . get_the_title() . '</span>';
                echo '</li>';
            }
        }
    }
    // Archives
    elseif (is_archive()) {
        echo '<li class="breadcrumb-item current">';
        echo '<span>' . get_the_archive_title() . '</span>';
        echo '</li>';
    }
    // Recherche
    elseif (is_search()) {
        echo '<li class="breadcrumb-item current">';
        echo '<span>Recherche : ' . get_search_query() . '</span>';
        echo '</li>';
    }
    // 404
    elseif (is_404()) {
        echo '<li class="breadcrumb-item current">';
        echo '<span>Page non trouvée</span>';
        echo '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
    echo '</nav>';
}

/**
 * Fonction simplifiée pour le fil d'Ariane statique
 * 
 * @param string $parent_label - Label du lien parent
 * @param string $parent_link - URL du lien parent
 * @param string $current_label - Label de la page courante (optionnel, utilise le titre si vide)
 */
function mrdstheme_breadcrumb_simple($parent_label = '', $parent_link = '', $current_label = '') {
    $separator = '<span class="breadcrumb-separator">›</span>';
    
    // Si pas de label courant, utiliser le titre de la page
    if (empty($current_label)) {
        $current_label = get_the_title();
    }
    
    echo '<nav class="breadcrumb-nav" aria-label="Fil d\'Ariane">';
    echo '<div class="container">';
    echo '<ul class="breadcrumb-list">';
    
    // Lien parent
    if (!empty($parent_label) && !empty($parent_link)) {
        echo '<li class="breadcrumb-item">';
        echo '<a href="' . esc_url($parent_link) . '">' . esc_html($parent_label) . '</a>';
        echo '</li>';
        echo '<li class="breadcrumb-separator">' . $separator . '</li>';
    }
    
    // Page courante
    echo '<li class="breadcrumb-item current">';
    echo '<span>' . esc_html($current_label) . '</span>';
    echo '</li>';
    
    echo '</ul>';
    echo '</div>';
    echo '</nav>';
}