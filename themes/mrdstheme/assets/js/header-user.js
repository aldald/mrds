// Header User Dropdown Toggle
document.addEventListener('DOMContentLoaded', function() {
    
    // ================================
    // DROPDOWN UTILISATEUR CONNECTÉ
    // ================================
    
    const headerUserSections = document.querySelectorAll('.header-user-section.logged-in');
    
    headerUserSections.forEach(function(section) {
        const btn = section.querySelector('.header-user-btn');
        
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Fermer les autres dropdowns
                headerUserSections.forEach(function(other) {
                    if (other !== section) {
                        other.classList.remove('active');
                        const otherBtn = other.querySelector('.header-user-btn');
                        if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // Toggle le dropdown actuel
                section.classList.toggle('active');
                const isExpanded = section.classList.contains('active');
                this.setAttribute('aria-expanded', isExpanded);
            });
        }
    });
    
    // Fermer dropdown si clic en dehors
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header-user-section.logged-in')) {
            headerUserSections.forEach(function(section) {
                section.classList.remove('active');
                const btn = section.querySelector('.header-user-btn');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        }
    });
    
    // Fermer dropdown avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            headerUserSections.forEach(function(section) {
                section.classList.remove('active');
                const btn = section.querySelector('.header-user-btn');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        }
    });
    
    // ================================
    // ANCIEN DROPDOWN (Mobile - Non connecté)
    // ================================
    
// Empêche double initialisation si le fichier est chargé 2 fois
if (!window.__MRDS_MOBILE_DROPDOWN_INIT__) {
  window.__MRDS_MOBILE_DROPDOWN_INIT__ = true;

  // Event delegation: fonctionne même si le header est injecté après
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.user-dropdown .user-btn');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    const dropdown = btn.closest('.user-dropdown');
    if (!dropdown) return;

    dropdown.classList.toggle('active');
    btn.setAttribute('aria-expanded', dropdown.classList.contains('active') ? 'true' : 'false');
  }, true); // <- capture = true pour passer avant les scripts qui ferment

  // Fermer au clic dehors
  document.addEventListener('click', function (e) {
    document.querySelectorAll('.user-dropdown.active').forEach(function (dropdown) {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
        const btn = dropdown.querySelector('.user-btn');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }
    });
  });
}



});