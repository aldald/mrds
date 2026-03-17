<?php mrdstheme_get_header(); ?>

<?php
if (have_posts()) :
    while (have_posts()) : the_post(); ?>
        <article>
            <h1><?php the_title(); ?></h1>
            <div><?php the_content(); ?></div>
        </article>
    <?php endwhile;
else :
    echo '<p>Article non trouvé.</p>';
endif;
?>

<?php get_footer(); ?>
