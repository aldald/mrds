/**
 * MRDS Gestion Restaurateurs - JavaScript
 * 
 * Gestion CRUD des restaurateurs via REST API
 * 
 * @package mrds-gestion-restaurateurs
 */

(function () {
    'use strict';

    // Configuration passée par PHP
    const CONFIG = window.MRDSRestaurateurConfig || {};
    const REST_URL = CONFIG.restUrl || '';
    const NONCE = CONFIG.nonce || '';

    // Éléments DOM
    let formEl, tableBody, messageEl;
    let formTitleEl, saveBtn, resetBtn, newBtn;
    let inputId, inputFirstname, inputLastname, inputEmail, inputPhone;

    /**
     * ========================================
     * INITIALISATION
     * ========================================
     */
    function init() {
        // Récupérer les éléments DOM
        formEl = document.getElementById('mrds-restaurateur-form');
        tableBody = document.getElementById('mrds-restaurateurs-rows');
        messageEl = document.getElementById('mrds-restaurateur-message');
        formTitleEl = document.getElementById('mrds-restaurateur-form-title');
        saveBtn = document.getElementById('mrds-restaurateur-save-btn');
        resetBtn = document.getElementById('mrds-restaurateur-reset-btn');
        newBtn = document.getElementById('mrds-restaurateur-new-btn');

        inputId = document.getElementById('mrds-restaurateur-id');
        inputFirstname = document.getElementById('mrds-restaurateur-firstname');
        inputLastname = document.getElementById('mrds-restaurateur-lastname');
        inputEmail = document.getElementById('mrds-restaurateur-email');
        inputPhone = document.getElementById('mrds-restaurateur-phone');

        // Vérifier que les éléments existent
        if (!formEl || !tableBody) {
            console.error('MRDS Restaurateurs: Éléments DOM introuvables');
            return;
        }

        // Événements
        formEl.addEventListener('submit', handleSubmit);
        resetBtn.addEventListener('click', resetForm);
        newBtn.addEventListener('click', function () {
            resetForm();
            inputFirstname.focus();
        });

        // Charger la liste
        loadRestaurateurs();
    }

    /**
     * ========================================
     * CHARGER LA LISTE DES RESTAURATEURS
     * ========================================
     */
    async function loadRestaurateurs() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-3">Chargement…</td></tr>';

        try {
            const response = await fetch(REST_URL, {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': NONCE,
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Erreur lors du chargement');
            }

            const data = await response.json();
            renderTable(data);
        } catch (error) {
            console.error('MRDS Restaurateurs:', error);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-danger">Erreur lors du chargement</td></tr>';
        }
    }

    /**
     * ========================================
     * AFFICHER LE TABLEAU
     * ========================================
     */
    function renderTable(restaurateurs) {
        if (!restaurateurs || restaurateurs.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-3">Aucun restaurateur trouvé. Créez-en un !</td></tr>';
            return;
        }

        let html = '';

        restaurateurs.forEach(function (r) {
            const createdAt = r.created_at ? formatDate(r.created_at) : '-';

            html += `
                <tr data-id="${r.id}">
                    <td>${escapeHtml(r.first_name)}</td>
                    <td>${escapeHtml(r.last_name)}</td>
                    <td>${escapeHtml(r.email)}</td>
                    <td>${escapeHtml(r.phone) || '-'}</td>
                    <td>${createdAt}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary mrds-edit-btn" data-id="${r.id}">
                            Modifier
                        </button>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = html;

        // Ajouter les événements sur les boutons Modifier
        tableBody.querySelectorAll('.mrds-edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'), 10);
                const restaurateur = restaurateurs.find(function (r) {
                    return r.id === id;
                });
                if (restaurateur) {
                    editRestaurateur(restaurateur);
                }
            });
        });
    }

    /**
     * ========================================
     * ÉDITER UN RESTAURATEUR
     * ========================================
     */
    function editRestaurateur(r) {
        inputId.value = r.id;
        inputFirstname.value = r.first_name || '';
        inputLastname.value = r.last_name || '';
        inputEmail.value = r.email || '';
        inputPhone.value = r.phone || '';

        formTitleEl.textContent = 'Modifier le restaurateur';
        saveBtn.textContent = 'Mettre à jour';

        // Scroll vers le formulaire
        formEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        inputFirstname.focus();
    }

    /**
     * ========================================
     * SOUMISSION DU FORMULAIRE
     * ========================================
     */
    async function handleSubmit(e) {
        e.preventDefault();

        const id = inputId.value ? parseInt(inputId.value, 10) : null;
        const isEdit = id !== null && id > 0;

        const data = {
            first_name: inputFirstname.value.trim(),
            last_name: inputLastname.value.trim(),
            email: inputEmail.value.trim(),
            phone: inputPhone.value.trim(),
        };

        // Validation côté client
        if (!data.first_name) {
            showMessage('Le prénom est obligatoire.', 'danger');
            inputFirstname.focus();
            return;
        }

        if (!data.last_name) {
            showMessage('Le nom est obligatoire.', 'danger');
            inputLastname.focus();
            return;
        }

        if (!data.email) {
            showMessage('L\'email est obligatoire.', 'danger');
            inputEmail.focus();
            return;
        }

        if (!isValidEmail(data.email)) {
            showMessage('L\'adresse email n\'est pas valide.', 'danger');
            inputEmail.focus();
            return;
        }

        // Désactiver le bouton
        saveBtn.disabled = true;
        saveBtn.classList.add('loading');
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Enregistrement…';

        try {
            const url = isEdit ? `${REST_URL}/${id}` : REST_URL;
            const method = isEdit ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'X-WP-Nonce': NONCE,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Erreur lors de l\'enregistrement');
            }

            showMessage(result.message || 'Opération réussie !', 'success');
            resetForm();
            loadRestaurateurs();

        } catch (error) {
            console.error('MRDS Restaurateurs:', error);
            showMessage(error.message || 'Une erreur est survenue.', 'danger');
        } finally {
            saveBtn.disabled = false;
            saveBtn.classList.remove('loading');
            saveBtn.textContent = originalText;
        }
    }

    /**
     * ========================================
     * RÉINITIALISER LE FORMULAIRE
     * ========================================
     */
    function resetForm() {
        formEl.reset();
        inputId.value = '';
        formTitleEl.textContent = 'Ajouter un restaurateur';
        saveBtn.textContent = 'Enregistrer';
        hideMessage();
    }

    /**
     * ========================================
     * AFFICHER UN MESSAGE
     * ========================================
     */
    function showMessage(text, type) {
        if (!messageEl) return;

        const alertClass = type === 'success' ? 'alert-success' :
                          type === 'danger' ? 'alert-danger' :
                          type === 'warning' ? 'alert-warning' : 'alert-info';

        messageEl.innerHTML = `<div class="alert ${alertClass}">${escapeHtml(text)}</div>`;
        messageEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * ========================================
     * CACHER LE MESSAGE
     * ========================================
     */
    function hideMessage() {
        if (messageEl) {
            messageEl.innerHTML = '';
        }
    }

    /**
     * ========================================
     * HELPERS
     * ========================================
     */

    // Échapper le HTML
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Valider email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Formater la date
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        try {
            const date = new Date(dateStr);
            return date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            });
        } catch (e) {
            return dateStr;
        }
    }

    /**
     * ========================================
     * LANCEMENT
     * ========================================
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();