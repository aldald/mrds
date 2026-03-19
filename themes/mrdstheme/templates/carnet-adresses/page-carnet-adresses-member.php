<?php

/**
 * Template: Carnet d'adresses - Membre connecté
 * Fichier: templates/carnet-adresses/page-carnet-adresses-member.php
 */

mrdstheme_get_header();

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$prenom = $current_user->first_name ?: $current_user->display_name;

// ========================================
// COMPTEUR DE RESTAURANTS RESTANTS
// ========================================
$total_restaurants = wp_count_posts('restaurant')->publish;

// Compter les restaurants déjà réservés cette année
$reservations_cette_annee = new WP_Query([
    'post_type' => 'mrds_reservation',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'author' => $user_id,
    'meta_query' => [
        [
            'key' => '_mrds_date',
            'value' => date('Y') . '-01-01',
            'compare' => '>=',
            'type' => 'DATE'
        ]
    ],
    'fields' => 'ids'
]);

// Récupérer les IDs uniques des restaurants réservés
$restaurants_reserves = [];
if ($reservations_cette_annee->have_posts()) {
    foreach ($reservations_cette_annee->posts as $resa_id) {
        $resto_id = get_post_meta($resa_id, '_mrds_restaurant_id', true);
        if ($resto_id) {
            $restaurants_reserves[$resto_id] = true;
        }
    }
}
wp_reset_postdata();

$nb_restaurants_reserves = count($restaurants_reserves);
$nb_restaurants_restants = max(0, $total_restaurants - $nb_restaurants_reserves);

// ========================================
// PROCHAINES RÉSERVATIONS
// ========================================
$today = date('Y-m-d');
$prochaines_reservations = new WP_Query([
    'post_type' => 'mrds_reservation',
    'posts_per_page' => 5,
    'post_status' => 'publish',
    'author' => $user_id,
    'meta_key' => '_mrds_date',
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query' => [
        [
            'key' => '_mrds_date',
            'value' => $today,
            'compare' => '>=',
            'type' => 'DATE'
        ]
    ]
]);

// ========================================
// RÉSERVATIONS PASSÉES
// ========================================
$reservations_passees = new WP_Query([
    'post_type' => 'mrds_reservation',
    'posts_per_page' => 4,
    'post_status' => 'publish',
    'author' => $user_id,
    'meta_key' => '_mrds_date',
    'orderby' => 'meta_value',
    'order' => 'DESC',
    'meta_query' => [
        [
            'key' => '_mrds_date',
            'value' => $today,
            'compare' => '<',
            'type' => 'DATE'
        ]
    ]
]);

// ========================================
// FAVORIS (si vous avez implémenté le système)
// ========================================
$favoris_ids = mrds_get_user_favoris($user_id);

$favoris_query = null;
if (!empty($favoris_ids)) {
    $favoris_query = new WP_Query([
        'post_type' => 'restaurant',
        'posts_per_page' => 8,
        'post__in' => $favoris_ids,
        'post_status' => 'publish',
        'orderby' => 'post__in'
    ]);
}
?>

<!-- HERO MEMBRE -->
<section class="hero-membre">
    <div class="container">
        <div class="hero-membre-content text-center">
            <h2 class="section-title">Bonjour</h2>
            <div class="section-subtitle">
                <span class="dot"></span>
                <svg viewBox="0 0 120 35" class="curved-text">
                    <path id="curve" d="M 5,5 Q 60,35 115,5" fill="transparent" />
                    <text>
                        <textPath href="#curve" startOffset="50%" text-anchor="middle">
                            <?php echo esc_html($prenom); ?>
                        </textPath>
                    </text>
                </svg>
                <span class="dot"></span>
            </div>
            <p class="hero-membre-text">
                Il vous reste <strong><?php echo esc_html($nb_restaurants_restants); ?> restaurant<?php echo $nb_restaurants_restants > 1 ? 's' : ''; ?></strong> à découvrir cette année !
            </p>
            <?php
            echo do_shortcode(
                '[mrds_button class="my-btn-secondary" text="Trouver mon prochain restaurant" link="' . home_url('/#search-resto') . '" id="btnToggleSearch"]'
            );
            ?>
        </div>
    </div>
