<?php mrdstheme_get_header(); ?>

<?php
if (have_posts()) :
    while (have_posts()) : the_post(); ?>
        <h2><?php the_title(); ?></h2>
        <div><?php the_content(); ?></div>
    <?php endwhile;
else :
    echo '<p>Aucun contenu trouvé.</p>';
endif;
?>

<?php get_footer(); ?>
