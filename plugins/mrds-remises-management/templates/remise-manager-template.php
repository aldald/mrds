<div id="restaurant-remises-app" class="container my-4">
    <div class="mb-3">
        <a href="<?php echo esc_url(home_url('/gestion-restaurant/')); ?>" class="btn btn-sm btn-return">
            ← Retour à la gestion des restaurants
        </a>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="mb-0">Gestion des remises pour <?php echo get_the_title($_GET['restaurant_id']); ?></h2>
        <small class="text-muted">Création, modification et suppression de vos remises</small>
    </div>

    <!-- Liste des remises -->
    <div id="remises-list" class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mes remises</h5>
            <button type="button" id="remise-reset-btn" class="btn btn-sm btn-primary">
                + Nouvelle remise
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Titre</th>
                            <th>Active</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="remises-rows">
                        <!-- rempli en JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulaire Remise -->
    <div id="remise-form-wrapper" class="card">
        <div class="card-header">
            <h5 id="remise-form-title" class="mb-0">Ajouter une remise</h5>
        </div>
        <div class="card-body">
            <form id="remise-form">
                <input type="hidden" id="remise-id" value="">
                <input type="hidden" id="restaurant-id" value="<?php echo esc_attr($_GET['restaurant_id'] ?? ''); ?>">
                <!-- ========== Remise – Général ========== -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Général</legend>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="remise-title" class="form-label">Titre de la remise</label>
                            <input type="text" id="remise-title" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label for="valeur-remise" class="form-label">% de la remise</label>
                            <input type="number" id="valeur-remise" class="form-control" step="0.01">
                        </div>

                        <div class="col-md-3">
                            <label for="valeur-max-remise" class="form-label">Remise max TTC (en €)</label>
                            <input type="number" id="valeur-max-remise" class="form-control" step="0.01" value="500">
                        </div>

                        <div class="col-md-12">
                            <label for="description-interne" class="form-label">Note interne</label>
                            <textarea id="description-interne" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                </fieldset>

                <!-- ========== Conditions d’application ========== -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Conditions d’application</legend>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="date-debut" class="form-label">Date de début</label>
                            <input type="date" id="date-debut" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label for="date-fin" class="form-label">Date de fin</label>
                            <input type="date" id="date-fin" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <span class="form-label d-block mb-1">Jours de la semaine</span>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="form-check">
                                    <input class="form-check-input jours-semaine" type="checkbox" value="mon"
                                        id="jour-mon">
                                    <label class="form-check-label" for="jour-mon">Lundi</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input jours-semaine" type="checkbox" value="tue"
                                        id="jour-tue">
                                    <label class="form-check-label" for="jour-tue">Mardi</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input jours-semaine" type="checkbox" value="wed"
                                        id="jour-wed">
                                    <label class="form-check-label" for="jour-wed">Mercredi</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input jours-semaine" type="checkbox" value="thu"
                                        id="jour-thu">
                                    <label class="form-check-label" for="jour-thu">Jeudi</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input jours-semaine" type="checkbox" value="fri"
                                        id="jour-fri">
                                    <label class="form-check-label" for="jour-fri">Vendredi</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input jours-semaine" type="checkbox" value="sat"
                                        id="jour-sat">
                                    <label class="form-check-label" for="jour-sat">Samedi</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input jours-semaine" type="checkbox" value="sun"
                                        id="jour-sun">
                                    <label class="form-check-label" for="jour-sun">Dimanche</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Services</label>

                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input custom-margin" type="checkbox" id="service-dejeuner" value="dejeuner">
                                    <label class="form-check-label" for="service-dejeuner">Déjeuner</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="service-diner" value="diner">
                                    <label class="form-check-label" for="service-diner">Dîner</label>
                                </div>
                            </div>

                            <div class="form-text">Choisissez le(s) service(s) concerné(s).</div>
                        </div>

                        <div class="col-md-4">
                            <label for="min-couverts" class="form-label" style="display:none">Nombre minimum de couverts</label>
                            <input type="number" id="min-couverts" class="form-control" min="0" style="display:none">
                        </div>

                        <div class="col-md-4">
                            <label for="max-couverts" class="form-label" style="display:none">Nombre maximum de couverts</label>
                            <input type="number" id="max-couverts" class="form-control" min="0" style="display:none">
                        </div>

                        <div class="col-md-4">
                            <label for="montant-min-commande" class="form-label" style="display:none">Montant minimum de commande (€)</label>
                            <input type="number" id="montant-min-commande" class="form-control" step="0.01" style="display:none">
                        </div>
                    </div>
                </fieldset>

                <!-- Champs scope cachés pour compatibilité payload -->
                <input type="hidden" id="scope-remise" value="whole_order">
                <input type="hidden" id="categories-produits" value="">
                <input type="hidden" id="produits-cibles" value="">
                <input type="hidden" id="menu-concerne" value="">

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" id="remise-save-btn" class="btn btn-success">
                        Enregistrer
                    </button>
                    <button type="button" id="remise-reset-btn" class="btn btn-outline-secondary">
                        Annuler / Nouvelle remise
                    </button>
                </div>
            </form>

            <div id="remise-message" class="mt-3"></div>
        </div>
    </div>
</div>
<style>
    .plage-row {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        margin-bottom: 0.35rem;
    }

    .plage-row input[type="time"] {
        max-width: 130px;
    }

    a.btn.btn-sm.btn-return {
        background: transparent;
        border: 2px solid #141b42;
        color: #141b42;
        padding: 10px 20px !important;
        font-size: 14px !important;
        font-weight: 600;
        border-radius: 0;
        transition: all 0.3s ease;
        box-shadow: none;
        text-decoration: none;
    }
</style>