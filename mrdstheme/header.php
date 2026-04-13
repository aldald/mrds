<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <header class="site-header">
        <div class="header-container">

            <!-- Menu Gauche -->
            <nav class="nav-left">
                <?php
                wp_nav_menu([
                    'theme_location' => 'menu_left',
                    'container' => false,
                    'menu_class' => 'menu-list',
                    'fallback_cb' => false,
                ]);
                ?>
            </nav>

            <!-- Logo Centre -->
            <div class="logo-center">
                <!-- Logo principal (état normal) -->
                <a href="<?php echo home_url(); ?>" class="logo-default">
                    <?php if (has_custom_logo()): ?>
                        <?php the_custom_logo(); ?>
                    <?php else: ?>
                        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/logo.png"
                            alt="<?php bloginfo('name'); ?>" class="logo-icon">
                        <span class="logo-text">Mes Ronds<br>de Serviette</span>
                        <span class="logo-line"></span>
                        <span class="logo-city">• PARIS •</span>
                    <?php endif; ?>
                </a>

                <!-- Logo scroll (état compact) -->
                <a href="<?php echo home_url(); ?>" class="logo-scrolled">
                    <span class="logo-text">Mes Ronds<br>de Serviette</span>
                    <span class="logo-city">• PARIS •</span>
                </a>
            </div>

            <!-- Menu Droite -->
            <nav class="nav-right">
                <?php
                wp_nav_menu([
                    'theme_location' => 'menu_right',
                    'container' => false,
                    'menu_class' => 'menu-list',
                    'fallback_cb' => false,
                ]);
                ?>
            </nav>

        </div>
    </header>

    <main>