</section>

<!-- VOS PROCHAINES RÉSERVATIONS -->
<section class="section-reservations-prochaines">
    <div class="container">
        <h2 class="section-title-small">• Vos prochaines réservations •</h2>

        <div class="reservations-list">
            <?php if ($prochaines_reservations->have_posts()) : ?>
                <?php while ($prochaines_reservations->have_posts()) : $prochaines_reservations->the_post();
                    $resa_id = get_the_ID();
                    $restaurant_id = get_post_meta($resa_id, '_mrds_restaurant_id', true);
                    $restaurant = get_post($restaurant_id);
                    $date = get_post_meta($resa_id, '_mrds_date', true);
                    $time = get_post_meta($resa_id, '_mrds_time', true);
                    $guests = get_post_meta($resa_id, '_mrds_guests', true);

                    // Infos restaurant
                    $restaurant_image = get_the_post_thumbnail_url($restaurant_id, 'medium');
                    if (!$restaurant_image) {
                        $restaurant_image = get_template_directory_uri() . '/assets/images/placeholder-restaurant.png';
                    }

                    // Adresse / arrondissement
                    $adresse = get_field('adresse', $restaurant_id);
                    $arrondissement = $adresse['arrondissement'] ?? '';
                    $location = mrdstheme_build_address($adresse);

                    // Tags
                    $tags_terms = get_the_terms($restaurant_id, 'restaurant_tag');
                    $tags = [];
                    if ($tags_terms && !is_wp_error($tags_terms)) {
                        foreach ($tags_terms as $term) {
                            $tags[] = $term->name;
                        }
                    }

                    // Formater la date
                    $date_formatted = date_i18n('l j F Y', strtotime($date));
                    $time_formatted = $time;
                    $remise_text = mrdstheme_get_restaurant_remise_text($restaurant_id);
                ?>
                    <div class="restaurant-card-horizontal">
                        <div class="card-image">
                            <img src="<?php echo esc_url($restaurant_image); ?>" alt="<?php echo esc_attr($restaurant->post_title); ?>">
                            <?php if (!empty($remise_text)) : ?>
                                <span class="card-remise-badge"><?php echo esc_html($remise_text); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">
                                <a href="<?php echo get_permalink($restaurant_id); ?>"><?php echo esc_html($restaurant->post_title); ?></a>
                            </h3>
                            <p class="card-location"><?php echo esc_html($location); ?></p>

                            <?php if (!empty($tags)) : ?>
                                <div class="card-tags">
                                    <?php foreach (array_slice($tags, 0, 3) as $tag) : ?>
                                        <span class="tag"><?php echo esc_html($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="reservation-infos">
                                <span class="resa-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <?php echo esc_html($date_formatted); ?>
                                </span>
                                <span class="resa-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <?php echo esc_html($time_formatted); ?>
                                </span>
                                <span class="resa-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    <?php echo esc_html($guests); ?> couvert<?php echo $guests > 1 ? 's' : ''; ?>
                                </span>
                            </div>
                        </div>
                        <button class="card-favorite <?php echo mrds_is_favori($restaurant_id) ? 'active' : ''; ?>" data-restaurant-id="<?php echo $restaurant_id; ?>">
                            <span class="heart-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25.475" height="23.129" viewBox="0 0 25.475 23.129">
                                    <g id="likes" transform="translate(1 1)">
                                        <path id="Heart" d="M21.623,1.9a6.307,6.307,0,0,0-8.978,0l-.883.883L10.879,1.9A6.348,6.348,0,0,0,1.9,10.879l9.861,9.861,9.861-9.861a6.307,6.307,0,0,0,0-8.978" transform="translate(-0.025 -0.025)" fill="none" stroke="#da9d42" stroke-width="2" fill-rule="evenodd" />
                                    </g>
                                </svg>
                            </span>
                        </button>
                    </div>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div class="no-reservations">
                    <p>Vous n'avez pas encore de réservation à venir.</p>
                    <?php echo do_shortcode('[mrds_button class="my-btn-fourth" text="Découvrir nos restaurants" link="/le-carnet-dadresses/"]'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- MES FAVORIS -->
<section class="section-favoris">
    <div class="container-favoris">
        <div class="container">
            <div class="bg-smoke">
                <h2 class="section-title-small">Mes favoris</h2>

                <div class="resultats-grid">
                    <div class="row">
                        <?php if ($favoris_query && $favoris_query->have_posts()) : ?>
                            <?php while ($favoris_query->have_posts()) : $favoris_query->the_post();
                                $resto_id = get_the_ID();
                                $restaurant_image = get_the_post_thumbnail_url($resto_id, 'medium');
                                if (!$restaurant_image) {
                                    $restaurant_image = get_template_directory_uri() . '/assets/images/placeholder-restaurant.png';
                                }

                                $adresse = get_field('adresse', $resto_id);
                                $arrondissement = $adresse['arrondissement'] ?? '';
                                $location = mrdstheme_build_address($adresse);

                                $citation = get_field('citation_de_restaurant', $resto_id);
                                $quote = !empty(strip_tags($citation['citation'] ?? ''))
                                    ? $citation['citation'] : ($citation['description'] ?? '');
                                $chef = $citation['auteur'] ?? '';

                                $tags_terms = get_the_terms($resto_id, 'restaurant_tag');
                                $tags = [];
                                if ($tags_terms && !is_wp_error($tags_terms)) {
                                    foreach ($tags_terms as $term) {
                                        $tags[] = $term->name;
                                    }
                                }
                            ?>
                                <div class="col-12 col-md-6 col-lg-3 mb-4">
                                    <div class="restaurant-card">
                                        <div class="card-image">
                                            <img src="<?php echo esc_url($restaurant_image); ?>" alt="<?php the_title_attribute(); ?>">
                                            <button class="card-favorite active" data-restaurant-id="<?php echo $resto_id; ?>">
                                                <span class="heart-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="25.475" height="23.129" viewBox="0 0 25.475 23.129">
                                                        <g id="likes" transform="translate(1 1)">
                                                            <path id="Heart" d="M21.623,1.9a6.307,6.307,0,0,0-8.978,0l-.883.883L10.879,1.9A6.348,6.348,0,0,0,1.9,10.879l9.861,9.861,9.861-9.861a6.307,6.307,0,0,0,0-8.978" transform="translate(-0.025 -0.025)" fill="none" stroke="#da9d42" stroke-width="2" fill-rule="evenodd" />
                                                        </g>
                                                    </svg>
                                                </span>
                                            </button>
                                            <a href="<?php the_permalink(); ?>" class="card-arrow">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35">
                                                    <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" fill="#fff" />
                                                </svg>
                                            </a>
                                        </div>
                                        <div class="card-content">
                                            <h3 class="card-title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h3>
                                            <p class="card-location"><?php echo esc_html($location); ?></p>
                                            <?php if (!empty($tags)) : ?>
                                                <div class="card-tags">
                                                    <?php foreach (array_slice($tags, 0, 3) as $tag) : ?>
                                                        <span class="tag"><?php echo esc_html($tag); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($quote) : ?>
                                                <blockquote class="card-quote">
                                                    « <?php echo esc_html($quote); ?> »
                                                </blockquote>
                                                <?php if ($chef) : ?>
                                                    <p class="card-chef">— <?php echo esc_html($chef); ?></p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        <?php else : ?>
                            <div class="col-12">
                                <p class="no-favoris text-center">Vous n'avez pas encore de favoris. Explorez nos restaurants et ajoutez-les à vos favoris !</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="section-cta text-center">
                    <?php
                    echo do_shortcode('[mrds_button class="my-btn-fourth" text="Voir toutes nos adresses exclusives" link="/le-carnet-dadresses/"]');
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- VOS RÉSERVATIONS PASSÉES -->
<section class="section-reservations-passees">
    <div class="container">
        <h2 class="section-title-small">Vos réservations passées</h2>

        <div class="resultats-grid">
            <div class="row">
                <?php if ($reservations_passees->have_posts()) : ?>
                    <?php while ($reservations_passees->have_posts()) : $reservations_passees->the_post();
                        $resa_id = get_the_ID();
                        $restaurant_id = get_post_meta($resa_id, '_mrds_restaurant_id', true);
                        $restaurant = get_post($restaurant_id);

                        if (!$restaurant) continue;

                        $restaurant_image = get_the_post_thumbnail_url($restaurant_id, 'medium');
                        if (!$restaurant_image) {
                            $restaurant_image = get_template_directory_uri() . '/assets/images/placeholder-restaurant.png';
                        }

                        $adresse = get_field('adresse', $restaurant_id);
                        $arrondissement = $adresse['arrondissement'] ?? '';
                        $location = mrdstheme_build_address($adresse);

                        $citation = get_field('citation_de_restaurant', $restaurant_id);
                        $quote = !empty(strip_tags($citation['citation'] ?? ''))
                            ? $citation['citation'] : ($citation['description'] ?? '');
                        $chef = $citation['auteur'] ?? '';

                        $tags_terms = get_the_terms($restaurant_id, 'restaurant_tag');
                        $tags = [];
                        if ($tags_terms && !is_wp_error($tags_terms)) {
                            foreach ($tags_terms as $term) {
                                $tags[] = $term->name;
                            }
                        }
                    ?>
                        <div class="col-12 col-md-6 col-lg-3 mb-4">
                            <div class="restaurant-card">
                                <div class="card-image">
                                    <img src="<?php echo esc_url($restaurant_image); ?>" alt="<?php echo esc_attr($restaurant->post_title); ?>">
                                    <button class="card-favorite <?php echo in_array($restaurant_id, $favoris_ids) ? 'active' : ''; ?>" data-restaurant-id="<?php echo $restaurant_id; ?>">
                                        <span class="heart-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="25.475" height="23.129" viewBox="0 0 25.475 23.129">
                                                <g id="likes" transform="translate(1 1)">
                                                    <path id="Heart" d="M21.623,1.9a6.307,6.307,0,0,0-8.978,0l-.883.883L10.879,1.9A6.348,6.348,0,0,0,1.9,10.879l9.861,9.861,9.861-9.861a6.307,6.307,0,0,0,0-8.978" transform="translate(-0.025 -0.025)" fill="none" stroke="#da9d42" stroke-width="2" fill-rule="evenodd" />
                                                </g>
                                            </svg>
                                        </span>
                                    </button>
                                    <a href="<?php echo get_permalink($restaurant_id); ?>" class="card-arrow">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35">
                                            <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" fill="#fff" />
                                        </svg>
                                    </a>
                                </div>
                                <div class="card-content">
                                    <h3 class="card-title">
                                        <a href="<?php echo get_permalink($restaurant_id); ?>"><?php echo esc_html($restaurant->post_title); ?></a>
                                    </h3>
                                    <p class="card-location"><?php echo esc_html($location); ?></p>
                                    <?php if (!empty($tags)) : ?>
                                        <div class="card-tags">
                                            <?php foreach (array_slice($tags, 0, 3) as $tag) : ?>
                                                <span class="tag"><?php echo esc_html($tag); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($quote) : ?>
                                        <blockquote class="card-quote">
                                            « <?php echo esc_html($quote); ?> »
                                        </blockquote>
                                        <?php if ($chef) : ?>
                                            <p class="card-chef">— <?php echo esc_html($chef); ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <div class="col-12">
                        <p class="no-reservations text-center">Vous n'avez pas encore de réservation passée.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>