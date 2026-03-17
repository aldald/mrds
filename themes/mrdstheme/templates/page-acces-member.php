<!-- Popup Accès Membre -->
<div class="member-popup-overlay" id="memberPopup">
    <div class="member-popup">
        <div class="member-popup-content">

            <!-- Bouton fermer -->
            <button type="button" class="member-popup-close" id="closeMemberPopup" aria-label="Fermer">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <!-- Titre -->
            <h2 class="member-popup-title">Connectez-vous à votre compte</h2>
            <p class="member-popup-subtitle">
                Vous ne possédez pas de compte Membre ? <a href="<?php echo home_url('/nous-rejoindre'); ?>">Cliquez ici</a>
            </p>

            <!-- Formulaire -->
            <form class="member-form" id="mrds-login-form" method="post">

                <!-- Nonce de sécurité -->
                <?php wp_nonce_field('mrds_login_nonce', 'mrds_login_nonce'); ?>
                
                <!-- Zone messages -->
                <div class="form-messages" id="login-messages"></div>

                <div class="form-group">
                    <label for="member-email">E-mail</label>
                    <input type="text" id="member-email" name="email" placeholder="Entrer le nom d'utilisateur ou l'e-mail" required>
                </div>

                <div class="form-group">
                    <label for="member-password">Mot de passe</label>
                    <input type="password" id="member-password" name="password" placeholder="Entrer le mot de passe" required>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Gardez-moi connecté
                    </label>
                    <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password">Mot de passe perdu?</a>
                </div>

                <?php echo do_shortcode('[mrds_button class="my-btn-gold" text="Se connecter" type="submit" id="btn-login"]'); ?>

            </form>

            <!-- Retour accueil -->
            <a href="#" class="member-back" id="closeMemberPopupBack">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                    <g id="Arrow_Up" data-name="Arrow Up" transform="translate(0 16) rotate(-90)">
                        <path id="Tracé_10" data-name="Tracé 10" d="M8,0,6.545,1.455l5.506,5.506H0V9.039H12.052L6.545,14.545,8,16l8-8Z" transform="translate(0 16) rotate(-90)" fill="#141b42" />
                    </g>
                </svg>

                Retour à la page d'accueil
            </a>

        </div>
    </div>
</div>