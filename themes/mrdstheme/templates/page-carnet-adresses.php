<?php
/**
 * Template Name: Page Carnet d'adresses
 * Fichier: templates/page-carnet-adresses.php
 * 
 * Affiche le carnet d'adresses pour tous (visiteurs et membres)
 * Redirige les restaurateurs vers leur espace de gestion
 */

// Si restaurateur ou super_restaurateur → Redirection
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $user_roles = $current_user->roles;
    
    if (in_array('restaurateur', $user_roles) || in_array('super_restaurateur', $user_roles)) {
        wp_redirect(home_url('/gestion-reservations/'));
        exit;
    }
}

// Pour tous les autres (visiteurs + membres) → Template visiteurs
get_template_part('templates/carnet-adresses/page-carnet-adresses', 'visiteurs');