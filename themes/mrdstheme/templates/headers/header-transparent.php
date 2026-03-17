<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header header-transparent">
    <div class="header-container">
        
        <!-- Hamburger Button (Mobile) -->
        <button class="hamburger-btn" aria-label="Menu" aria-expanded="false">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
        
        <!-- Menu Gauche -->
<nav class="nav-left">
    <?php mrdstheme_nav_left( 'menu-list' ); ?>
</nav>

        <!-- Logo Centre -->
        <div class="logo-center">
            
            <!-- LOGO TRANSPARENT (visible par défaut, caché au scroll) -->
            <div class="logo-transparent-version">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo home_url(); ?>">
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo-icon.png" alt="" class="logo-icon">
                        <span class="logo-text">Mes Ronds<br>de Serviette</span>
                        <span class="logo-line"></span>
                        <span class="logo-city">• PARIS •</span>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- LOGO BLEU (caché par défaut, visible au scroll) -->
            <div class="logo-blue-version">
                <a href="<?php echo home_url(); ?>">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo-header-blue.svg" alt="" class="logo-icon">
                </a>
            </div>
            
        </div>

        <!-- Menu Droite (dynamique selon connexion) -->
        <nav class="nav-right">
            <?php mrdstheme_nav_right(); ?>
        </nav>
        
        <!-- User Dropdown Mobile (dynamique selon connexion) -->
        <?php mrdstheme_user_dropdown_mobile(); ?>

    </div>
    
    <!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay">
    <nav class="mobile-menu-nav">
        <?php mrdstheme_nav_left( 'mobile-menu-list', '_mobile' ); ?>
    </nav>
</div>
</header>

<main>