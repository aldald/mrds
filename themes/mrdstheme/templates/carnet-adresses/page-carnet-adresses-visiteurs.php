<?php

/**
 * Template Name: Page Carnet d'adresses visiteurs
 */
?>
<?php mrdstheme_get_header(); ?>

<section class="section-carnet">
    <div class="container">
        <!-- Titre -->
        <h1 class="carnet-title">Le carnet d'adresses</h1>

        <!-- Filtres dynamiques -->
        <div class="carnet-filters">
            <!-- Recherche libre -->
            <div class="filter-search">
                <input type="text" id="filterSearch" class="libre-search" placeholder="Rechercher un restaurant" autocomplete="off">
            </div>
            <!-- Filtre Tags restaurant -->
            <div class="filter-dropdown" data-filter="restaurant_tag">
                <button class="filter-btn" type="button">
                    <span>De quoi avez-vous envie ?</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6">
                        <path d="M5 6L0 0h10z" fill="#9e744d" />
                    </svg>
                </button>

                <div class="filter-menu">
                    <a href="#" data-tax="" data-value="">Tous</a>

                    <?php
                    $tags_restaurant = get_terms([
                        'taxonomy' => 'restaurant_tag',
                        'hide_empty' => true,
                    ]);

                    if ($tags_restaurant && !is_wp_error($tags_restaurant)) :
                        foreach ($tags_restaurant as $tag) :
                    ?>
                            <a href="#" data-tax="restaurant_tag" data-value="<?php echo esc_attr($tag->term_id); ?>">
                                <?php echo esc_html($tag->name); ?>
                            </a>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>

            <!-- Filtre Types de cuisine -->
            <div class="filter-dropdown" data-filter="type_cuisine">
                <button class="filter-btn" type="button">
                    <span>Types de cuisine</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6">
                        <path d="M5 6L0 0h10z" fill="#9e744d" />
                    </svg>
                </button>

                <div class="filter-menu">
                    <a href="#" data-tax="" data-value="">Tous</a>

                    <?php
                    $types_cuisine = get_terms([
                        'taxonomy' => 'type_cuisine',
                        'hide_empty' => true,
                    ]);

                    if ($types_cuisine && !is_wp_error($types_cuisine)) :
                        foreach ($types_cuisine as $cuisine) :
                    ?>
                            <a href="#" data-tax="type_cuisine" data-value="<?php echo esc_attr($cuisine->term_id); ?>">
                                <?php echo esc_html($cuisine->name); ?>
                            </a>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>


            <!-- Filtre Arrondissement -->
            <div class="filter-dropdown" data-filter="arrondissement">
                <button class="filter-btn" type="button">
                    <span>Dans quel arrondissement ?</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6">
                        <path d="M5 6L0 0h10z" fill="#9e744d" />
                    </svg>
                </button>
                <div class="filter-menu">
                    <a href="#" data-value="">Tous</a>
                    <?php for ($i = 1; $i <= 20; $i++) : ?>
                        <a href="#" data-value="<?php echo $i; ?>">Paris <?php echo $i; ?><?php echo ($i == 1) ? 'er' : 'e'; ?></a>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Bouton Reset -->
            <a href="#" class="filter-reset" id="resetFilters">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                    <path d="M3 3v5h5" />
                </svg>
                Réinitialiser
            </a>

        </div>

        <!-- Carte -->
        <div class="carnet-map">
            <?php mrdstheme_map_carnet(); ?>
        </div>

        <!-- Grille de restaurants -->
        <div class="carnet-grid" id="carnetGrid">
            <?php
            // Pagination
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

            // Query des restaurants
            $args = array(
                'post_type' => 'restaurant',
                'posts_per_page' => 8,
                'paged' => $paged,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order'   => 'ASC'
            );

            $restaurants_query = new WP_Query($args);

            if ($restaurants_query->have_posts()) :
                while ($restaurants_query->have_posts()) : $restaurants_query->the_post();
                    $restaurant_id = get_the_ID();
                    $restaurant_title = get_the_title();
                    $restaurant_link = get_permalink();
                    $restaurant_image = get_the_post_thumbnail_url($restaurant_id, 'medium_large');

                    // Champs ACF
                    $adresse = get_field('adresse', $restaurant_id);
                    $arrondissement = $adresse['arrondissement'] ?? '';
                    $ville = $adresse['ville'] ?? 'Paris';

                    // Construction de la location
                    if ($arrondissement) {
                        $restaurant_location = 'Paris ' . $arrondissement . ($arrondissement == 1 ? 'er' : 'e');
                    } else {
                        $restaurant_location = $ville;
                    }

                    // Citation
                    $citation = get_field('citation_de_restaurant', $restaurant_id);
                    $restaurant_quote = $citation['description'] ?? '';
                    $restaurant_chef = $citation['auteur'] ?? '';
                    $remise_text = mrdstheme_get_restaurant_remise_text($restaurant_id);

                    // Tags
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

                    // Cuisines
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



                    // Fallback image
                    if (!$restaurant_image) {
                        $restaurant_image = site_url('/wp-content/uploads/woocommerce-placeholder.webp');
                    }
            ?>
                    <div class="restaurant-card" data-restaurant-id="<?php echo $restaurant_id; ?>">
                        <div class="card-image">
                            <img src="<?php echo esc_url($restaurant_image); ?>" alt="<?php echo esc_attr($restaurant_title); ?>">
                            <?php if (!empty($remise_text)) : ?>
                                <span class="card-remise-badge"><?php echo esc_html($remise_text); ?></span>
                            <?php endif; ?>
                            <a href="<?php echo esc_url($restaurant_link); ?>" class="card-arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35">
                                    <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" fill="#fff" />
                                </svg>
                            </a>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">
                                <a href="<?php echo esc_url($restaurant_link); ?>">
                                    <?php echo esc_html($restaurant_title); ?>
                                </a>
                            </h3>
                            <p class="card-location"><?php echo esc_html($restaurant_location); ?></p>
                            <?php if (!empty($tags) || !empty($cuisines)) : ?>
                                <div class="card-tags">
                                    <?php foreach (array_slice($tags, 0, 3) as $tag) : ?>
                                        <span class="tag"><?php echo esc_html($tag); ?></span>
                                    <?php endforeach; ?>

                                    <?php foreach (array_slice($cuisines, 0, 3) as $cuisine) : ?>
                                        <span class="tag tag-type-cuisine"><?php echo esc_html($cuisine); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($restaurant_quote) : ?>
                                <blockquote class="card-quote">« <?php echo esc_html(mrds_limit_text($restaurant_quote, 120)); ?> »</blockquote>
                            <?php endif; ?>
                            <?php if ($restaurant_chef) : ?>
                                <p class="card-chef">— <?php echo esc_html($restaurant_chef); ?></p>
                            <?php endif; ?>
                           
                        </div>
                    </div>
                <?php
                endwhile;
            else :
                ?>
                <div class="col-12">
                    <p class="text-center">Aucun restaurant trouvé.</p>
                </div>
            <?php
            endif;
            ?>
        </div>

        <!-- Pagination -->
        <?php if ($restaurants_query->max_num_pages > 1) : ?>
            <div class="carnet-pagination" id="carnetPagination">
                <?php
                $total_pages = $restaurants_query->max_num_pages;
                for ($i = 1; $i <= $total_pages; $i++) :
                    $active_class = ($i == $paged) ? 'active' : '';
                ?>
                    <a href="#" class="pagination-dot <?php echo $active_class; ?>" data-page="<?php echo $i; ?>" aria-label="Page <?php echo $i; ?>"></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>
    </div>
</section>

<?php get_footer(); ?>