<?php
/**
 * Template Name: Mentions Légales
 */
?>
<?php mrdstheme_get_header(); ?>

<section class="section-mentions-legales">
    <div class="container">
        
        <h1 class="mentions-main-title"><?php the_title(); ?></h1>
        
        <div class="mentions-content">
            <?php
            if (have_posts()) :
                while (have_posts()) : the_post();
                    the_content();
                endwhile;
            endif;
            ?>
        </div>
        
    </div>
</section>

<?php get_footer(); ?>