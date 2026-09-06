import { Chart } from 'chart.js/auto';

const PALETTE = {
    sage: '#8A9A5B',
    terracotta: '#E2725B',
    gold: '#C9A227',
    slate: '#64748b',
    blue: '#3b82f6',
    amber: '#f59e0b',
    green: '#22c55e',
    red: '#ef4444',
    indigo: '#6366f1',
};

/**
 * Reads the inert JSON data blob rendered by admin/statistics/index.blade.php and
 * initializes the charts alongside the existing breakdown/trend tables. No-ops
 * silently if the page (or its data/canvases) isn't present.
 */
export function initStatisticsCharts() {
    const dataEl = document.getElementById('stats-chart-data');
    if (!dataEl) return;

    let data;
    try {
        data = JSON.parse(dataEl.textContent);
    } catch {
        return;
    }

    const statusCanvas = document.getElementById('chart-status-breakdown');
    if (statusCanvas && data.statusBreakdown?.length) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: {
                labels: data.statusBreakdown.map((row) => row.label),
                datasets: [{
                    data: data.statusBreakdown.map((row) => row.count),
                    backgroundColor: data.statusBreakdown.map((row) => PALETTE[row.color] || PALETTE.slate),
                    borderWidth: 0,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } },
            },
        });
    }

    const paymentCanvas = document.getElementById('chart-payment-breakdown');
    if (paymentCanvas && data.paymentBreakdown?.length) {
        new Chart(paymentCanvas, {
            type: 'bar',
            data: {
                labels: data.paymentBreakdown.map((row) => row.label),
                datasets: [{ label: 'Orders', data: data.paymentBreakdown.map((row) => row.count), backgroundColor: PALETTE.sage, borderRadius: 4 }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    const topProductsCanvas = document.getElementById('chart-top-products');
    if (topProductsCanvas && data.topProducts?.length) {
        new Chart(topProductsCanvas, {
            type: 'bar',
            data: {
                labels: data.topProducts.map((row) => row.label),
                datasets: [{ label: 'Units sold', data: data.topProducts.map((row) => row.count), backgroundColor: PALETTE.terracotta, borderRadius: 4 }],
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

    const trendCanvas = document.getElementById('chart-trend');
    if (trendCanvas && data.trend?.length) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: data.trend.map((row) => row.label),
                datasets: [{
                    label: 'Revenue',
                    data: data.trend.map((row) => row.revenue),
                    borderColor: PALETTE.sage,
                    backgroundColor: 'rgba(138, 154, 91, 0.15)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } },
            },
        });
    }
}

/**
 * Reads the inert JSON data blob rendered by admin/dashboard.blade.php and
 * draws a bare sparkline (no axes/legend/points) inside the Revenue and
 * Orders stat cards. No-ops silently if the page/data/canvases aren't present.
 */
export function initDashboardSparklines() {
    const dataEl = document.getElementById('dashboard-chart-data');
    if (!dataEl) return;

    let data;
    try {
        data = JSON.parse(dataEl.textContent);
    } catch {
        return;
    }

    const sparklineConfig = (values, color) => ({
        type: 'line',
        data: {
            labels: values.map((_, i) => i),
            datasets: [{
                data: values,
                borderColor: color,
                backgroundColor: `${color}26`,
                fill: true,
                tension: 0.35,
                borderWidth: 1.5,
                pointRadius: 0,
            }],
        },
        options: {
            maintainAspectRatio: false,
            animation: { duration: 900 },
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                x: { display: false },
                y: { display: false },
            },
            elements: { line: { capBezierPoints: true } },
        },
    });

    const revenueCanvas = document.getElementById('spark-revenue');
    if (revenueCanvas && data.revenue?.length) {
        new Chart(revenueCanvas, sparklineConfig(data.revenue, PALETTE.sage));
    }

    const ordersCanvas = document.getElementById('spark-orders');
    if (ordersCanvas && data.orders?.length) {
        new Chart(ordersCanvas, sparklineConfig(data.orders, PALETTE.terracotta));
    }
}
