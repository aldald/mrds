<?php

/**
 * Template Name: Découvrir le Club
 */
?>
<?php mrdstheme_get_header(); ?>

<?php
// ============================================
// RÉCUPÉRATION DES CHAMPS ACF
// ============================================

// CTA Section
$dc_cta_button_text = get_field('dc_cta_button_text') ?: 'Trouver mon prochain restaurant';
$dc_button_link = get_field('dc_button_link');
$dc_button_url = $dc_button_link ? $dc_button_link['url'] : '/';



// Section Avantages
$dc_avantages_title = get_field('dc_avantages_title') ?: 'Les avantages';
$dc_avantages_subtitle = get_field('dc_avantages_subtitle') ?: 'DU CLUB';
$dc_avantages_items = get_field('dc_avantages_items');


// Section Chiffres
$dc_chiffres_items = get_field('dc_chiffres_items');

// Section Carte Membre
$dc_carte_membre_title = get_field('dc_carte_membre_title') ?: 'Comment s\'utilise la carte membre ?';
$dc_carte_membre_items = get_field('dc_carte_membre_items');
$dc_carte_membre_button_text = get_field('dc_carte_membre_button_text') ?: 'Rejoindre le club';
$dc_carte_membre_button_link = get_field('dc_carte_membre_button_link');
$dc_carte_membre_button_url = $dc_carte_membre_button_link ? $dc_carte_membre_button_link['url'] : '/';
$dc_carte_membre_button_target = ($dc_carte_membre_button_link && !empty($dc_carte_membre_button_link['target'])) ? $dc_carte_membre_button_link['target'] : '_self';

// Section Offre Limitée
$dc_offre_title = get_field('dc_offre_title') ?: 'Découvrez notre offre limitée à 2500 membres';
$dc_offre_image = get_field('dc_offre_image');
$dc_offre_price = get_field('dc_offre_price') ?: 'Pour 500€/an';
$dc_offre_liste = get_field('dc_offre_liste');
$dc_offre_button_text = get_field('dc_offre_button_text') ?: 'Rejoindre le club';
$dc_offre_button_link = get_field('dc_offre_button_link');
$dc_offre_button_url = $dc_offre_button_link ? $dc_offre_button_link['url'] : '/';
$dc_offre_button_target = ($dc_offre_button_link && !empty($dc_offre_button_link['target'])) ? $dc_offre_button_link['target'] : '_self';
?>

<!-- CALL TO ACTION SECTION -->
<section class="cta-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-auto">
                <?php
                echo do_shortcode(sprintf(
                    '[mrds_button class="my-btn-secondary" text="%s" link="%s" id="btnToggleSearch"]',
                    esc_attr($dc_cta_button_text),
                    esc_url($dc_button_url)
                ));
                ?>

            </div>
        </div>
    </div>
</section>


<!-- SECTION AVANTAGES -->
<section class="section-avantages">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html($dc_avantages_title); ?></h2>
        <div class="section-subtitle">
            <span class="dot"></span>
            <svg viewBox="0 0 120 35" class="curved-text">
                <path id="curve" d="M 5,5 Q 60,35 115,5" fill="transparent" />
                <text>
                    <textPath href="#curve" startOffset="50%" text-anchor="middle">
                        <?php echo esc_html($dc_avantages_subtitle); ?>
                    </textPath>
                </text>
            </svg>
            <span class="dot"></span>
        </div>

        <div class="row">
            <?php if ($dc_avantages_items) : ?>
                <?php foreach ($dc_avantages_items as $avantage) : ?>
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
                <div class="col-12">
                    <p class="text-center">Aucun avantage configuré.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- SECTION CHIFFRES -->
<section class="section-chiffres">
    <div class="container">
        <div class="chiffres-content">
            <div class="row">
                <?php if ($dc_chiffres_items) : ?>
                    <?php foreach ($dc_chiffres_items as $chiffre) : ?>
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


<!-- SECTION CARTE MEMBRE -->
<section class="section-carte-membre">
    <div class="container">
        <div class="bg-carte-memeber">
            <h2 class="section-title"><?php echo esc_html($dc_carte_membre_title); ?></h2>

            <div class="row">
                <?php if ($dc_carte_membre_items) : ?>
                    <?php foreach ($dc_carte_membre_items as $item) : ?>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="carte-membre-card">
                                <div class="card-icon">
                                    <?php if (!empty($item['icon'])) : ?>
                                        <img src="<?php echo esc_url($item['icon']['url']); ?>" alt="<?php echo esc_attr($item['icon']['alt']); ?>">
                                    <?php else : ?>
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/icon.png" alt="">
                                    <?php endif; ?>
                                </div>
                                <p class="card-text"><?php echo esc_html($item['text']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="col-12">
                        <p class="text-center">Aucune information configurée.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (! is_user_logged_in()) : ?>
                <div class="section-cta">
                    <?php
                    echo do_shortcode(sprintf(
                        '[mrds_button class="my-btn-primary" text="%s" link="%s" target="%s"]',
                        esc_attr($dc_carte_membre_button_text),
                        esc_url($dc_carte_membre_button_url),
                        esc_attr($dc_carte_membre_button_target)
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- SECTION OFFRE LIMITÉE -->
<section class="section-offre-limitee">
    <div class="container">
        <div class="offre-header">
            <h2 class="section-title">
                <?php echo esc_html($dc_offre_title); ?>
            </h2>
        </div>

        <div class="row g-0">
            <div class="col-lg-5">
                <div class="offre-image">
                    <?php if ($dc_offre_image) : ?>
                        <img src="<?php echo esc_url($dc_offre_image['url']); ?>" alt="<?php echo esc_attr($dc_offre_image['alt']); ?>">
                    <?php else : ?>
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/offre-couverts.png" alt="Table élégante">
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="offre-content">
                    <p class="offre-price"><?php echo esc_html($dc_offre_price); ?></p>

                    <?php if ($dc_offre_liste) : ?>
                        <ul class="offre-liste">
                            <?php foreach ($dc_offre_liste as $ligne) : ?>
                                <li><?php echo esc_html($ligne['item']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (! is_user_logged_in()) : ?>

                        <div class="offre-cta">
                            <?php
                            echo do_shortcode(sprintf(
                                '[mrds_button class="my-btn-gold" text="%s" link="%s" target="%s"]',
                                esc_attr($dc_offre_button_text),
                                esc_url($dc_offre_button_url),
                                esc_attr($dc_offre_button_target)
                            ));
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>


<?php get_footer(); ?>