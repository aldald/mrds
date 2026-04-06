<?php

/**
 * Template pour afficher un restaurant individuel
 * 
 * @package mrdstheme
 */

mrdstheme_get_header();

// Récupération de l'ID du restaurant
$restaurant_id = get_the_ID();

// ====================================
// CHAMPS ACF DU RESTAURANT
// ====================================

// Adresse (group)
$adresse = get_field('adresse', $restaurant_id);
$adresse_rue = $adresse['adresse_rue'] ?? '';
$complement_adresse = $adresse['complement_d\'adresse'] ?? '';
$code_postal = $adresse['code_postal'] ?? '';
$ville = $adresse['ville'] ?? 'Paris';
$arrondissement = $adresse['arrondissement'] ?? '';



// ============================================
// STRINGS CONFIGURABLES — Options Page ACF
// ============================================
$str_offer_label          = get_field('single_resto_offer_label',          'option') ?: "L'offre exclusive";
$str_offer_title          = get_field('single_resto_offer_title',          'option') ?: 'Nos offres exclusives';
$str_validite_label       = get_field('single_resto_validite_label',       'option') ?: 'Validité';
$str_validite_jours_label = get_field('single_resto_validite_jours_label', 'option') ?: 'Validité jours/heure';
$str_btn_reserver         = get_field('single_resto_btn_reserver',         'option') ?: 'Réserver';
$str_site_web_label       = get_field('single_resto_site_web_label',       'option') ?: 'Site web';
$str_menu_label           = get_field('single_resto_menu_label',           'option') ?: 'Menu';
$str_exemples_label       = get_field('single_resto_exemples_label',       'option') ?: 'Exemples de plats';
$str_btn_rejoindre      = get_field('single_resto_btn_rejoindre',      'option') ?: 'Rejoindre le club';
$str_btn_rejoindre_link = get_field('single_resto_btn_rejoindre_link', 'option') ?: '/nous-rejoindre/';

// ── Toutes les remises actives du restaurant ──
// Récupérer les remises liées au restaurant via le champ ACF remises_liees
// return_format: object → on reçoit directement des WP_Post objects
$remises_liees_raw = get_field('remises_liees', $restaurant_id);
$remises_liees_raw = is_array($remises_liees_raw) ? $remises_liees_raw : [];

$remises_actives = array_filter($remises_liees_raw, function ($remise) {
    return isset($remise->ID) && (bool) get_field('remise_active', $remise->ID);
});

// Construction de l'adresse complète
$adresse_complete = $adresse_rue;
if ($complement_adresse) {
    $adresse_complete .= ', ' . $complement_adresse;
}
if ($code_postal) {
    $adresse_complete .= ', ' . $code_postal;
}
if ($ville) {
    $adresse_complete .= ' ' . $ville;
}
// if ($arrondissement) {
//     $adresse_complete .= ' - Paris ' . $arrondissement . ($arrondissement == 1 ? 'er' : 'e');
// }

// Contact
$telephone = get_field('telephone', $restaurant_id);
$site_web = get_field('site_web', $restaurant_id);

// Tags
$tags_restaurant = get_field('tags_restaurant', $restaurant_id);
$type_cuisine_tags = get_field('type_de_cuisine', $restaurant_id);

// Citation
$citation = get_field('citation_de_restaurant', $restaurant_id);
$citation_texte = !empty(strip_tags($citation['citation'] ?? '')) ? $citation['citation'] : ($citation['description'] ?? '');
$citation_auteur = $citation['auteur'] ?? 'Nom du chef';

// Description menu
$description_menu = get_field('description_menu', $restaurant_id);

// Exemples de plats
$exemple_plats = get_field('exemple_de_plats', $restaurant_id);

// Tarifs
$tarifs = get_field('tarifs', $restaurant_id);

// Horaires (repeater)
$horaires = get_field('horaires', $restaurant_id);

// Images
$featured_image = get_the_post_thumbnail_url($restaurant_id, 'large');
$gallery_images = get_field('gallery', $restaurant_id); // Si tu as un champ galerie ACF

