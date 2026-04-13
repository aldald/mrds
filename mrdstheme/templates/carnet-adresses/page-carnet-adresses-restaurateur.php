<?php

/**
 * Template: Carnet d'adresses - Restaurateur
 * Fichier: templates/carnet-adresses/page-carnet-adresses-restaurateur.php
 */

mrdstheme_get_header();

$current_user = wp_get_current_user();

// Récupérer le restaurant du restaurateur
$restaurant = null;
$restaurant_name = 'Nom du restaurant';

$args = [
    'post_type' => 'restaurant',
    'posts_per_page' => 1,
    'meta_query' => [
        'relation' => 'OR',
        [
            'key' => 'restaurant_owner',
            'value' => $current_user->ID,
            'compare' => '='
        ],
        [
            'key' => 'restaurant_restaurateurs',
            'value' => $current_user->ID,
            'compare' => 'LIKE'
        ]
    ]
];

$query = new WP_Query($args);
if ($query->have_posts()) {
    $query->the_post();
    $restaurant = get_post();
    $restaurant_name = get_the_title();
    wp_reset_postdata();
}

// Stats statiques
$nb_reservations = 24;
$nb_repas = 156;
?>

<!-- HERO RESTAURATEUR -->
<section class="hero-restaurateur">
    <div class="container">
        <div class="hero-restaurateur-content text-center">
            <h2 class="section-title">Votre espace</h2>
            <div class="section-subtitle">
                <span class="dot"></span>
                <svg viewBox="0 0 120 35" class="curved-text">
                    <path id="curve" d="M 5,5 Q 60,35 115,5" fill="transparent" />
                    <text>
                        <textPath href="#curve" startOffset="50%" text-anchor="middle">
                            <?= $restaurant_name; ?>
                        </textPath>
                    </text>
                </svg>
                <span class="dot"></span>
            </div>


            <div class="hero-stats-box">
                Déjà <strong><?php echo esc_html($nb_reservations); ?> réservations</strong> effectuées et <strong><?php echo esc_html($nb_repas); ?> repas</strong> servis !
            </div>
        </div>
    </div>
</section>

<!-- CONTENU PRINCIPAL -->
<section class="section-espace-restaurateur">
    <div class="container">
        <div class="row">

            <!-- COLONNE GAUCHE : Calendrier -->
            <div class="col-12 col-lg-6">
                <div class="box-apercu-reservations">
                    <h2 class="box-title">
                        <span class="dot"></span>
                        Aperçu de mes réservations
                        <span class="dot"></span>
                    </h2>

                    <div class="calendrier-wrapper">
                        <div class="calendrier-header">
                            <button class="calendrier-nav prev" aria-label="Mois précédent">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="16" viewBox="0 0 10 16">
                                    <path d="M8 0L0 8l8 8" fill="none" stroke="#141B42" stroke-width="2" />
                                </svg>
                            </button>
                            <span class="calendrier-mois">Novembre</span>
                            <button class="calendrier-nav next" aria-label="Mois suivant">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="16" viewBox="0 0 10 16">
                                    <path d="M2 0l8 8-8 8" fill="none" stroke="#141B42" stroke-width="2" />
                                </svg>
                            </button>
                        </div>

                        <div class="calendrier-jours">
                            <span class="jour-header">L</span>
                            <span class="jour-header">M</span>
                            <span class="jour-header">M</span>
                            <span class="jour-header">J</span>
                            <span class="jour-header">V</span>
                            <span class="jour-header">S</span>
                            <span class="jour-header">D</span>
                        </div>

                        <div class="calendrier-grille">
                            <?php
                            // Calendrier statique - Novembre
                            $jours = [
                                ['', '', '', '', 1, 2, 3],
                                [4, 5, 6, 7, 8, 9, 10],
                                [11, 12, 13, 14, 15, 16, 17],
                                [18, 19, 20, 21, 22, 23, 24],
                                [25, 26, 27, 28, 29, 30, '']
                            ];

                            // Jours avec réservations (statique)
                            $jours_reservations = [
                                2 => [
                                    ['heure' => '20h30', 'couverts' => 4],
                                    ['heure' => '20h30', 'couverts' => 4],
                                    ['heure' => '20h30', 'couverts' => 4],
                                    ['heure' => '20h30', 'couverts' => 4],
                                ],
                                5 => [],
                                4 => [],
                            ];

                            foreach ($jours as $semaine) :
                                foreach ($semaine as $jour) :
                                    $has_resa = isset($jours_reservations[$jour]);
                                    $class = 'jour-cell';
                                    if ($jour === '') $class .= ' empty';
                                    if ($has_resa) $class .= ' has-reservation';
                            ?>
                                    <div class="<?php echo $class; ?>">
                                        <?php if ($jour !== '') : ?>
                                            <span class="jour-numero"><?php echo $jour; ?></span>
                                            <?php if ($has_resa && !empty($jours_reservations[$jour])) : ?>
                                                <div class="jour-tooltip">
                                                    <?php foreach ($jours_reservations[$jour] as $resa) : ?>
                                                        <div class="tooltip-resa">
                                                            <?php echo esc_html($resa['heure']); ?> I <?php echo esc_html($resa['couverts']); ?> convives
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                            <?php
                                endforeach;
                            endforeach;
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE : Actions -->
            <div class="col-12 col-lg-6">
                <div class="box-actions-restaurateur">

                    <!-- Bouton Modifier ma fiche -->
                    <a href="<?php echo get_the_permalink(186) ?>" class="action-box">
                        <span class="action-text">Modifier ma fiche restaurant</span>
                        <span class="action-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35">
                                <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" fill="#141B42" />
                            </svg>
                        </span>
                    </a>

                    <!-- Bouton Personnaliser réductions -->
                    <a href="#" class="action-box">
                        <span class="action-text">Personnaliser mes réductions</span>
                        <span class="action-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 35 35">
                                <path d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" fill="#141B42" />
                            </svg>
                        </span>
                    </a>

                    <!-- Liens secondaires -->
                    <div class="actions-links">
                        <a href="<?php echo $restaurant ? get_permalink($restaurant->ID) : '#'; ?>" class="action-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="M21 21l-4.35-4.35"></path>
                            </svg>
                            Voir ma fiche restaurant comme un client
                        </a>

                        <a href="#" class="action-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="1"></circle>
                                <circle cx="5" cy="12" r="1"></circle>
                                <circle cx="19" cy="12" r="1"></circle>
                            </svg>
                            Plus de paramètres
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<?php get_footer(); ?>