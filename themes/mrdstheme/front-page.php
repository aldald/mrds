<?php

/**
 * Template Name: Page Accueil
 */
?>
<?php mrdstheme_get_header(); ?>

<?php
// ============================================
// HERO SECTION - Récupération des champs ACF
// ============================================
$hero_title = get_field('hero_title') ?: 'Jusqu\'à -20% sur une sélection de plus de 40 restaurants premium à Paris';
$hero_button_text = get_field('hero_button_text') ?: 'Rejoindre le club';
$hero_button_link = get_field('hero_button_link');
$hero_button_url = $hero_button_link ? $hero_button_link['url'] : '/';
$hero_button_target = ($hero_button_link && !empty($hero_button_link['target'])) ? $hero_button_link['target'] : '_self';

// Image de fond
$hero_bg_image = get_field('hero_background_image');
$hero_bg_url = $hero_bg_image ? $hero_bg_image['url'] : get_template_directory_uri() . '/assets/images/hero.jpg';
?>

<!-- HERO SECTION -->
<section class="hero-section" style="background-image: url('<?php echo esc_url($hero_bg_url); ?>');">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="hero-content text-center">
                    <h1 class="hero-title"><?php echo esc_html($hero_title); ?></h1>
                    <?php if (! is_user_logged_in()) : ?>
                        <?php
                        echo do_shortcode(sprintf(
                            '[mrds_button class="my-btn-primary" text="%s" link="%s" target="%s"]',
                            esc_attr($hero_button_text),
                            esc_url($hero_button_url),
                            esc_attr($hero_button_target)
                        ));
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// ============================================
// CTA SECTION - Récupération des champs ACF
// ============================================
$cta_button_text = get_field('cta_button_text') ?: 'Trouver mon prochain restaurant';
?>

<!-- CALL TO ACTION SECTION -->
<section class="cta-section" id="search-resto">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-auto">

                <?php
                echo do_shortcode(sprintf(
                    '[mrds_button class="my-btn-secondary" text="%s" link="#" id="btnToggleSearch"]',
                    esc_attr($cta_button_text)
                ));
                ?>

            </div>
        </div>

        <!-- Formulaire de recherche -->
        <div class="search-form" id="searchForm">
            <!-- Étape 1 : Envies -->
            <div class="search-step">
                <h3 class="search-step-title">1- De quoi avez-vous envie ?</h3>
                <div class="search-tags">
                    <?php
                    // Récupérer les termes de la taxonomie restaurant_tag
                    $tags_restaurant = get_terms([
                        'taxonomy' => 'restaurant_tag',
                        'hide_empty' => true,
                    ]);

                    if ($tags_restaurant && !is_wp_error($tags_restaurant)) :
                        foreach ($tags_restaurant as $tag) :
                    ?>
                            <button type="button" class="search-tag" data-filter="restaurant_tag" data-tax="restaurant_tag" data-value="<?php echo esc_attr($tag->term_id); ?>">
                                <?php echo esc_html($tag->name); ?>
                            </button>
                        <?php
                        endforeach;
                    endif;

                    // Récupérer les termes de la taxonomie type_cuisine
                    $types_cuisine = get_terms([
                        'taxonomy' => 'type_cuisine',
                        'hide_empty' => true,
                    ]);

                    if ($types_cuisine && !is_wp_error($types_cuisine)) :
                        foreach ($types_cuisine as $cuisine) :
                        ?>
                            <button type="button" class="search-tag" data-filter="type_cuisine" data-tax="type_cuisine" data-value="<?php echo esc_attr($cuisine->term_id); ?>">
                                <?php echo esc_html($cuisine->name); ?>
                            </button>
                    <?php
                        endforeach;
                    endif;

                    ?>
                </div>
            </div>

            <!-- Étape 2 : Arrondissements -->
            <div class="search-step">
                <h3 class="search-step-title">2- Dans quel arrondissement ?</h3>
                <div class="search-arrondissements">
                    <?php for ($i = 1; $i <= 20; $i++) : ?>
                        <button type="button" class="search-arrondissement" data-filter="arrondissement" data-value="<?php echo $i; ?>">
                            <span>Paris</span>
                            <span><?php echo $i; ?><?php echo ($i == 1) ? 'er' : 'e'; ?></span>
                        </button>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Bouton Rechercher -->
            <div class="search-submit">
                <button type="button" class="my-btn-primary" id="btnRechercher">
                    <span class="btn-diamond">◆</span>
                    Rechercher
                    <span class="btn-diamond">◆</span>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- SECTION RÉSULTATS -->
