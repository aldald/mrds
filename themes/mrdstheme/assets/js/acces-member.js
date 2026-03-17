// Popup Accès Membre / Restaurateur + AJAX Login/Register
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // CONFIG - URL AJAX (fonctionne toujours)
    // ============================================
    
    // Récupérer l'URL AJAX depuis la variable globale ou la construire
    var ajaxUrl = (typeof MRDS_Auth !== 'undefined' && MRDS_Auth.ajax_url) 
        ? MRDS_Auth.ajax_url 
        : '/wp-admin/admin-ajax.php';
    
    // ============================================
    // POPUP GESTION
    // ============================================
    
    const memberPopup = document.getElementById('memberPopup');
    const closeMemberPopup = document.getElementById('closeMemberPopup');
    const closeMemberPopupBack = document.getElementById('closeMemberPopupBack');
    const popupSubtitle = document.querySelector('.member-popup-subtitle');
    const body = document.body;
    
    // Liens pour ouvrir le popup
    const memberButtons = document.querySelectorAll('a[href="#acces-membre"], a[href*="acces-membre"]');
    const restaurateurButtons = document.querySelectorAll('a[href="#acces-restaurateur"], a[href*="acces-restaurateur"]');
    
    // Ouvrir popup - Accès Membre
    if (memberButtons.length > 0) {
        memberButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                openPopup('Membre');
            });
        });
    }
    
    // Ouvrir popup - Accès Restaurateur
    if (restaurateurButtons.length > 0) {
        restaurateurButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                openPopup('Restaurateur');
            });
        });
    }
    
    // Fermer le popup - Bouton X
    if (closeMemberPopup) {
        closeMemberPopup.addEventListener('click', function() {
            closePopup();
        });
    }
    
    // Fermer le popup - Lien retour
    if (closeMemberPopupBack) {
        closeMemberPopupBack.addEventListener('click', function(e) {
            e.preventDefault();
            closePopup();
        });
    }
    
    // Fermer le popup - Clic sur l'overlay
    if (memberPopup) {
        memberPopup.addEventListener('click', function(e) {
            if (e.target === memberPopup) {
                closePopup();
            }
        });
    }
    
    // Fermer le popup - Touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && memberPopup && memberPopup.classList.contains('active')) {
            closePopup();
        }
    });
    
    function openPopup(type) {
        if (memberPopup) {
            if (popupSubtitle) {
                popupSubtitle.innerHTML = 'Vous ne possédez pas de compte ' + type + ' ? <a href="/nous-rejoindre">Cliquez ici</a>';
            }
            memberPopup.style.display = 'flex';
            setTimeout(function() {
                memberPopup.classList.add('active');
            }, 10);
            body.classList.add('popup-open');
        }
    }
    
    function closePopup() {
        if (memberPopup) {
            memberPopup.classList.remove('active');
            setTimeout(function() {
                memberPopup.style.display = 'none';
            }, 300);
            body.classList.remove('popup-open');
        }
    }
    
    // ============================================
    // FORMULAIRE INSCRIPTION (AJAX)
    // ============================================
    
    const registerForm = document.getElementById('mrds-register-form');
    
    if (registerForm) {
        
        console.log('Formulaire inscription trouvé'); // Debug
        
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('Submit intercepté'); // Debug
            
            const submitBtn = registerForm.querySelector('button[type="submit"]');
            const messagesDiv = document.getElementById('register-messages');
            const nonceField = document.getElementById('mrds_register_nonce');
            
            if (!nonceField) {
                console.error('Nonce non trouvé');
                return;
            }
            
            const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
            
            // Désactiver le bouton
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="btn-diamond">◆</span> Chargement... <span class="btn-diamond">◆</span>';
            }
            
            // Vider les messages
            if (messagesDiv) {
                messagesDiv.innerHTML = '';
                messagesDiv.className = 'form-messages';
            }
            
            // Construire les données
            const formData = new FormData(registerForm);
            formData.append('action', 'mrds_register');
            formData.append('nonce', nonceField.value);
            
            console.log('Envoi AJAX vers:', ajaxUrl); // Debug
            
            // Appel AJAX
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function(response) {
                console.log('Réponse reçue:', response.status); // Debug
                return response.json();
            })
            .then(function(data) {
                console.log('Data:', data); // Debug
                
                if (data.success) {
                    // Succès
                    if (messagesDiv) {
                        messagesDiv.innerHTML = '<div class="message success">' + data.data.message + '</div>';
                        messagesDiv.className = 'form-messages show';
                    }
                    
                    // Rediriger vers checkout
                    setTimeout(function() {
                        window.location.href = data.data.redirect;
                    }, 1500);
                    
                } else {
                    // Erreur
                    if (messagesDiv) {
                        messagesDiv.innerHTML = '<div class="message error">' + data.data.message + '</div>';
                        messagesDiv.className = 'form-messages show';
                    }
                    
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                }
            })
            .catch(function(error) {
                console.error('Erreur AJAX:', error);
                
                if (messagesDiv) {
                    messagesDiv.innerHTML = '<div class="message error">Une erreur est survenue. Veuillez réessayer.</div>';
                    messagesDiv.className = 'form-messages show';
                }
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        });
    } else {
        console.log('Formulaire mrds-register-form non trouvé sur cette page');
    }
    
// ============================================
// FORMULAIRE CONNEXION (AJAX)
// ============================================

const loginForm = document.getElementById('mrds-login-form');

if (loginForm) {
    
    console.log('Formulaire connexion trouvé'); // Debug
    
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const messagesDiv = document.getElementById('login-messages');
        const nonceField = document.getElementById('mrds_login_nonce');
        
        if (!nonceField) {
            console.error('Nonce login non trouvé');
            return;
        }
        
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="btn-diamond">◆</span> Connexion... <span class="btn-diamond">◆</span>';
        }
        
        if (messagesDiv) {
            messagesDiv.innerHTML = '';
            messagesDiv.className = 'form-messages';
        }
        
        const formData = new FormData(loginForm);
        formData.append('action', 'mrds_login');
        formData.append('nonce', nonceField.value);
        
        const rememberCheckbox = loginForm.querySelector('input[name="remember"]');
        formData.append('remember', rememberCheckbox && rememberCheckbox.checked ? 'true' : 'false');
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                if (messagesDiv) {
                    messagesDiv.innerHTML = '<div class="message success">' + data.data.message + '</div>';
                    messagesDiv.className = 'form-messages show';
                }
                
                // Redirection selon le rôle (au lieu de reload)
                setTimeout(function() {
                    window.location.href = data.data.redirect;
                }, 1000);
                
            } else {
                if (messagesDiv) {
                    messagesDiv.innerHTML = '<div class="message error">' + data.data.message + '</div>';
                    messagesDiv.className = 'form-messages show';
                }
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            }
        })
        .catch(function(error) {
            console.error('Erreur:', error);
            
            if (messagesDiv) {
                messagesDiv.innerHTML = '<div class="message error">Une erreur est survenue.</div>';
                messagesDiv.className = 'form-messages show';
            }
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    });
}
    
});