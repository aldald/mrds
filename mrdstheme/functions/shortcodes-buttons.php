<?php

/**
 * Shortcode pour les boutons
 * 
 * Utilisation :
 * [mrds_button class="my-btn-primary" text="Rejoindre le club" link="#"]
 * [mrds_button class="my-btn-secondary" text="Trouver mon prochain restaurant" link="#" id="btnToggleSearch"]
 * [mrds_button class="my-btn-tertiary" text="Quelle réduction ?" link="#"]
 * [mrds_button class="my-btn-fourth" text="Voir toutes nos adresses" link="#"]
 * [mrds_button class="my-btn-gold" text="Voir toutes nos adresses" link="#"]
 * [mrds_button class="my-btn-gold" text="Paiement" type="submit" id="btn-register"]
 * 
 * Options :
 * - class  : my-btn-primary, my-btn-secondary, my-btn-tertiary, my-btn-fourth, my-btn-gold
 * - text   : Texte du bouton
 * - link   : URL du lien (ignoré si type="submit" ou type="button")
 * - target : _self (défaut) ou _blank pour nouvel onglet
 * - id     : ID du bouton (optionnel)
 * - type   : (optionnel) "submit" ou "button" pour générer un <button> au lieu d'un <a>
 */

function mrdstheme_button_shortcode($atts)
{
    $atts = shortcode_atts([
        'class'  => 'my-btn-primary',
        'text'   => 'Cliquez ici',
        'link'   => '#',
        'target' => '_self',
        'id'     => '',
        'type'   => '',  // Nouveau : vide = lien <a>, "submit" ou "button" = <button>
    ], $atts, 'mrds_button');

    $class  = esc_attr($atts['class']);
    $text   = esc_html($atts['text']);
    $link   = esc_url($atts['link']);
    $target = esc_attr($atts['target']);
    $id     = !empty($atts['id']) ? 'id="' . esc_attr($atts['id']) . '"' : '';
    $type   = esc_attr($atts['type']);

    // Contenu selon la classe
    switch ($class) {
        case 'my-btn-primary':
            // Avec losanges ◆
            $content = '<span class="btn-diamond">◆</span>' . $text . '<span class="btn-diamond">◆</span>';
            break;

        case 'my-btn-secondary':
            // Avec flèche →
            $content = '<span class="btn-text">' . $text . '</span><span class="btn-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 35 35">
  <g id="Arrow_Up" data-name="Arrow Up" transform="translate(35) rotate(90)">
    <path id="Tracé_10" data-name="Tracé 10" d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" transform="translate(0 35) rotate(-90)" fill="#141b42"/>
  </g>
</svg>
</span>';
            break;

        case 'my-btn-tertiary':
            // Texte simple
            $content = $text;
            break;

        case 'my-btn-fourth':
            // Avec flèche →
            $content = '<span class="btn-text">' . $text . '</span><span class="btn-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 35 35">
  <g id="Arrow_Up" data-name="Arrow Up" transform="translate(35) rotate(90)">
    <path id="Tracé_10" data-name="Tracé 10" d="M17.5,0,14.318,3.182,26.364,15.227H0v4.545H26.364L14.318,31.818,17.5,35,35,17.5Z" transform="translate(0 35) rotate(-90)" fill="#141b42"></path>
  </g>
</svg>
</span>';
            break;

        case 'my-btn-gold':
            // Avec losanges ◆
            $content = '<span class="btn-diamond">◆</span>' . $text . '<span class="btn-diamond">◆</span>';
            break;

        default:
            $content = $text;
            break;
    }

    // Si type="submit" ou type="button", générer un <button>
    if ($type === 'submit' || $type === 'button') {
        return sprintf(
            '<button type="%s" class="%s" %s>%s</button>',
            $type,
            $class,
            $id,
            $content
        );
    }

    // Sinon, générer un <a> (comportement par défaut)
    return sprintf(
        '<a href="%s" target="%s" class="%s" %s>%s</a>',
        $link,
        $target,
        $class,
        $id,
        $content
    );
}
add_shortcode('mrds_button', 'mrdstheme_button_shortcode');