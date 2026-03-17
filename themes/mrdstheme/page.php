<?php mrdstheme_get_header(); ?>

<section class="section-mentions-legales">
    <div class="container">
        
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        
        <h1 class="mentions-main-title"><?php the_title(); ?></h1>
        
        <div class="mentions-content">
            <?php the_content(); ?>
        </div>
        
        <?php endwhile; else : ?>
            <p>Aucune page trouvée.</p>
        <?php endif; ?>
        
    </div>
</section>

<?php get_footer(); ?>