<section class="section-resultats" id="sectionResultats">
    <div class="container">
        <h2 class="resultats-title">Votre sélection</h2>

        <!-- Filtres actifs (CLIQUABLES) -->
        <div class="resultats-filters">

            <!-- Filtre Tags restaurant -->
            <div class="filter-row">
                <span class="filter-label">De quoi avez-vous envie ?</span>
                <div class="filter-values" id="filterTagsRestaurant">
                    <?php
                    $tags_restaurant = get_terms([
                        'taxonomy' => 'restaurant_tag',
                        'hide_empty' => true,
                    ]);

                    if ($tags_restaurant && !is_wp_error($tags_restaurant)) :
                        foreach ($tags_restaurant as $tag) :
                    ?>
                            <span class="filter-item" data-filter="restaurant_tag" data-tax="restaurant_tag" data-value="<?php echo esc_attr($tag->term_id); ?>">
                                <span class="filter-radio"></span>
                                <?php echo esc_html($tag->name); ?>
                            </span>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>
            <!-- Filtre Types de cuisine -->
            <div class="filter-row">
                <span class="filter-label">Types de cuisine</span>
                <div class="filter-values" id="filterTypesCuisine">
                    <?php
                    $types_cuisine = get_terms([
                        'taxonomy' => 'type_cuisine',
                        'hide_empty' => true,
                    ]);

                    if ($types_cuisine && !is_wp_error($types_cuisine)) :
                        foreach ($types_cuisine as $cuisine) :
                    ?>
                            <span class="filter-item" data-filter="type_cuisine" data-tax="type_cuisine" data-value="<?php echo esc_attr($cuisine->term_id); ?>">
                                <span class="filter-radio"></span>
                                <?php echo esc_html($cuisine->name); ?>
                            </span>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>


            <!-- Filtre Arrondissements -->
            <div class="filter-row">
                <span class="filter-label">Dans quel arrondissement ?</span>
                <div class="filter-values" id="filterArrondissements">
                    <?php for ($i = 1; $i <= 20; $i++) : ?>
                        <span class="filter-item" data-filter="arrondissement" data-value="<?php echo $i; ?>">
                            <span class="filter-radio"></span>
                            Paris <?php echo $i; ?><?php echo ($i == 1) ? 'er' : 'e'; ?>
                        </span>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Bouton Actualiser -->
            <div class="filter-row filter-actions">
                <button type="button" class="my-btn-primary" id="btnActualiser">
                    <span class="btn-diamond">◆</span>
                    Actualiser
                    <span class="btn-diamond">◆</span>
                </button>
            </div>

        </div>

        <!-- Grille des restaurants -->
        <div class="resultats-grid">
            <div class="row" id="resultatsGridContent">
                <!-- Résultats chargés via JS -->
            </div>
        </div>
    </div>
</section>

<?php
// ============================================
// SECTION AVANTAGES - Récupération des champs ACF
// ============================================
$avantages_title = get_field('avantages_title') ?: 'Les avantages';
$avantages_subtitle = get_field('avantages_subtitle') ?: 'DU CLUB';
$avantages_items = get_field('avantages_items');
?>

