<?php
/**
 * Template: Gestion des Restaurateurs
 * 
 * Affiché via le shortcode [mrds_restaurateur_manager]
 * Accessible aux : administrator, super_restaurateur
 *
 * @package mrds-gestion-restaurateurs
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="mrds-restaurateurs-app" class="container my-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Gestion des restaurateurs</h1>
        <small class="text-muted">Gestion des restaurateurs liés à votre compte</small>
    </div>

    <!-- Liste des restaurateurs -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mes restaurateurs</h5>
            <button type="button" id="mrds-restaurateur-new-btn" class="btn btn-sm btn-primary">
                + Nouveau restaurateur
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Prénom</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Date de création</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mrds-restaurateurs-rows">
                        <tr>
                            <td colspan="6" class="text-center py-3">Chargement…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="card">
        <div class="card-header">
            <h5 id="mrds-restaurateur-form-title" class="mb-0">Ajouter un restaurateur</h5>
        </div>
        <div class="card-body">
            <form id="mrds-restaurateur-form">
                <input type="hidden" id="mrds-restaurateur-id" value="">

                <!-- Informations personnelles -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Informations personnelles</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="mrds-restaurateur-firstname" class="form-label">Prénom *</label>
                            <input type="text" class="form-control" id="mrds-restaurateur-firstname" required>
                        </div>
                        <div class="col-md-6">
                            <label for="mrds-restaurateur-lastname" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="mrds-restaurateur-lastname" required>
                        </div>
                    </div>
                </fieldset>

                <!-- Contact -->
                <fieldset class="border rounded-3 p-3 mb-3">
                    <legend class="float-none w-auto px-2 fs-6 text-muted">Contact</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="mrds-restaurateur-email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="mrds-restaurateur-email" required>
                            <div class="form-text">Un email avec les identifiants sera envoyé à cette adresse.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="mrds-restaurateur-phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="mrds-restaurateur-phone">
                        </div>
                    </div>
                </fieldset>

                <!-- Boutons -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" id="mrds-restaurateur-save-btn" class="btn btn-success">
                        Enregistrer
                    </button>
                    <button type="button" id="mrds-restaurateur-reset-btn" class="btn btn-outline-secondary">
                        Annuler / Nouveau
                    </button>
                </div>
            </form>

            <!-- Zone de message -->
            <div id="mrds-restaurateur-message" class="mt-3"></div>
        </div>
    </div>

</div>