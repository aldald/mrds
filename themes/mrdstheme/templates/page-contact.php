<?php

/**
 * Template Name: Page Contact
 * 
 * @package mrdstheme
 */

mrdstheme_get_header();

// ============================================
// CHAMPS ACF (optionnels) - Configuration
// ============================================
$contact_title = get_field('contact_title') ?: 'Contactez';
$contact_subtitle = get_field('contact_subtitle') ?: 'NOUS';
$contact_intro = get_field('contact_intro') ?: 'Une question, une suggestion ou simplement envie d\'en savoir plus sur notre club ? Notre équipe est à votre écoute.';

// Informations de contact
$contact_adresse = get_field('contact_adresse') ?: '123 Avenue des Champs-Élysées, 75008 Paris';
$contact_telephone = get_field('contact_telephone') ?: '+33 1 23 45 67 89';
$contact_email = get_field('contact_email') ?: 'contact@mesrestosdusoir.fr';
$contact_horaires = get_field('contact_horaires') ?: 'Lundi - Vendredi : 9h - 18h';

// ID du formulaire Contact Form 7
$cf7_form_id = get_field('cf7_form_id') ?: 0;
?>

<!-- HERO CONTACT -->
<section class="hero-contact">
    <div class="container">
        <div class="hero-contact-content text-center">
            <h1 class="section-title"><?php echo esc_html($contact_title); ?></h1>
            <div class="section-subtitle">
                <span class="dot"></span>
                <svg viewBox="0 0 120 35" class="curved-text">
                    <path id="curve" d="M 5,5 Q 60,35 115,5" fill="transparent" />
                    <text>
                        <textPath href="#curve" startOffset="50%" text-anchor="middle">
                            <?php echo esc_html($contact_subtitle); ?>
                        </textPath>
                    </text>
                </svg>
                <span class="dot"></span>
            </div>
            <p class="hero-contact-intro"><?php echo esc_html($contact_intro); ?></p>
        </div>
    </div>
</section>

<!-- SECTION CONTACT -->
<section class="section-contact">
    <div class="container">
        <div class="row">

            <!-- COLONNE GAUCHE : Formulaire -->
            <div class="col-12 col-lg-7">
                <div class="contact-form-box">
                    <h2 class="box-title">
                        <span class="dot"></span>
                        Envoyez-nous un message
                        <span class="dot"></span>
                    </h2>

                    <div class="contact-form-wrapper">
                        <?php if ($cf7_form_id) : ?>
                            <?php echo do_shortcode('[contact-form-7 id="' . esc_attr($cf7_form_id) . '"]'); ?>
                        <?php else : ?>
                            <!-- Formulaire par défaut si CF7 non configuré -->
                            <form class="mrds-contact-form" id="mrdsContactForm">
                                <div class="form-row">
                                    <div class="form-group half">
                                        <label for="contact_nom">Nom <span class="required">*</span></label>
                                        <input type="text" id="contact_nom" name="nom" required placeholder="Votre nom">
                                    </div>
                                    <div class="form-group half">
                                        <label for="contact_prenom">Prénom <span class="required">*</span></label>
                                        <input type="text" id="contact_prenom" name="prenom" required placeholder="Votre prénom">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group half">
                                        <label for="contact_email">Email <span class="required">*</span></label>
                                        <input type="email" id="contact_email" name="email" required placeholder="votre@email.com">
                                    </div>
                                    <div class="form-group half">
                                        <label for="contact_telephone">Téléphone</label>
                                        <input type="tel" id="contact_telephone" name="telephone" placeholder="+33 6 12 34 56 78">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="contact_sujet">Sujet <span class="required">*</span></label>
                                    <select id="contact_sujet" name="sujet" required>
                                        <option value="">Sélectionnez un sujet</option>
                                        <option value="adhesion">Adhésion au club</option>
                                        <option value="reservation">Question sur une réservation</option>
                                        <option value="partenariat">Devenir restaurant partenaire</option>
                                        <option value="reclamation">Réclamation</option>
                                        <option value="autre">Autre demande</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="contact_message">Message <span class="required">*</span></label>
                                    <textarea id="contact_message" name="message" rows="6" required placeholder="Votre message..."></textarea>
                                </div>

                                <div class="form-group checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="rgpd" required>
                                        <span class="checkmark"></span>
                                        J'accepte que mes données soient utilisées pour traiter ma demande conformément à la <a href="/politique-de-confidentialite/" target="_blank">politique de confidentialité</a>. <span class="required">*</span>
                                    </label>
                                </div>

                                <div class="form-submit">
                                    <?php echo do_shortcode('[mrds_button class="my-btn-gold" text="Envoyer le message" link="#" id="btnSubmitContact"]'); ?>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE : Informations -->
            <div class="col-12 col-lg-5">
                <div class="contact-info-box">
                    <h2 class="box-title">
                        <span class="dot"></span>
                        Nos coordonnées
                        <span class="dot"></span>
                    </h2>

                    <div class="contact-info-list">
                        <!-- Adresse -->
                        <div class="contact-info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </div>
                            <div class="info-content">
                                <h4>Adresse</h4>
                                <p><?php echo esc_html($contact_adresse); ?></p>
                            </div>
                        </div>

                        <!-- Téléphone -->
                        <div class="contact-info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                            </div>
                            <div class="info-content">
                                <h4>Téléphone</h4>
                                <p><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $contact_telephone)); ?>"><?php echo esc_html($contact_telephone); ?></a></p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="contact-info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </div>
                            <div class="info-content">
                                <h4>Email</h4>
                                <p><a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p>
                            </div>
                        </div>

                        <!-- Horaires -->
                        <div class="contact-info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="info-content">
                                <h4>Horaires</h4>
                                <p><?php echo esc_html($contact_horaires); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Réseaux sociaux -->
                    <div class="contact-social">
                        <h4>Suivez-nous</h4>
                        <div class="social-links">
                            <a href="#" class="social-link" aria-label="Facebook">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                                </svg>
                            </a>
                            <a href="#" class="social-link" aria-label="Instagram">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                                </svg>
                            </a>
                            <a href="#" class="social-link" aria-label="LinkedIn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                                    <rect x="2" y="9" width="4" height="12"></rect>
                                    <circle cx="4" cy="4" r="2"></circle>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Box FAQ rapide -->
                <div class="contact-faq-box">
                    <h3 class="faq-box-title">Questions fréquentes</h3>
                    <div class="section-cta">
                        <?php
                        echo do_shortcode(sprintf(
                            '[mrds_button class="my-btn-gold" text="Voir toutes les FAQ" link="/faq" target=""]',
                           
                        ));
                        ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- SECTION MAP (optionnelle) -->
<section class="section-contact-map">
    <div class="container-fluid p-0">
        <div class="contact-map-wrapper">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.2158746411657!2d2.3044193!3d48.8698679!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66fc4f8f3049b%3A0xcbb47407434935db!2sAv.%20des%20Champs-%C3%89lys%C3%A9es%2C%20Paris!5e0!3m2!1sfr!2sfr!4v1234567890"
                width="100%"
                height="400"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<?php get_footer(); ?>