<!-- SECTION AVANTAGES -->
<section class="section-avantages">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html($avantages_title); ?></h2>
        <div class="section-subtitle">
            <span class="dot"></span>
            <svg viewBox="0 0 120 35" class="curved-text">
                <path id="curve" d="M 5,5 Q 60,35 115,5" fill="transparent" />
                <text>
                    <textPath href="#curve" startOffset="50%" text-anchor="middle">
                        <?php echo esc_html($avantages_subtitle); ?>
                    </textPath>
                </text>
            </svg>
            <span class="dot"></span>
        </div>

        <div class="row">
            <?php if ($avantages_items) : ?>
                <?php foreach ($avantages_items as $avantage) : ?>
                    <div class="col-12 col-md-4">
                        <div class="avantage-item">
                            <div class="avantage-icon">
                                <?php if (!empty($avantage['icon'])) : ?>
                                    <img src="<?php echo esc_url($avantage['icon']['url']); ?>" alt="<?php echo esc_attr($avantage['icon']['alt']); ?>">
                                <?php else : ?>
                                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/zone.png" alt="">
                                <?php endif; ?>
                            </div>
                            <p class="avantage-text"><?php echo esc_html($avantage['text']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <!-- Fallback si pas d'avantages configurés -->
                <?php for ($i = 0; $i < 3; $i++) : ?>
                    <div class="col-12 col-md-4">
                        <div class="avantage-item">
                            <div class="avantage-icon">
                                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/zone.png" alt="">
                            </div>
                            <p class="avantage-text">Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod</p>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// ============================================
// SECTION TROUVAILLES - Récupération des champs ACF
// ============================================
$trouvailles_title = get_field('trouvailles_title') ?: 'Nos dernières trouvailles';
$trouvailles_restaurants = get_field('trouvailles_restaurants');
$trouvailles_button_text = get_field('trouvailles_button_text') ?: 'Voir toutes nos adresses exclusives';
$trouvailles_button_link = get_field('trouvailles_button_link');
$trouvailles_button_url = $trouvailles_button_link ? $trouvailles_button_link['url'] : '/restaurants';
$trouvailles_button_target = ($trouvailles_button_link && !empty($trouvailles_button_link['target'])) ? $trouvailles_button_link['target'] : '_self';
?>

<!-- SECTION TROUVAILLES -->
<section class="section-trouvailles">
    <div class="container">
        <div class="bg-trouvailles">
            <h2 class="section-title"><?php echo esc_html($trouvailles_title); ?></h2>

            <div class="row">
                <?php if ($trouvailles_restaurants) : ?>
                    <?php foreach ($trouvailles_restaurants as $restaurant) : ?>
                        <?php
                        // Récupération des données du restaurant
                        $restaurant_id = $restaurant->ID;
                        $restaurant_title = get_the_title($restaurant_id);
                        $restaurant_link = get_permalink($restaurant_id);
                        $restaurant_image = get_the_post_thumbnail_url($restaurant_id, 'medium_large');

                        // ====================================
                        // Champs ACF RÉELS du restaurant
                        // ====================================

                        // ADRESSE - Group
                        $adresse = get_field('adresse', $restaurant_id);
                        $arrondissement = $adresse['arrondissement'] ?? '';
                        $ville = $adresse['ville'] ?? 'Paris';

                        // Construction de la location (Paris + arrondissement)
                        if ($arrondissement) {
                            $restaurant_location = 'Paris ' . $arrondissement . ($arrondissement == 1 ? 'er' : 'e');
                        } else {
                            $restaurant_location = $ville;
                        }

                        // CITATION - Group (nom correct: citation_de_restaurant)
                        $citation = get_field('citation_de_restaurant', $restaurant_id);
                        $restaurant_quote = $citation['description'] ?? '« Notre table est le reflet de notre engagement : sublimer chaque produit. »';
                        $restaurant_chef = $citation['auteur'] ?? 'Nom du chef';

                        // TAGS - Taxonomy
                        $restaurant_tags = get_field('tags_restaurant', $restaurant_id);
                        $type_cuisine_tags = get_field('type_de_cuisine', $restaurant_id);

                        // Fallback pour l'image
                        if (!$restaurant_image) {
                            $restaurant_image = site_url('/wp-content/uploads/woocommerce-placeholder.webp');;
                        }
                        ?>
                        <div class="col-12 col-md-6 col-lg-3 mb-4">
                            <div class="restaurant-card">
                                <div class="card-image">
                                    <img src="<?php echo esc_url($restaurant_image); ?>" alt="<?php echo esc_attr($restaurant_title); ?>">
                                    <button class="card-favorite" data-restaurant-id="<?php echo esc_attr($restaurant_id); ?>">
                                        <span class="heart-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="25.475" height="23.129" viewBox="0 0 25.475 23.129">
                                                <g id="likes" transform="translate(1 1)">
                                                    <g id="Groupe_15616" data-name="Groupe 15616">
                                                        <path id="Heart" d="M21.623,1.9a6.307,6.307,0,0,0-8.978,0l-.883.883L10.879,1.9A6.348,6.348,0,0,0,1.9,10.879l9.861,9.861,9.861-9.861a6.307,6.307,0,0,0,0-8.978" transform="translate(-0.025 -0.025)" fill="none" stroke="#da9d42" stroke-width="2" fill-rule="evenodd" />
                                                    </g>
                                                </g>
                                            </svg>
                                        </span>
                                    </button>
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

                                    <?php if ($restaurant_tags || $type_cuisine_tags) : ?>

                                        <div class="card-tags">
                                            <?php
                                            // 1) Tags restaurant (restaurant_tag)
                                            if (!empty($restaurant_tags) && is_array($restaurant_tags)) {
                                                foreach ($restaurant_tags as $tag_id) {
                                                    $tag_term = get_term((int) $tag_id, 'restaurant_tag');
                                                    if ($tag_term && !is_wp_error($tag_term)) {
                                                        echo '<span class="tag">' . esc_html($tag_term->name) . '</span>';
                                                    }
                                                }
                                            }

                                            // 2) Cuisines (type_cuisine via ACF field type_de_cuisine)
                                            if (!empty($type_cuisine_tags) && is_array($type_cuisine_tags)) {
                                                foreach ($type_cuisine_tags as $cuisine_id) {
                                                    // Comme c'est un Taxonomy field ACF, ça peut être un ID ou un objet WP_Term
                                                    if (is_object($cuisine_id) && isset($cuisine_id->term_id)) {
                                                        $cuisine_term = $cuisine_id;
                                                    } else {
                                                        $cuisine_term = get_term((int) $cuisine_id, 'type_cuisine');
                                                    }

                                                    if ($cuisine_term && !is_wp_error($cuisine_term)) {
                                                        echo '<span class="tag tag-type-cuisine">' . esc_html($cuisine_term->name) . '</span>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    <?php endif;  ?>

                                    <blockquote class="card-quote">
                                        <?php echo esc_html(mrds_limit_text($restaurant_quote, 120)); ?>
                                    </blockquote>
                                    <p class="card-chef">— <?php echo esc_html($restaurant_chef); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="col-12">
                        <p class="text-center">Aucun restaurant sélectionné.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="section-cta">
                        <?php
                        echo do_shortcode(sprintf(
                            '[mrds_button class="my-btn-fourth" text="%s" link="%s" target="%s"]',
                            esc_attr($trouvailles_button_text),
                            esc_url($trouvailles_button_url),
                            esc_attr($trouvailles_button_target)
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// ============================================
// SECTION FONCTIONNEMENT - Récupération des champs ACF
// ============================================
$fonctionnement_title = get_field('fonctionnement_title') ?: 'Le fonctionnement';
$fonctionnement_subtitle = get_field('fonctionnement_subtitle') ?: 'DU CLUB';
$fonctionnement_items = get_field('fonctionnement_items');
$fonctionnement_button_text = get_field('fonctionnement_button_text') ?: 'Rejoindre le club';
$fonctionnement_button_link = get_field('fonctionnement_button_link');
$fonctionnement_button_url = $fonctionnement_button_link ? $fonctionnement_button_link['url'] : '/';
$fonctionnement_button_target = ($fonctionnement_button_link && !empty($fonctionnement_button_link['target'])) ? $fonctionnement_button_link['target'] : '_self';
?>

<!-- SECTION FONCTIONNEMENT -->
<section class="section-fonctionnement">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html($fonctionnement_title); ?></h2>
        <div class="section-subtitle">
            <span class="dot"></span>
            <svg viewBox="0 0 120 35" class="curved-text">
                <path id="curve" d="M 5,5 Q 60,35 115,5" fill="transparent" />
                <text>
                    <textPath href="#curve" startOffset="50%" text-anchor="middle">
                        <?php echo esc_html($fonctionnement_subtitle); ?>
                    </textPath>
                </text>
            </svg>
            <span class="dot"></span>
        </div>

        <div class="row">
            <?php if ($fonctionnement_items) : ?>
                <?php $count = 1; ?>
                <?php foreach ($fonctionnement_items as $item) : ?>
                    <div class="col-12 col-md-4">
                        <div class="fonctionnement-card">
                            <span class="card-number"><?php echo $count; ?></span>
                            <h3 class="card-title">
                                    <?php echo esc_html($item['title']); ?>
                                </a>
                            </h3>
                            <p class="card-text"><?php echo esc_html($item['text']); ?></p>
                        </div>
                    </div>
                    <?php $count++; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="col-12">
                    <p class="text-center">Aucune étape configurée.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="section-cta">
                    <?php if (! is_user_logged_in()) : ?>
                        <?php
                        echo do_shortcode(sprintf(
                            '[mrds_button class="my-btn-primary" text="%s" link="%s" target="%s"]',
                            esc_attr($fonctionnement_button_text),
                            esc_url($fonctionnement_button_url),
                            esc_attr($fonctionnement_button_target)
                        ));
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// ============================================
// SECTION CHIFFRES - Récupération des champs ACF
// ============================================
$chiffres_items = get_field('chiffres_items');
?>

<!-- SECTION CHIFFRES -->
<section class="section-chiffres">
    <div class="container">
        <div class="chiffres-content">
            <div class="row">
                <?php if ($chiffres_items) : ?>
                    <?php foreach ($chiffres_items as $chiffre) : ?>
                        <div class="col-12 col-md-4">
                            <div class="chiffre-item">
                                <span class="chiffre-number"><?php echo esc_html($chiffre['number']); ?></span>
                                <p class="chiffre-text"><?php echo esc_html($chiffre['text']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="col-12">
                        <p class="text-center">Aucun chiffre configuré.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>



<?php
// ============================================
// SECTION CARTE - Récupération des champs ACF
// ============================================
$carte_restaurants = get_field('carte_restaurants');
$carte_restaurant_ids = '';

if ($carte_restaurants) {
    $ids = [];
    foreach ($carte_restaurants as $restaurant) {
        $ids[] = $restaurant->ID;
    }
    $carte_restaurant_ids = implode(',', $ids);
}
?>

<!-- SECTION CARTE -->
<section class="section-carte">
    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-5">
                <div class="carte-map">
                    <?php
                    if ($carte_restaurant_ids) {
                        echo mrdstheme_render_map([
                            'id' => 'map-home',
                            'height' => '100%',
                            'zoom' => 13,
                            'show_controls' => false,
                            'restaurants' => $carte_restaurant_ids,
                        ]);
                    } else {
                        mrdstheme_map_home();
                    }
                    ?>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="carte-restaurants">
                    <?php if ($carte_restaurants) : ?>
                        <?php foreach ($carte_restaurants as $restaurant) : ?>
                            <?php
                            $restaurant_id = $restaurant->ID;
                            $restaurant_title = get_the_title($restaurant_id);
                            $restaurant_link = get_permalink($restaurant_id);
                            $restaurant_image = get_the_post_thumbnail_url($restaurant_id, 'medium');

                            // ====================================
                            // Champs ACF RÉELS du restaurant
                            // ====================================

                            // ADRESSE - Group
                            $adresse = get_field('adresse', $restaurant_id);
                            $arrondissement = $adresse['arrondissement'] ?? '';
                            $ville = $adresse['ville'] ?? 'Paris';

                            // Construction de la location
                            if ($arrondissement) {
                                $restaurant_location = 'Paris ' . $arrondissement . ($arrondissement == 1 ? 'er' : 'e');
                            } else {
                                $restaurant_location = $ville;
                            }

                            // CITATION - Group
                            $citation = get_field('citation_de_restaurant', $restaurant_id);
                            $restaurant_quote = $citation['description'] ?? '« Notre table est le reflet de notre engagement : sublimer chaque produit, respecter la saison et éveiller vos sens. »';
                            $restaurant_chef = $citation['auteur'] ?? 'Nom du chef';

                            // TAGS - Taxonomy
                            $restaurant_tags = get_field('tags_restaurant', $restaurant_id);
                            $type_cuisine_tags = get_field('type_de_cuisine', $restaurant_id);


                            // Fallback pour l'image
                            if (!$restaurant_image) {
                                $restaurant_image = site_url('/wp-content/uploads/woocommerce-placeholder.webp');
                            }
                            ?>
                            <div class="restaurant-card-horizontal" data-restaurant-id="<?php echo esc_attr($restaurant_id); ?>">
                                <div class="card-image">
                                    <img src="<?php echo esc_url($restaurant_image); ?>" alt="<?php echo esc_attr($restaurant_title); ?>">
                                </div>
                                <div class="card-content">
                                    <h3 class="card-title">
                                        <a href="<?php echo esc_url($restaurant_link); ?>">
                                            <?php echo esc_html($restaurant_title); ?>
                                        </a>
                                    </h3>
                                    <p class="card-location"><?php echo esc_html($restaurant_location); ?></p>

                                    <?php if ($restaurant_tags || $type_cuisine_tags) : ?>

                                        <div class="card-tags">
                                            <?php
                                            // 1) Tags restaurant (restaurant_tag)
                                            if (!empty($restaurant_tags) && is_array($restaurant_tags)) {
                                                foreach ($restaurant_tags as $tag_id) {
                                                    $tag_term = get_term((int) $tag_id, 'restaurant_tag');
                                                    if ($tag_term && !is_wp_error($tag_term)) {
                                                        echo '<span class="tag">' . esc_html($tag_term->name) . '</span>';
                                                    }
                                                }
                                            }

                                            // 2) Cuisines (type_cuisine via ACF field type_de_cuisine)
                                            if (!empty($type_cuisine_tags) && is_array($type_cuisine_tags)) {
                                                foreach ($type_cuisine_tags as $cuisine_id) {
                                                    // Comme c'est un Taxonomy field ACF, ça peut être un ID ou un objet WP_Term
                                                    if (is_object($cuisine_id) && isset($cuisine_id->term_id)) {
                                                        $cuisine_term = $cuisine_id;
                                                    } else {
                                                        $cuisine_term = get_term((int) $cuisine_id, 'type_cuisine');
                                                    }

                                                    if ($cuisine_term && !is_wp_error($cuisine_term)) {
                                                        echo '<span class="tag tag-type-cuisine">' . esc_html($cuisine_term->name) . '</span>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    <?php endif;  ?>

                                    <blockquote class="card-quote">
                                        <?php echo esc_html(mrds_limit_text($restaurant_quote, 120)); ?>

                                    </blockquote>
                                    <p class="card-chef">— <?php echo esc_html($restaurant_chef); ?></p>
                                </div>
                                <a href="<?php echo esc_url($restaurant_link); ?>" class="card-arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35">
                                        <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" fill="#fff" />
                                    </svg>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="text-center">Aucun restaurant sélectionné.</p>
                    <?php endif; ?>
                </div>
                <a href="/le-carnet-dadresses/" class="btn-to-resto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                        <path d="M8,0,6.545,1.455l5.506,5.506H0V9.039H12.052L6.545,14.545,8,16l8-8Z" transform="translate(16 0) rotate(90)" fill="currentColor" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>