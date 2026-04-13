<?php
/**
 * Template pour la page Réserver
 * Slug: reserver
 */

mrdstheme_get_header(); 
?>

<section class="section-reservation-page">
    <div class="container">
        
        <?php echo do_shortcode('[mrds_reservation_form]'); ?>
        
    </div>
</section>

<?php get_footer(); ?>