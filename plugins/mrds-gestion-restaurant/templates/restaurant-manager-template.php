<?php
// Variables fournies par render_restaurant_manager():
// $types_cuisine, $tags_restaurant, $owners, $restaurateurs
?>

<div id="mrds-restaurants-app" class="container my-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gestion des restaurants</h1>
        <small class="text-muted">Gestion des restaurants liés à votre compte</small>
    </div>

    <!-- Liste -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mes restaurants</h5>
            <?php if (current_user_can('administrator')) : ?>
                <button type="button" id="mrds-restaurant-new-btn" class="btn btn-sm btn-primary">
                    + Nouveau restaurant
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nom</th>
                            <th>Adresse</th>
                            <th>Type(s) de cuisine</th>
                            <th>Tags</th>
                            <th>Proprietaire</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mrds-restaurants-rows">
                        <tr>
                            <td colspan="6" class="text-center py-3">Chargement...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="card" id="mrds-restaurant-form-card"
        <?php if (!current_user_can('administrator')) : ?>
        style="display:none;"
        <?php endif; ?>>
        <div class="card-header">
            <h5 id="mrds-restaurant-form-title" class="mb-0">Ajouter un restaurant</h5>
        </div>
        <div class="card-body">
            <form id="mrds-restaurant-form">
                <input type="hidden" id="mrds-restaurant-id" value="">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="mrds-restaurant-title" class="form-label">Nom du restaurant</label>
                        <input type="text" class="form-control" id="mrds-restaurant-title" required>
                    </div>

                    <?php if (current_user_can('publish_restaurants')) : ?>
                        <div class="col-md-6">
                            <label for="mrds-restaurant-restaurateurs" class="form-label">Gere par</label>
                            <select id="mrds-restaurant-restaurateurs" class="form-select" multiple>
                                <?php foreach ($restaurateurs as $u) : ?>
                                    <option value="<?php echo esc_attr($u->ID); ?>">
                                        <?php echo esc_html($u->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Maintenez Ctrl/Cmd pour selectionner plusieurs.</div>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="my-4">

                <!-- Images -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Images</legend>

                    <div class="row g-3">
                        <!-- Image principale -->
                        <div class="col-md-4">
                            <label class="form-label">Photo principale</label>
                            <div id="mrds-image-preview" class="mb-2 mrds-image-preview-box" style="display:none;">
                                <img src="" alt="Preview">
                                <button type="button" id="mrds-image-remove" class="btn btn-sm btn-outline-danger mrds-img-remove-btn" title="Retirer">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                            <input type="file" class="form-control" id="mrds-restaurant-image" accept="image/*">
                            <div class="form-text">Image mise en avant</div>
                            <div id="mrds-image-upload-status" class="small mt-1"></div>
                        </div>

                        <!-- Galerie -->
                        <div class="col-md-8">
                            <label class="form-label">Galerie <small class="text-muted">(max 4 images)</small></label>
                            <div id="mrds-gallery-preview" class="d-flex flex-wrap gap-2"></div>
                            <input type="file" class="form-control" id="mrds-gallery-input" accept="image/*">
                            <div class="form-text">Ajoutez jusqu'a 4 images supplementaires</div>
                            <div id="mrds-gallery-upload-status" class="small mt-1"></div>
                        </div>
                    </div>
                </fieldset>

                <!-- Adresse -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Adresse</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="mrds-adresse-rue" class="form-label">Adresse (rue)</label>
                            <input type="text" id="mrds-adresse-rue" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="mrds-adresse-complement" class="form-label">Complement d'adresse</label>
                            <input type="text" id="mrds-adresse-complement" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label for="mrds-code-postal" class="form-label">Code postal</label>
                            <input type="text" id="mrds-code-postal" class="form-control">
                        </div>
                        <div class="col-md-5">
                            <label for="mrds-ville" class="form-label">Ville</label>
                            <input type="text" id="mrds-ville" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="mrds-arrondissement" class="form-label">Arrondissement</label>
                            <select id="mrds-arrondissement" class="form-select">
                                <option value="">--</option>
                                <?php for ($i = 1; $i <= 20; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <!-- Contact / web -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Contact</legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="mrds-telephone" class="form-label">Telephone</label>
                            <input type="text" id="mrds-telephone" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label for="mrds-site-web" class="form-label">Site web</label>
                            <input type="url" id="mrds-site-web" class="form-control" placeholder="https://">
                        </div>
                    </div>
                </fieldset>

                <!-- Taxonomies -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Typologie</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="mrds-type-cuisine" class="form-label">Type(s) de cuisine</label>
                            <select id="mrds-type-cuisine" class="form-select" multiple>
                                <?php foreach ($types_cuisine as $term) : ?>
                                    <option value="<?php echo esc_attr($term->term_id); ?>">
                                        <?php echo esc_html($term->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Maintenez Ctrl/Cmd pour plusieurs choix.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="mrds-tags-restaurant" class="form-label">Tags</label>
                            <select id="mrds-tags-restaurant" class="form-select" multiple>
                                <?php foreach ($tags_restaurant as $term) : ?>
                                    <option value="<?php echo esc_attr($term->term_id); ?>">
                                        <?php echo esc_html($term->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <!-- Contenus -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Contenus</legend>
                    <div class="mb-3">
                        <label for="mrds-description-menu" class="form-label">Description du menu</label>
                        <textarea id="mrds-description-menu" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="mrds-exemple-plats" class="form-label">Exemples de plats</label>
                        <textarea id="mrds-exemple-plats" class="form-control" rows="3"></textarea>
                    </div>

                    <!-- Description (nouveau) -->
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <div id="mrds-citation-description-text" class="form-control mrds-contenteditable" contenteditable="true"></div>
                    </div>

                    <!-- Citation + Auteur -->
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Citation / phrase d'accroche</label>
                            <div id="mrds-citation-description" class="form-control mrds-contenteditable" contenteditable="true"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="mrds-citation-auteur" class="form-label">Auteur de la citation</label>
                            <input type="text" id="mrds-citation-auteur" class="form-control">
                        </div>
                    </div>
                </fieldset>

                <!-- Tarifs -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Tarifs</legend>
                    <div id="mrds-tarifs-container" class="mb-2"></div>
                    <button type="button" id="mrds-add-tarif-btn" class="btn btn-sm btn-outline-secondary">
                        + Ajouter un tarif
                    </button>
                </fieldset>

                <!-- Horaires -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Horaires</legend>
                    <div id="mrds-horaires-container" class="mb-2"></div>
                    <button type="button" id="mrds-add-horaire-btn" class="btn btn-sm btn-outline-secondary">
                        + Ajouter une plage horaire
                    </button>
                    <div class="form-text">Ex : Midi - L, Ma, Me ; Soir - Je, Ve, Sa</div>
                </fieldset>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" id="mrds-restaurant-save-btn" class="btn btn-success">
                        Enregistrer
                    </button>
                    <button type="button" id="mrds-restaurant-reset-btn" class="btn btn-outline-secondary">
                        Annuler
                    </button>
                </div>
            </form>

            <div id="mrds-restaurant-message" class="mt-3"></div>
        </div>
    </div>
</div>

<style>
    .mrds-tarif-row,
    .mrds-horaire-row {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
        margin-bottom: .35rem;
    }

    .mrds-tarif-row input[type="text"],
    .mrds-tarif-row input[type="number"],
    .mrds-horaire-row select {
        max-width: 220px;
    }

    /* Images preview */
    .mrds-image-preview-box {
        position: relative;
        display: inline-block;
    }

    .mrds-image-preview-box img {
        max-width: 150px;
        max-height: 120px;
        object-fit: cover;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .mrds-img-remove-btn {
        position: absolute;
        top: -8px;
        right: -8px;
        padding: 2px 6px;
        font-size: 10px;
        border-radius: 50%;
    }

    /* Gallery preview */
    .mrds-gallery-item {
        position: relative;
        display: inline-block;
    }

    .mrds-gallery-item img {
        width: 100px;
        height: 80px;
        object-fit: cover;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .mrds-gallery-item .mrds-gallery-remove-btn {
        position: absolute;
        top: -6px;
        right: -6px;
        padding: 1px 5px;
        font-size: 9px;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        border: none;
        cursor: pointer;
    }

    .mrds-gallery-item .mrds-gallery-remove-btn:hover {
        background: #b02a37;
    }
</style>