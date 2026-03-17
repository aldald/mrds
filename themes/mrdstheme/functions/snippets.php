<?php
/**
 * SNIPPET À AJOUTER DANS functions.php
 * 
 * Ce code charge le fichier JavaScript carnet-slider.js
 * uniquement sur la page carnet d'adresses
 */

function mrdstheme_enqueue_carnet_assets() {
    // Charger uniquement sur la page carnet d'adresses
    if (is_page_template('template-carnet-adresses.php')) {
        wp_enqueue_script(
            'carnet-slider',
            get_template_directory_uri() . '/assets/js/carnet-slider.js',
            ['jquery'],
            filemtime(get_template_directory() . '/assets/js/carnet-slider.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'mrdstheme_enqueue_carnet_assets');





// function pour afficher les filtres de recherche ( la formulaire de recherche )
function mrdstheme_get_search($filters = [], $is_home = false)
{

    $filters = MRDS_Gestion_Restaurant::get_instance()->get_restaurant_filter_options();
    if(!$is_home){
        ob_start();
        ?>
        <div class="carnet-filters">
            <div class="filter-dropdown">
                <button class="filter-btn" type="button">
                    <span>De quoi avez-vous envie ?</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6">
                        <path d="M5 6L0 0h10z" fill="#9e744d" />
                    </svg>
                </button>
                <div class="filter-menu">
                    <a href="#" data-filter="all">Tous</a>
                    <a href="#" data-filter="vegetarien">Végétarien</a>
                    <a href="#" data-filter="etoile">Étoilé</a>
                    <a href="#" data-filter="bistronomique">Bistronomique</a>
                    <a href="#" data-filter="gastronomique">Gastronomique</a>
                </div>
            </div>

            <div class="filter-dropdown">
                <button class="filter-btn" type="button">
                    <span>Dans quel arrondissement ?</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6">
                        <path d="M5 6L0 0h10z" fill="#9e744d" />
                    </svg>
                </button>
                <div class="filter-menu">
                    <a href="#" data-filter="all">Tous</a>
                    <a href="#" data-filter="1er">Paris 1er</a>
                    <a href="#" data-filter="2e">Paris 2e</a>
                    <a href="#" data-filter="3e">Paris 3e</a>
                    <a href="#" data-filter="4e">Paris 4e</a>
                    <a href="#" data-filter="5e">Paris 5e</a>
                    <a href="#" data-filter="6e">Paris 6e</a>
                    <a href="#" data-filter="7e">Paris 7e</a>
                    <a href="#" data-filter="8e">Paris 8e</a>
                </div>
            </div>

            <div class="filter-dropdown">
                <button class="filter-btn" type="button">
                    <span>Quelle réduction ?</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="6" viewBox="0 0 10 6">
                        <path d="M5 6L0 0h10z" fill="#9e744d" />
                    </svg>
                </button>
                <div class="filter-menu">
                    <a href="#" data-filter="all">Toutes</a>
                    <a href="#" data-filter="10">-10%</a>
                    <a href="#" data-filter="15">-15%</a>
                    <a href="#" data-filter="20">-20%</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}