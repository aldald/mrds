<?php

/**
 * Template Name: Page FAQ
 */
?>
<?php mrdstheme_get_header(); ?>

<?php
// ============================================
// PAGE FAQ - Récupération des champs ACF
// ============================================
$faq_title = get_field('faq_title') ?: 'FAQ';
$faq_items = get_field('faq_items');
?>

<section class="section-faq">
    <div class="container">
        <h1 class="faq-title"><?php echo esc_html($faq_title); ?></h1>

        <div class="faq-list">
            <?php if ($faq_items) : ?>
                <?php foreach ($faq_items as $faq) : ?>
                    <div class="faq-item">
                        <button class="faq-question" aria-expanded="false">
                            <span class="faq-question-text"><?php echo esc_html($faq['question']); ?></span>
                            <span class="faq-arrow">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                    <path id="Tracé_10" data-name="Tracé 10" d="M8,0,6.545,1.455l5.506,5.506H0V9.039H12.052L6.545,14.545,8,16l8-8Z" transform="translate(0 16) rotate(-90)" fill="#141b42" />
                                </svg>
                            </span>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-content">
                                <?php echo wp_kses_post($faq['answer']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="text-center">Aucune question configurée.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>