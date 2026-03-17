/**
 * Animations - Section Chiffres
 * 
 * - Fade in + slide up au scroll
 * - Compteur animé pour les chiffres
 * 
 * @package mrdstheme
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ================================
    // INTERSECTION OBSERVER
    // Détecte quand la section entre dans le viewport
    // ================================
    
    const sectionChiffres = document.querySelector('.section-chiffres');
    
    if (!sectionChiffres) return;
    
    const chiffreItems = sectionChiffres.querySelectorAll('.chiffre-item');
    const chiffreNumbers = sectionChiffres.querySelectorAll('.chiffre-number');
    
    // Variable pour éviter de relancer l'animation
    let hasAnimated = false;
    
    // Options de l'observer
    const observerOptions = {
        root: null, // viewport
        rootMargin: '0px',
        threshold: 0.3 // Déclenche quand 30% de la section est visible
    };
    
    // Callback de l'observer
    const observerCallback = (entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !hasAnimated) {
                hasAnimated = true;
                
                // Ajouter la classe .animate à chaque item
                chiffreItems.forEach(item => {
                    item.classList.add('animate');
                });
                
                // Lancer l'animation des compteurs
                chiffreNumbers.forEach(number => {
                    animateCounter(number);
                });
            }
        });
    };
    
    // Créer et lancer l'observer
    const observer = new IntersectionObserver(observerCallback, observerOptions);
    observer.observe(sectionChiffres);
    
    
    // ================================
    // ANIMATION COMPTEUR
    // Anime de 0 jusqu'à la valeur finale
    // ================================
    
    function animateCounter(element) {
        const text = element.textContent.trim();
        
        // Extraire le nombre et le suffixe (ex: "50+" → 50, "+")
        const match = text.match(/^(\d+)(.*)$/);
        
        if (!match) return;
        
        const targetNumber = parseInt(match[1], 10);
        const suffix = match[2] || ''; // "+", "%", " restaurants", etc.
        
        const duration = 1500; // Durée en ms
        const startTime = performance.now();
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing: ease-out (décélération)
            const easeOut = 1 - Math.pow(1 - progress, 3);
            
            const currentNumber = Math.floor(easeOut * targetNumber);
            element.textContent = currentNumber + suffix;
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                // S'assurer d'afficher le nombre final exact
                element.textContent = targetNumber + suffix;
            }
        }
        
        // Démarrer à 0
        element.textContent = '0' + suffix;
        requestAnimationFrame(updateCounter);
    }
    
});