/**
 * MRDS Restaurant Stats - JavaScript
 */
(function () {
    'use strict';

    const CONFIG = window.MRDSStatsConfig || {};
    let chart = null;

    async function init() {
        try {
            const res = await fetch(CONFIG.restUrl, {
                headers: { 'X-WP-Nonce': CONFIG.nonce, 'Content-Type': 'application/json' }
            });
            if (!res.ok) throw new Error();
            render(await res.json());
        } catch (e) {
            document.getElementById('mrds-stats-loader').style.display = 'none';
            document.getElementById('mrds-stats-empty').style.display = 'block';
        }
    }

    function render(data) {
        if (!data?.totals) {
            document.getElementById('mrds-stats-loader').style.display = 'none';
            document.getElementById('mrds-stats-empty').style.display = 'block';
            return;
        }

        const t = data.totals;
        document.getElementById('stat-reservations').textContent = t.reservations_month || 0;
        document.getElementById('stat-guests').textContent = t.guests_month || 0;
        document.getElementById('stat-avg').textContent = t.reservations_month > 0 ? (t.guests_month / t.reservations_month).toFixed(1) : '0';

        const ev = t.evolution_percent || 0;
        const evEl = document.getElementById('stat-evolution-value');
        evEl.textContent = (ev >= 0 ? '+' : '') + ev;
        evEl.parentElement.classList.add(ev >= 0 ? 'positive' : 'negative');

        if (data.by_month?.length) renderChart(data.by_month);
        if (data.popular_days?.length) renderDays(data.popular_days);
        if (data.restaurants?.length > 1) renderRestaurants(data.restaurants);

        document.getElementById('mrds-stats-loader').style.display = 'none';
        document.getElementById('mrds-stats-content').style.display = 'block';
    }

    function renderChart(data) {
        const ctx = document.getElementById('mrds-chart-months');
        if (!ctx) return;
        if (chart) chart.destroy();

        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(m => m.month_short),
                datasets: [{
                    label: 'Réservations',
                    data: data.map(m => m.count),
                    backgroundColor: '#DA9D42',
                    borderColor: '#C4882E',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: '#666' }, grid: { color: '#E5E5E5' } },
                    x: { ticks: { color: '#666' }, grid: { display: false } }
                }
            }
        });
    }

    function renderDays(days) {
        const list = document.getElementById('popular-days-list');
        if (!list) return;
        list.innerHTML = days.map((d, i) => `
            <li class="popular-day-item">
                <span class="day-rank">${i + 1}</span>
                <span class="day-name">${d.day}</span>
                <span class="day-percent">${d.percent}%</span>
                <div class="day-bar"><div class="day-bar-fill" style="width:${d.percent}%"></div></div>
            </li>
        `).join('');
    }

    function renderRestaurants(restaurants) {
        const tbody = document.getElementById('mrds-restaurants-tbody');
        if (!tbody) return;
        tbody.innerHTML = restaurants.map(r => `
            <tr>
                <td>${r.name}</td>
                <td class="text-center">${r.reservations_month}</td>
                <td class="text-center">${r.guests_month}</td>
            </tr>
        `).join('');
        document.getElementById('mrds-restaurants-section').style.display = 'block';
    }

    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
})();
