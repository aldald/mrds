<?php
/**
 * Template: Statistiques Restaurant
 * Shortcode: [mrds_restaurant_stats]
 */
?>
<div id="mrds-stats-app" class="container my-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Statistiques</h2>
        <small class="text-muted">Vue d'ensemble de vos réservations</small>
    </div>

    <!-- Loader -->
    <div id="mrds-stats-loader" class="text-center py-5">
        <div class="spinner"></div>
        <p class="mt-3 text-muted">Chargement des statistiques…</p>
    </div>

    <!-- Contenu -->
    <div id="mrds-stats-content" style="display: none;">

        <!-- KPIs -->
        <div class="row g-4 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <span class="stat-number" id="stat-reservations">0</span>
                    <span class="stat-label">Réservations ce mois</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <span class="stat-number" id="stat-guests">0</span>
                    <span class="stat-label">Couverts ce mois</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <span class="stat-number" id="stat-avg">0</span>
                    <span class="stat-label">Moy. couverts/résa</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <span class="stat-number" id="stat-evolution">
                        <span id="stat-evolution-value">0</span>%
                    </span>
                    <span class="stat-label">vs mois précédent</span>
                </div>
            </div>
        </div>

        <!-- Graphique + Jours populaires -->
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Évolution des réservations</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="mrds-chart-months" height="200"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Jours populaires</h5>
                    </div>
                    <div class="card-body">
                        <ul class="popular-days-list" id="popular-days-list"></ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Multi-restaurants -->
        <div id="mrds-restaurants-section" class="mt-4" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Détail par restaurant</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Restaurant</th>
                                    <th class="text-center">Réservations</th>
                                    <th class="text-center">Couverts</th>
                                </tr>
                            </thead>
                            <tbody id="mrds-restaurants-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vide -->
    <div id="mrds-stats-empty" style="display: none;">
        <div class="alert alert-info">Aucune donnée disponible.</div>
    </div>

</div>
