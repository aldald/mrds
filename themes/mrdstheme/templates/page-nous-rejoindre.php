<?php

/**
 * Template Name: Page Nous Rejoindre
 */

// Si l'utilisateur est connecté => redirection vers Mon compte
if (is_user_logged_in()) {
    // URL WooCommerce Mon compte si dispo, sinon fallback /mon-compte/
    $account_url = function_exists('wc_get_page_permalink')
        ? wc_get_page_permalink('myaccount')
        : home_url('/mon-compte/');

    wp_safe_redirect($account_url);
    exit;
}

?>
<?php mrdstheme_get_header(); ?>

<?php
// ============================================
// PAGE NOUS REJOINDRE - Récupération des champs ACF
// ============================================

// Hero Section
$nr_hero_title = get_field('nr_hero_title') ?: 'Devenez membre et profitez de -20% sur + de 40 restaurants';

// Section Fonctionnement
$nr_fonctionnement_title = get_field('nr_fonctionnement_title') ?: 'Le fonctionnement';
$nr_fonctionnement_subtitle = get_field('nr_fonctionnement_subtitle') ?: 'DU CLUB';
$nr_fonctionnement_items = get_field('nr_fonctionnement_items');
$nr_fonctionnement_button_text = get_field('nr_fonctionnement_button_text') ?: 'Rejoindre le club';
$nr_fonctionnement_button_link = get_field('nr_fonctionnement_button_link') ?: '#section-inscription';

// Section Inscription
$nr_inscription_intro_title = get_field('nr_inscription_intro_title') ?: 'Je souhaite adhérer au club Mes Ronds de Serviette :';
$nr_inscription_intro_text = get_field('nr_inscription_intro_text') ?: 'C\'est et dolore de l\'adhésion dé tur e : consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.';
$nr_inscription_form_title = get_field('nr_inscription_form_title') ?: 'Je m\'inscris :';
?>

<!-- HERO SECTION -->
<section class="hero-rejoindre">
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="hero-content text-center">
                    <h1 class="hero-title"><?php echo esc_html($nr_hero_title); ?></h1>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION FONCTIONNEMENT -->
<section class="section-fonctionnement">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html($nr_fonctionnement_title); ?></h2>
        <div class="section-subtitle">
            <span class="dot"></span>
            <svg viewBox="0 0 120 35" class="curved-text">
                <path id="curve" d="M 5,5 Q 60,35 115,5" fill="transparent" />
                <text>
                    <textPath href="#curve" startOffset="50%" text-anchor="middle">
                        <?php echo esc_html($nr_fonctionnement_subtitle); ?>
                    </textPath>
                </text>
            </svg>
            <span class="dot"></span>
        </div>

        <div class="row">
            <?php if ($nr_fonctionnement_items) : ?>
                <?php $count = 1; ?>
                <?php foreach ($nr_fonctionnement_items as $item) : ?>
                    <div class="col-12 col-md-4">
                        <div class="fonctionnement-card">
                            <span class="card-number"><?php echo $count; ?></span>
                            <h3 class="card-title"><?php echo esc_html($item['title']); ?></h3>
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
                    <?php
                    echo do_shortcode(sprintf(
                        '[mrds_button class="my-btn-primary" text="%s" link="%s"]',
                        esc_attr($nr_fonctionnement_button_text),
                        esc_attr($nr_fonctionnement_button_link)
                    ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION INSCRIPTION -->
<section class="section-inscription" id="section-inscription">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">

                <!-- Bloc intro -->
                <div class="inscription-intro">
                    <div class="intro-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 30 30">
                            <g id="Groupe_883" data-name="Groupe 883" transform="translate(-364 -1102)">
                                <g id="Tick" transform="translate(371 1109)">
                                    <rect id="Rectangle_460" data-name="Rectangle 460" width="16" height="16" fill="none" />
                                    <path id="Checkbox" d="M6.345,11.034,0,4.69,1.287,3.4,6.345,8.368,14.713,0,16,1.287Z" transform="translate(0 2)" fill="#141b42" />
                                </g>
                                <g id="Radio_Button" data-name="Radio Button" transform="translate(364 1102)">
                                    <g id="Rectangle_664" data-name="Rectangle 664" fill="none" stroke="#303133" stroke-width="2">
                                        <rect width="30" height="30" rx="15" stroke="none" />
                                        <rect x="1" y="1" width="28" height="28" rx="14" fill="none" />
                                    </g>
                                    <rect id="Rectangle_666" data-name="Rectangle 666" width="14" height="14" rx="7" transform="translate(8 8)" fill="none" />
                                </g>
                            </g>
                        </svg>
                    </div>
                    <h2 class="intro-title"><?php echo esc_html($nr_inscription_intro_title); ?></h2>
                    <p class="intro-text"><?php echo esc_html($nr_inscription_intro_text); ?></p>
                </div>

                <!-- Formulaire -->
                <div class="inscription-form-container">
                    <h3 class="form-title"><?php echo esc_html($nr_inscription_form_title); ?></h3>

                    <form class="inscription-form" id="mrds-register-form" method="post">

                        <!-- Nonce de sécurité -->
                        <?php wp_nonce_field('mrds_register_nonce', 'mrds_register_nonce'); ?>

                        <!-- Zone messages -->
                        <div class="form-messages" id="register-messages"></div>

                        <div class="form-group full-width">
                            <label for="insc-email">E-mail *</label>
                            <input type="email" id="insc-email" name="email" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="insc-nom">Nom *</label>
                                <input type="text" id="insc-nom" name="nom" required>
                            </div>
                            <div class="form-group">
                                <label for="insc-prenom">Prénom *</label>
                                <input type="text" id="insc-prenom" name="prenom" required>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="insc-telephone">Téléphone</label>
                            <input type="tel" id="insc-telephone" name="telephone">
                        </div>

                        <div class="form-group full-width">
                            <label for="insc-adresse">Adresse</label>
                            <input type="text" id="insc-adresse" name="adresse">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="insc-ville">Ville</label>
                                <input type="text" id="insc-ville" name="ville">
                            </div>
                            <div class="form-group">
                                <label for="insc-codepostal">Code postal</label>
                                <input type="text" id="insc-codepostal" name="codepostal">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="insc-pays">Pays</label>
                            <select id="insc-pays" name="pays">
                                <option value="FR">France</option>
                                <option value="BE">Belgique</option>
                                <option value="CH">Suisse</option>
                                <option value="LU">Luxembourg</option>
                                <option value="CA">Canada</option>
                            </select>
                        </div>

                        <div class="form-checkboxes">
                            <label class="checkbox-label">
                                <input type="checkbox" name="cgu" required>
                                <span class="checkmark"></span>
                                J'accepte les CGU/CGV
                            </label>

                            <label class="checkbox-label">
                                <input type="checkbox" name="rgpd">
                                <span class="checkmark"></span>
                                Les informations recueillies à partir de ce formulaire sont nécessaires à la gestion de votre demande par MesRondsDeServiette.
                            </label>
                        </div>

                        <p class="form-rgpd-link">
                            <a href="<?php echo home_url('/politique-de-confidentialite'); ?>">En savoir plus sur la gestion de vos données et vos droits</a>
                        </p>

                        <div class="form-submit">
                            <?php echo do_shortcode('[mrds_button class="my-btn-gold" text="Paiement" type="submit" id="btn-register"]'); ?>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>