/**
 * Header Sticky - Gestion du scroll
 * 
 * Logique :
 * - Header transparent → au scroll devient bleu + sticky
 * - Header bleu → au scroll reste bleu + sticky
 * 
 * @package mrdstheme
 */

document.addEventListener('DOMContentLoaded', function() {
    
    const header = document.querySelector('.site-header');
    const body = document.body;
    
    if (!header) return;
    
    // Déterminer le type de header initial
    const isTransparent = header.classList.contains('header-transparent');
    const isBlue = header.classList.contains('header-blue');
    
    // Ajouter une classe au body pour identifier le type de header
    if (isBlue) {
        body.classList.add('header-type-blue');
    } else if (isTransparent) {
        body.classList.add('header-type-transparent');
    }
    
    // Seuil de scroll pour activer le sticky (en pixels)
    const scrollThreshold = 50;
    
    // Calculer la hauteur du header pour la compensation
    let headerHeight = header.offsetHeight;
    
    // Variable pour éviter les recalculs inutiles
    let ticking = false;
    let isSticky = false;
    
    /**
     * Gérer le scroll
     */
    function handleScroll() {
        const currentScrollY = window.scrollY;
        
        if (currentScrollY > scrollThreshold) {
            // On a scrollé → Activer le sticky
            if (!isSticky) {
                header.classList.add('header-sticky');
                body.classList.add('header-is-sticky');
                isSticky = true;
                
                // Si c'était transparent, on ajoute la classe pour le transformer en bleu
                if (isTransparent) {
                    header.classList.add('header-scrolled');
                }
            }
        } else {
            // On est en haut → Désactiver le sticky
            if (isSticky) {
                header.classList.remove('header-sticky');
                body.classList.remove('header-is-sticky');
                isSticky = false;
                
                // Si c'était transparent, on retire la classe pour revenir transparent
                if (isTransparent) {
                    header.classList.remove('header-scrolled');
                }
            }
        }
        
        ticking = false;
    }
    
    /**
     * Optimisation avec requestAnimationFrame
     */
    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                handleScroll();
            });
            ticking = true;
        }
    }
    
    /**
     * Recalculer la hauteur du header au resize
     */
    function onResize() {
        headerHeight = header.offsetHeight;
        
        // Mettre à jour la variable CSS si nécessaire
        document.documentElement.style.setProperty('--header-height', headerHeight + 'px');
    }
    
    // Écouter le scroll
    window.addEventListener('scroll', onScroll, { passive: true });
    
    // Écouter le resize pour recalculer la hauteur
    window.addEventListener('resize', onResize, { passive: true });
    
    // Initialiser la variable CSS
    document.documentElement.style.setProperty('--header-height', headerHeight + 'px');
    
    // Vérifier l'état initial (au cas où la page est chargée en milieu de page)
    handleScroll();
    
});