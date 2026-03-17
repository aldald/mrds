</main>

<footer class="site-footer">

    <div class="container">
        <div class="footer-main">
            <!-- Logo -->
            <div class="footer-logo">
                <a href="<?php echo home_url(); ?>">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo-footer.svg" alt="<?php bloginfo('name'); ?>">
                </a>
            </div>


            <div class="right-footer">
                <!-- Bouton scroll to top -->
                <button class="scroll-to-top" id="scrollToTop">
                    <svg id="Groupe_15668" data-name="Groupe 15668" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                        <path id="Tracé_10" data-name="Tracé 10" d="M8,0,6.545,1.455l5.506,5.506H0V9.039H12.052L6.545,14.545,8,16l8-8Z" transform="translate(0 16) rotate(-90)" fill="#141b42" />
                    </svg>

                </button>

                <!-- Réseaux sociaux -->
                <div class="footer-social">
                    <a href="#" target="_blank" aria-label="Instagram">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/instagram.svg" alt="">

                    </a>
                    <a href="#" target="_blank" aria-label="Twitter">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/twitter.svg" alt="">
                    </a>
                    <a href="#" target="_blank" aria-label="Facebook">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/facebook.svg" alt="">
                    </a>
                </div>
            </div>
        </div>

        <!-- Ligne séparatrice -->
        <div class="footer-separator"></div>

        <!-- Copyright et liens légaux -->
        <div class="footer-bottom">
                <?php
                wp_nav_menu([
                    'theme_location' => 'menu_footer',
                    'menu_class'     => 'footer-menu-list',  // ← Votre classe ici
                    'container'      => false,
                    'items_wrap'     => '%3$s',
                    'fallback_cb'    => false,
                    'depth'          => 1,
                ]);
                ?> -
                Tous droits réservés - &nbsp;<a href="https://www.coccinet.com" target="_blank">Coccinet</a> © <?php echo date('Y'); ?>
        </div>
    </div>
</footer>

<?php get_template_part('templates/page-acces-member'); ?>




<?php wp_footer(); ?>
</body>

</html>