// Fallback image
if (!$featured_image) {
    $featured_image = content_url('/uploads/woocommerce-placeholder.webp');
}

?>

<!-- Fil d'Ariane -->
<?php mrdstheme_breadcrumb(); ?>

<!-- Galerie Photos -->
<section class="restaurant-gallery">
    <div class="container">
        <div class="gallery-grid">
            <?php
            // Récupération de la galerie ACF
            $gallery_images = get_field('gallerie', $restaurant_id);

            if ($gallery_images && is_array($gallery_images)) :
                // Limiter à 3 images maximum pour la grille
                $gallery_count = min(count($gallery_images), 4);

                for ($i = 0; $i < $gallery_count; $i++) :
                    $image = $gallery_images[$i];
                    $image_url = $image['url'];
                    $image_alt = $image['alt'] ?: get_the_title();
                    $main_class = ($i === 0) ? ' gallery-main' : '';
            ?>
                    <div class="gallery-item<?php echo $main_class; ?>">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>">
                    </div>
                <?php
                endfor;
            else :
                // Fallback : utiliser la featured image si pas de galerie
                $featured_image = get_the_post_thumbnail_url($restaurant_id, 'large');
                if (!$featured_image) {
                    $featured_image = content_url('/uploads/woocommerce-placeholder.webp');
                }
                ?>
                <div class="gallery-item gallery-main">
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                </div>
                <div class="gallery-item">
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                </div>
                <div class="gallery-item">
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                </div>

                <div class="gallery-item">
                    <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Contenu Principal -->
