document.addEventListener('DOMContentLoaded', function () {

    if (!window.RestaurantRemisesConfig) {
        console.error('RestaurantRemisesConfig manquant');
        return;
    }

    const apiUrl = RestaurantRemisesConfig.restUrl;
    const nonce = RestaurantRemisesConfig.nonce;
    const restaurantId = RestaurantRemisesConfig.restaurantId;
    // --- DOM ---
    const rowsTbody = document.getElementById('remises-rows');
    const form = document.getElementById('remise-form');
    const formTitle = document.getElementById('remise-form-title');
    const msgBox = document.getElementById('remise-message');
    const btnReset = document.getElementById('remise-reset-btn');
const serviceDejeuner = document.getElementById('service-dejeuner');
const serviceDiner = document.getElementById('service-diner');
    // Champs
    const fieldId = document.getElementById('remise-id');
    const fieldTitle = document.getElementById('remise-title');
    const fieldValRem = document.getElementById('valeur-remise');
    const fieldValMax = document.getElementById('valeur-max-remise');
    const fieldDescInt = document.getElementById('description-interne');

    const fieldDateDebut = document.getElementById('date-debut');
    const fieldDateFin = document.getElementById('date-fin');
    const checkJours = document.querySelectorAll('.jours-semaine');

    const fieldMinCouv = document.getElementById('min-couverts');
    const fieldMaxCouv = document.getElementById('max-couverts');
    const fieldMontantMin = document.getElementById('montant-min-commande');

    const fieldScope = document.getElementById('scope-remise');
    const fieldCatProd = document.getElementById('categories-produits');
    const fieldProdCib = document.getElementById('produits-cibles');
    const fieldMenuConc = document.getElementById('menu-concerne');
    const fieldRestaurantId = document.getElementById('restaurant-id');

    if (!form || !rowsTbody) {
        return;
    }

    // -------- Helpers --------

    function showMessage(text, type = 'success') {
        msgBox.textContent = text;
        msgBox.style.color = (type === 'error') ? 'red' : 'green';
        if (text) {
            setTimeout(() => { msgBox.textContent = ''; }, 4000);
        }
    }

    function resetForm() {
        fieldId.value = '';
        fieldTitle.value = '';
        fieldValRem.value = '';
        fieldValMax.value = '500';
        fieldDescInt.value = '';

        fieldDateDebut.value = '';
        fieldDateFin.value = '';
        checkJours.forEach(c => c.checked = false);

        if (serviceDejeuner) serviceDejeuner.checked = false;
        if (serviceDiner) serviceDiner.checked = false;

        formTitle.textContent = 'Ajouter une remise';
    }

 

    // Conversion date : yyyy-mm-dd (input) -> d/m/Y (ACF)
function toAcfDate(value) {
    if (!value) return '';
    const [y, m, d] = value.split('-');
    if (!y || !m || !d) return '';
    return `${d}/${m}/${y}`;
}

    // Conversion date : d/m/Y (ACF) -> yyyy-mm-dd (input)
    function fromAcfDate(value) {
        if (!value) return '';
        const parts = value.split('/');
        if (parts.length !== 3) return '';
        const [d, m, y] = parts;
        return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
    }

    function renderRemises(remises) {
        rowsTbody.innerHTML = '';

        if (!remises || remises.length === 0) {
            rowsTbody.innerHTML = '<tr><td colspan="8">Aucune remise pour le moment.</td></tr>';
            return;
        }

        remises.forEach(remise => {
            const tr = document.createElement('tr');

            const tdTitle = document.createElement('td');
            tdTitle.textContent = remise.title || '';

const tdActive = document.createElement('td');
const toggleId = 'toggle-remise-' + remise.id;
tdActive.innerHTML = `
    <label class="mrds-toggle" for="${toggleId}" title="${remise.remise_active ? 'Désactiver' : 'Activer'}">
        <input type="checkbox" id="${toggleId}" ${remise.remise_active ? 'checked' : ''}>
        <span class="mrds-toggle-slider"></span>
    </label>
`;
tdActive.querySelector('input').addEventListener('change', () => {
    swapActiveStatus(remise.id);
});
            const tdType = document.createElement('td');
            tdType.textContent = remise.type_de_remise_label || remise.type_de_remise || '';

            const tdVal = document.createElement('td');
            if (remise.valeur_de_la_remise != null) {
                tdVal.textContent = remise.valeur_de_la_remise;
            } else {
                tdVal.textContent = '-';
            }

            const tdPeriode = document.createElement('td');
            tdPeriode.textContent = (remise.date_debut || '') + ' → ' + (remise.date_fin || '');

            const tdJours = document.createElement('td');
            if (Array.isArray(remise.jours_semaine)) {
                tdJours.textContent = remise.jours_semaine.join(', ');
            } else {
                tdJours.textContent = '-';
            }

            const tdScope = document.createElement('td');
            tdScope.textContent = remise.remise_text || '';

            const tdActions = document.createElement('td');

const btnEdit = document.createElement('button');
btnEdit.type = 'button';
btnEdit.className = 'btn btn-sm btn-outline-primary';
btnEdit.innerHTML = '<i class="fa-solid fa-pen"></i>';
btnEdit.setAttribute('data-bs-toggle', 'tooltip');
btnEdit.setAttribute('data-bs-placement', 'top');
btnEdit.setAttribute('data-bs-title', 'Modifier la remise');
btnEdit.addEventListener('click', () => fillForm(remise));

const btnDelete = document.createElement('button');
btnDelete.type = 'button';
btnDelete.className = 'btn btn-sm btn-outline-danger';
btnDelete.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
btnDelete.setAttribute('data-bs-toggle', 'tooltip');
btnDelete.setAttribute('data-bs-placement', 'top');
btnDelete.setAttribute('data-bs-title', 'Supprimer la remise');
btnDelete.addEventListener('click', () => {
    if (confirm('Supprimer cette remise ?')) {
        deleteRemise(remise.id);
    }
});
            const divactions = document.createElement('div');
            divactions.className = 'd-flex gap-1 justify-content-center';

            divactions.appendChild(btnEdit);
            divactions.appendChild(btnDelete);

            tdActions.appendChild(divactions);

            tr.appendChild(tdTitle);
            tr.appendChild(tdActive);
            tr.appendChild(tdScope);
            tr.appendChild(tdActions);

            rowsTbody.appendChild(tr);
        });

        if (window.bootstrap && bootstrap.Tooltip) {
    document
        .querySelectorAll('#restaurant-remises-app [data-bs-toggle="tooltip"]')
        .forEach(function (el) {
            const inst = bootstrap.Tooltip.getInstance(el);
            if (inst) inst.dispose();
            new bootstrap.Tooltip(el);
        });
}
    }

    function fillForm(remise) {
        resetForm();

        fieldId.value = remise.id;
        fieldTitle.value = remise.title || '';
        if (remise.valeur_de_la_remise != null) fieldValRem.value = remise.valeur_de_la_remise;
        fieldValMax.value = (remise.valeur_max_remise != null) ? remise.valeur_max_remise : '500';
        fieldDescInt.value = remise.description_interne || '';

        fieldDateDebut.value = fromAcfDate(remise.date_debut || '');
        fieldDateFin.value = fromAcfDate(remise.date_fin || '');

        if (Array.isArray(remise.jours_semaine)) {
            checkJours.forEach(c => {
                c.checked = remise.jours_semaine.includes(c.value);
            });
        }
        const srv = Array.isArray(remise.services) ? remise.services : [];
        if (serviceDejeuner) serviceDejeuner.checked = srv.includes('dejeuner');
        if (serviceDiner) serviceDiner.checked = srv.includes('diner');

        formTitle.textContent = 'Modifier la remise #' + remise.id;
    }

    // --------- API calls ---------

    function fetchRemises() {
        fetch(apiUrl + '?restaurant_id=' + restaurantId, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': nonce,
                'Content-Type': 'application/json',
            },
        })
            .then(r => r.json())
            .then(data => {
                renderRemises(data);
            })
            .catch(err => {
                console.error(err);
                showMessage('Erreur lors du chargement des remises', 'error');
            });
    }

    function saveRemise(e) {
        e.preventDefault();

        const id = fieldId.value.trim();

        // Récup jours semaine
        const jours = [];
        checkJours.forEach(c => {
            if (c.checked) jours.push(c.value);
        });

// Récup services (Déjeuner / Dîner)
const services = [];
if (serviceDejeuner && serviceDejeuner.checked) services.push('dejeuner');
if (serviceDiner && serviceDiner.checked) services.push('diner');

        const payload = {
            title: fieldTitle.value,
            type_de_remise: 21, // toujours pourcentage
            valeur_de_la_remise: fieldValRem.value ? parseFloat(fieldValRem.value) : null,
            valeur_max_remise: fieldValMax.value ? parseFloat(fieldValMax.value) : null,
            description_interne: fieldDescInt.value || '',

            date_debut: toAcfDate(fieldDateDebut.value),
            date_fin: toAcfDate(fieldDateFin.value),
            jours_semaine: jours,
            services: services,
            nombre_minimum_de_couverts: null,
            nombre_maximum_de_couverts: null,
            montant_minimum_commande: null,
            restaurant_id: fieldRestaurantId.value ? fieldRestaurantId.value : null,
            scope_remise: 'whole_order',
            categories_produits: '',
            produits_cibles: '',
            menu_concerne: '',
        };

        const method = id ? 'PUT' : 'POST';
        const url = id ? `${apiUrl}/${id}` : apiUrl;

        fetch(url, {
            method: method,
            headers: {
                'X-WP-Nonce': nonce,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(data => {
                if (data && data.success === false) {
                    console.error(data);
                    showMessage('Erreur lors de l’enregistrement de la remise', 'error');
                    return;
                }
                showMessage('Remise enregistrée');
                resetForm();
                fetchRemises();
            })
            .catch(err => {
                console.error(err);
                showMessage('Erreur lors de l’enregistrement de la remise', 'error');
            });
    }

    function deleteRemise(id) {
        const url = `${apiUrl}/${id}`;

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': nonce,
                'Content-Type': 'application/json',
            },
        })
            .then(r => r.json())
            .then(data => {
                showMessage('Remise supprimée');
                fetchRemises();
            })
            .catch(err => {
                console.error(err);
                showMessage('Erreur lors de la suppression', 'error');
            });
    }


    function swapActiveStatus(id) {
        const url = `${apiUrl}/${id}`;
        const payload = {
            // Général (ACF noms exacts)
            id: id,
            restaurant_id: restaurantId,
        };
        fetch(url, {
            method: 'PATCH',
            headers: {
                'X-WP-Nonce': nonce,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
            .then(r => r.json())
            .then(data => {
                showMessage('Remise mise à jour');
                fetchRemises();
            })
            .catch(err => {
                console.error(err);
                showMessage('Erreur lors de la mise à jour ', 'error');
            });

    }
    // --------- Events ---------
    form.addEventListener('submit', saveRemise);
    btnReset.addEventListener('click', resetForm);

    // init
    fetchRemises();
});