<section class="restaurant-content">
    <div class="container">
        <div class="row">
            <!-- Colonne principale -->
            <div class="col-lg-8">
                <div class="restaurant-main">

                    <!-- En-tête du restaurant -->
                    <header class="restaurant-header">
                        <h1 class="restaurant-title"><?php the_title(); ?></h1>

                        <div class="restaurant-meta">
                            <?php if ($adresse_complete) : ?>
                                <p class="restaurant-address">
                                    <span class="meta-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                    </span>
                                    <?php echo esc_html($adresse_complete); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($telephone) : ?>
                                <p class="restaurant-phone">
                                    <span class="meta-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                        </svg>
                                    </span>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $telephone)); ?>"><?php echo esc_html($telephone); ?></a>
                                </p>
                            <?php endif; ?>

                            <?php if ($site_web) : ?>
                                <p class="restaurant-website">
                                    <span class="meta-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="2" y1="12" x2="22" y2="12"></line>
                                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                        </svg>
                                    </span>
                                    <a href="<?php echo esc_url($site_web); ?>" target="_blank" rel="noopener"><?php echo esc_html($str_site_web_label); ?></a>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="restaurant-divider"></div>

                        <!-- Tags -->
                        <?php if ($tags_restaurant || $type_cuisine_tags) : ?>
                            <div class="restaurant-tags">
                                <?php
                                // 1) restaurant_tag (ACF tags_restaurant)
                                if (!empty($tags_restaurant)) {
                                    if (is_array($tags_restaurant)) {
                                        foreach ($tags_restaurant as $tag) {
                                            $tag_term = is_object($tag) ? $tag : get_term((int) $tag, 'restaurant_tag');
                                            if ($tag_term && !is_wp_error($tag_term)) {
                                                echo '<span class="tag">' . esc_html($tag_term->name) . '</span>';
                                            }
                                        }
                                    } else {
                                        $tag_term = is_object($tags_restaurant) ? $tags_restaurant : get_term((int) $tags_restaurant, 'restaurant_tag');
                                        if ($tag_term && !is_wp_error($tag_term)) {
                                            echo '<span class="tag">' . esc_html($tag_term->name) . '</span>';
                                        }
                                    }
                                }

                                // 2) type_cuisine (ACF type_de_cuisine)
                                if (!empty($type_cuisine_tags)) {
                                    if (is_array($type_cuisine_tags)) {
                                        foreach ($type_cuisine_tags as $cuisine) {
                                            $cuisine_term = is_object($cuisine) ? $cuisine : get_term((int) $cuisine, 'type_cuisine');
                                            if ($cuisine_term && !is_wp_error($cuisine_term)) {
                                                echo '<span class="tag tag-type-cuisine">' . esc_html($cuisine_term->name) . '</span>';
                                            }
                                        }
                                    } else {
                                        $cuisine_term = is_object($type_cuisine_tags) ? $type_cuisine_tags : get_term((int) $type_cuisine_tags, 'type_cuisine');
                                        if ($cuisine_term && !is_wp_error($cuisine_term)) {
                                            echo '<span class="tag tag-type-cuisine">' . esc_html($cuisine_term->name) . '</span>';
                                        }
                                    }
                                }
                                ?>
                            </div>
                        <?php endif; ?>


                        <!-- Horaires -->
                        <?php if ($horaires && is_array($horaires)) : ?>
                            <div class="restaurant-hours">
                                <?php
                                // Codes EXACTS ACF (valeurs stockées) => Labels affichés
                                $days_display = [
                                    'L'   => ['label' => 'Lun', 'title' => 'Lundi'],
                                    'Mar' => ['label' => 'Mar', 'title' => 'Mardi'],
                                    'Mer' => ['label' => 'Mer', 'title' => 'Mercredi'],
                                    'J'   => ['label' => 'Jeu', 'title' => 'Jeudi'],
                                    'V'   => ['label' => 'Ven', 'title' => 'Vendredi'],
                                    'S'   => ['label' => 'Sam', 'title' => 'Samedi'],
                                    'D'   => ['label' => 'Dim', 'title' => 'Dimanche'],
                                ];

                                foreach ($horaires as $horaire) :
                                    $periode = $horaire['periode'] ?? '';
                                    $jours = $horaire['jours'] ?? [];
                                    $jours = is_array($jours) ? $jours : [];
                                ?>
                                    <p class="hours-line">
                                        <span class="hours-label"><?php echo esc_html($periode); ?> :</span>
                                        <span class="hours-days">
                                            <?php foreach ($days_display as $day_code => $meta) : ?>
                                                <span
                                                    class="day <?php echo in_array($day_code, $jours, true) ? 'active' : 'inactive'; ?>"
                                                    title="<?php echo esc_attr($meta['title']); ?>">
                                                    <?php echo esc_html($meta['label']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </span>
                                    </p>
                                <?php endforeach; ?>

                            </div>
                        <?php endif; ?>
                    </header>

                    <!-- Citation du chef -->
                    <div class="restaurant-quote">
                        <blockquote>
                            <?php echo wp_kses_post($citation_texte); ?>
                        </blockquote>
                        <?php if (!empty($citation_auteur)) { ?><p class="quote-author">— <?php echo esc_html($citation_auteur); ?></p><?php } ?>
                    </div>

                    <!-- Description du menu -->
                    <?php if ($description_menu) : ?>
                        <div class="restaurant-menu-description">
                            <h2><?php echo esc_html($str_menu_label); ?> :</h2>
                            <?php echo wpautop(esc_html($description_menu)); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Exemples de plats -->
                    <?php if ($exemple_plats) : ?>
                        <div class="restaurant-dishes">
                            <h3><?php echo esc_html($str_exemples_label); ?> :</h3>
                            <?php
                            // Convertir le texte en liste si ce n'est pas déjà du HTML
                            $plats_lines = explode("\n", $exemple_plats);
                            if (count($plats_lines) > 1) : ?>
                                <ul class="dishes-list">
                                    <?php foreach ($plats_lines as $plat) :
                                        $plat = trim($plat);
                                        if (!empty($plat)) : ?>
                                            <li><?php echo esc_html($plat); ?></li>
                                    <?php endif;
                                    endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p><?php echo esc_html($exemple_plats); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Prix -->
                    <?php if ($tarifs && is_array($tarifs)) : ?>
                        <div class="restaurant-prices">
                            <h3>Prix :</h3>
                            <ul class="prices-list">
                                <?php foreach ($tarifs as $tarif) : ?>
                                    <li>
                                        <span class="price-label"><?php echo esc_html($tarif['nom_de_menu']); ?>:</span>
                                        <span class="price-value"><?php echo esc_html($tarif['prix']); ?></span>

                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <aside class="restaurant-sidebar">
                    <div class="offer-box">
                        <span class="offer-label">
                            <?php echo count($remises_actives) > 1 ? esc_html($str_offer_title) : esc_html($str_offer_label); ?>
                        </span>
                        <?php

                        $remises_actives = array_values($remises_actives);

                        // ── Vérifier si l'utilisateur a déjà réservé ──
                        $has_booked        = false;
                        $booked_date_fmt   = '';
                        if (is_user_logged_in() && function_exists('mrds_is_current_user_member') && mrds_is_current_user_member()) {
                            $user_id       = get_current_user_id();
                            $resa_existante = get_posts([
                                'post_type'      => 'mrds_reservation',
                                'posts_per_page' => 1,
                                'post_status'    => 'publish',
                                'meta_query'     => [
                                    'relation' => 'AND',
                                    ['key' => '_mrds_user_id',       'value' => $user_id],
                                    ['key' => '_mrds_restaurant_id', 'value' => $restaurant_id],
                                    ['key' => '_mrds_status', 'value' => ['cancelled', 'refused'], 'compare' => 'NOT IN'],
                                ],
                                'meta_key' => '_mrds_date',
                                'orderby'  => 'meta_value',
                                'order'    => 'DESC',
                            ]);
                            if (!empty($resa_existante)) {
                                $has_booked      = true;
                                $booked_date_raw = get_post_meta($resa_existante[0]->ID, '_mrds_date', true);
                                $booked_date_fmt = $booked_date_raw ? date_i18n('d/m/Y', strtotime($booked_date_raw)) : '';
                            }
                        }

                        // ── Correspondance jours abréviations FR ──
                        $jours_abbr = [
                            'mon' => 'Lun',
                            'tue' => 'Mar',
                            'wed' => 'Mer',
                            'thu' => 'Jeu',
                            'fri' => 'Ven',
                            'sat' => 'Sam',
                            'sun' => 'Dim',
                        ];
                        ?>

                        <?php foreach ($remises_actives as $remise) :
                            $r_id       = $remise->ID;
                            $valeur_max = get_field('valeur_max_remise', $r_id);
                            $date_debut = get_field('date_debut', $r_id); // format d/m/Y
                            $date_fin   = get_field('date_fin', $r_id);
                            $jours      = get_field('jours_semaine', $r_id) ?: [];
                            $services   = get_field('services', $r_id) ?: [];
                            $min_couv   = get_field('nombre_minimum_de_couverts', $r_id);
                            $max_couv   = get_field('nombre_maximum_de_couverts', $r_id);
                            $montant_min = get_field('montant_minimum_commande', $r_id);

                            // Jours abrégés
                            $jours_labels = array_map(fn($j) => $jours_abbr[$j] ?? $j, $jours);
                            if (count($jours_labels) > 1) {
                                $last_jour = array_pop($jours_labels);
                                $jours_str = implode(', ', $jours_labels) . ' et ' . $last_jour;
                            } else {
                                $jours_str = implode('', $jours_labels);
                            }
                            // Services
                            $services_labels = [];
                            if (in_array('dejeuner', $services)) $services_labels[] = 'déjeuner';
                            if (in_array('diner',    $services)) $services_labels[] = 'dîner';
                            if (count($services_labels) > 1) {
                                $last_service = array_pop($services_labels);
                                $services_str = implode(', ', $services_labels) . ' et ' . $last_service;
                            } else {
                                $services_str = implode('', $services_labels);
                            }
                            $validite_jours = $jours_str . ($services_str ? ' au ' . $services_str : '');

                            // Couverts + montant min
                            $couverts_str = '';
                            if ($min_couv && $max_couv) $couverts_str = $min_couv . '-' . $max_couv . ' couverts';
                            elseif ($min_couv)           $couverts_str = 'min. ' . $min_couv . ' couverts';
                            if ($montant_min)             $couverts_str .= ($couverts_str ? ', ' : '') . 'min. ' . $montant_min . '€';

                            // Conversion date d/m/Y → YYYY-mm-dd pour data-* (utilisé par le JS)
                            $data_debut = '';
                            $data_fin   = '';
                            if ($date_debut) {
                                $p = explode('/', $date_debut);
                                if (count($p) === 3) $data_debut = $p[2] . '-' . $p[1] . '-' . $p[0];
                            }
                            if ($date_fin) {
                                $p = explode('/', $date_fin);
                                if (count($p) === 3) $data_fin   = $p[2] . '-' . $p[1] . '-' . $p[0];
                            }
                        ?>
                            <div class="remise-card"
                                data-date-debut="<?php echo esc_attr($data_debut); ?>"
                                data-date-fin="<?php echo esc_attr($data_fin); ?>"
                                data-jours="<?php echo esc_attr(json_encode($jours)); ?>">

                                <p class="remise-titre"><?php echo esc_html($remise->post_title); ?></p>

                                <?php if ($valeur_max) : ?>
                                    <p class="remise-max">Jusqu'à <?php echo esc_html($valeur_max); ?>€ max.</p>
                                <?php endif; ?>

                                <?php if ($date_debut && $date_fin) : ?>
                                    <p class="remise-validite"><strong><?php echo esc_html($str_validite_label); ?> :</strong>
                                        Du <?php echo esc_html($date_debut); ?> au <?php echo esc_html($date_fin); ?></p>
                                <?php endif; ?>

                                <?php if ($validite_jours) : ?>
                                    <p class="remise-jours"><strong><?php echo esc_html($str_validite_jours_label); ?> :</strong> <?php echo esc_html($validite_jours); ?></p>
                                <?php endif; ?>

                                <?php if ($couverts_str) : ?>
                                    <p class="remise-couverts"><strong>Couverts/Min. :</strong> <?php echo esc_html($couverts_str); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if (function_exists('mrds_is_current_user_member') && mrds_is_current_user_member()) : ?>

                            <?php if ($has_booked) : ?>
                                <p class="already-booked-text">
                                    Vous avez déjà réservé le <strong><?php echo esc_html($booked_date_fmt); ?></strong> dans ce restaurant.
                                </p>
                            <?php endif; ?>

                            <!-- Widget Réservation — toujours affiché -->
                            <div class="reservation-widget-inline" data-restaurant-id="<?php echo esc_attr($restaurant_id); ?>">
                                <div class="widget-selects">
                                    <div class="widget-select-item">
                                        <span class="select-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                                <line x1="3" y1="10" x2="21" y2="10"></line>
                                            </svg>
                                        </span>
                                        <div class="select-wrapper">
                                            <input type="text" class="widget-date-picker" id="widget-date-<?php echo $restaurant_id; ?>" placeholder="Date" readonly>
                                        </div>
                                    </div>
                                    <div class="widget-select-item">
                                        <span class="select-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                        </span>
                                        <div class="select-wrapper">
                                            <select class="widget-time-select" id="widget-time-<?php echo $restaurant_id; ?>" disabled>
                                                <option value="">Heure</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="widget-select-item">
                                        <span class="select-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            </svg>
                                        </span>
                                        <div class="select-wrapper">
                                            <select class="widget-guests-select" id="widget-guests-<?php echo $restaurant_id; ?>">
                                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                                    <option value="<?php echo $i; ?>" <?php selected($i, 2); ?>><?php echo $i; ?> Pers.</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <?php echo do_shortcode('[mrds_button class="my-btn-gold" text="' . esc_attr($str_btn_reserver) . '" link="#" id="btn-reserver-' . $restaurant_id . '"]');
                                ?>
                            </div>

                        <?php else : ?>
                            <?php echo do_shortcode('[mrds_button class="my-btn-gold" text="' . esc_attr($str_btn_rejoindre) . '" link="' . esc_url($str_btn_rejoindre_link) . '"]'); ?>
                        <?php endif; ?>

                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>