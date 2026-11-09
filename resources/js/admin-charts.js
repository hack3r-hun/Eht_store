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

const SERIES = ['#8A9A5B', '#E2725B', '#C9A227', '#64748b', '#6366f1', '#3b82f6', '#22c55e', '#f59e0b'];

const grid = { color: 'rgba(148, 163, 184, 0.18)' };

/**
 * Statistics page: reads the inert JSON blob rendered by admin/statistics/index.blade.php and
 * draws every chart whose <canvas> is present. Each chart is skipped silently when its data or
 * canvas is missing (empty states are rendered server-side).
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

    const money = (value) => `${data.currency} ${Number(value).toLocaleString()}`;
    const draw = (id, config) => {
        const canvas = document.getElementById(id);
        if (canvas) new Chart(canvas, config);
    };
    const legendBottom = { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } };
    const noLegend = { legend: { display: false } };

    const doughnut = (id, labels, counts, colors) => draw(id, {
        type: 'doughnut',
        data: { labels, datasets: [{ data: counts, backgroundColor: colors || SERIES, borderWidth: 0 }] },
        options: { maintainAspectRatio: false, cutout: '62%', plugins: legendBottom },
    });

    // Sales over time: money as lines (left axis), orders as bars (right axis)
    if (data.trend?.labels?.length) {
        const dense = data.trend.labels.length > 45;

        draw('chart-trend', {
            data: {
                labels: data.trend.labels,
                datasets: [
                    { type: 'bar', label: 'Orders', data: data.trend.orders, backgroundColor: 'rgba(226, 114, 91, 0.35)', borderRadius: 4, yAxisID: 'orders', order: 3 },
                    { type: 'line', label: 'Total order value', data: data.trend.order_value, borderColor: PALETTE.gold, backgroundColor: 'rgba(201, 162, 39, 0.1)', tension: 0.1, pointRadius: dense ? 0 : 2, yAxisID: 'money', order: 2 },
                    { type: 'line', label: 'Paid revenue', data: data.trend.paid, borderColor: PALETTE.sage, backgroundColor: 'rgba(138, 154, 91, 0.15)', fill: true, tension: 0.1, pointRadius: dense ? 0 : 2, yAxisID: 'money', order: 1 },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${ctx.dataset.yAxisID === 'money' ? money(ctx.parsed.y) : ctx.parsed.y}` } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 12, autoSkip: true } },
                    money: { position: 'left', beginAtZero: true, grid, ticks: { callback: (v) => Number(v).toLocaleString() }, title: { display: true, text: data.currency } },
                    orders: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { precision: 0 }, title: { display: true, text: 'Orders' } },
                },
            },
        });
    }

    if (data.weekday) {
        const best = data.weekday.orders.indexOf(Math.max(...data.weekday.orders));

        draw('chart-weekday', {
            type: 'bar',
            data: {
                labels: data.weekday.labels,
                datasets: [{ label: 'Orders', data: data.weekday.orders, backgroundColor: data.weekday.orders.map((_, i) => (i === best ? PALETTE.terracotta : 'rgba(138, 154, 91, 0.55)')), borderRadius: 6 }],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { ...noLegend, tooltip: { callbacks: { afterLabel: (ctx) => `Value: ${money(data.weekday.value[ctx.dataIndex])}` } } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid, ticks: { precision: 0 } } },
            },
        });
    }

    if (data.hours) {
        draw('chart-hours', {
            type: 'bar',
            data: { labels: data.hours.labels, datasets: [{ label: 'Orders', data: data.hours.orders, backgroundColor: 'rgba(201, 162, 39, 0.6)', borderRadius: 4 }] },
            options: {
                maintainAspectRatio: false,
                plugins: noLegend,
                scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }, y: { beginAtZero: true, grid, ticks: { precision: 0 } } },
            },
        });
    }

    if (data.status?.labels?.length) {
        doughnut('chart-status', data.status.labels, data.status.counts, data.status.colors.map((c) => PALETTE[c] || PALETTE.slate));
    }

    if (data.method?.labels?.length) {
        doughnut('chart-method', data.method.labels, data.method.counts);
    }

    if (data.paymentStatus?.labels?.length) {
        const byLabel = { Paid: PALETTE.green, Pending: PALETTE.amber, Failed: PALETTE.red, Refunded: PALETTE.slate };
        doughnut('chart-payment-status', data.paymentStatus.labels, data.paymentStatus.counts, data.paymentStatus.labels.map((l) => byLabel[l] || PALETTE.slate));
    }

    if (data.topProducts?.labels?.length) {
        draw('chart-top-products', {
            type: 'bar',
            data: { labels: data.topProducts.labels, datasets: [{ label: 'Earned', data: data.topProducts.revenue, backgroundColor: PALETTE.terracotta, borderRadius: 4 }] },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: { ...noLegend, tooltip: { callbacks: { label: (ctx) => money(ctx.parsed.x), afterLabel: (ctx) => `${data.topProducts.units[ctx.dataIndex]} sold` } } },
                scales: { x: { beginAtZero: true, grid, ticks: { callback: (v) => Number(v).toLocaleString() } }, y: { grid: { display: false } } },
            },
        });
    }

    if (data.categories?.labels?.length) {
        draw('chart-categories', {
            type: 'doughnut',
            data: { labels: data.categories.labels, datasets: [{ data: data.categories.revenue, backgroundColor: SERIES, borderWidth: 0 }] },
            options: {
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: { legend: { position: 'right', labels: { boxWidth: 12 } }, tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${money(ctx.parsed)}` } } },
            },
        });
    }

    if (data.cities?.labels?.length) {
        draw('chart-cities', {
            type: 'bar',
            data: { labels: data.cities.labels, datasets: [{ label: 'Orders', data: data.cities.orders, backgroundColor: PALETTE.sage, borderRadius: 4 }] },
            options: { indexAxis: 'y', maintainAspectRatio: false, plugins: noLegend, scales: { x: { beginAtZero: true, grid, ticks: { precision: 0 } }, y: { grid: { display: false } } } },
        });
    }

    if (data.customers) {
        doughnut('chart-customers', data.customers.labels, data.customers.counts, [PALETTE.sage, PALETTE.terracotta, PALETTE.gold]);
    }

    if (data.buyerType) {
        doughnut('chart-buyer-type', data.buyerType.labels, data.buyerType.counts, [PALETTE.terracotta, PALETTE.sage]);
    }

    if (data.audience?.labels?.length) {
        draw('chart-audience', {
            type: 'bar',
            data: {
                labels: data.audience.labels,
                datasets: [
                    { label: 'New accounts', data: data.audience.customers, backgroundColor: PALETTE.sage, borderRadius: 4 },
                    { label: 'Newsletter sign-ups', data: data.audience.subscribers, backgroundColor: PALETTE.gold, borderRadius: 4 },
                ],
            },
            options: {
                maintainAspectRatio: false,
                plugins: legendBottom,
                scales: { x: { stacked: true, grid: { display: false }, ticks: { maxTicksLimit: 12 } }, y: { stacked: true, beginAtZero: true, grid, ticks: { precision: 0 } } },
            },
        });
    }

    if (data.inventory) {
        doughnut('chart-inventory', data.inventory.labels, data.inventory.counts, [PALETTE.green, PALETTE.amber, PALETTE.red]);
    }

    if (data.wishlist?.labels?.length) {
        draw('chart-wishlist', {
            type: 'bar',
            data: { labels: data.wishlist.labels, datasets: [{ label: 'Saves', data: data.wishlist.counts, backgroundColor: PALETTE.terracotta, borderRadius: 4 }] },
            options: { indexAxis: 'y', maintainAspectRatio: false, plugins: noLegend, scales: { x: { beginAtZero: true, grid, ticks: { precision: 0 } }, y: { grid: { display: false } } } },
        });
    }

    if (data.customOrders?.labels?.length) {
        draw('chart-custom-orders', {
            type: 'bar',
            data: {
                labels: data.customOrders.labels,
                datasets: [{ label: 'Requests', data: data.customOrders.counts, backgroundColor: [PALETTE.blue, PALETTE.amber, PALETTE.green, PALETTE.slate, PALETTE.sage], borderRadius: 4 }],
            },
            options: { maintainAspectRatio: false, plugins: noLegend, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid, ticks: { precision: 0 } } } },
        });
    }

    if (data.ratings) {
        draw('chart-ratings', {
            type: 'bar',
            data: { labels: data.ratings.labels, datasets: [{ label: 'Reviews', data: data.ratings.counts, backgroundColor: PALETTE.gold, borderRadius: 4 }] },
            options: { maintainAspectRatio: false, plugins: noLegend, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid, ticks: { precision: 0 } } } },
        });
    }
}

/**
 * Reads the inert JSON data blob rendered by admin/dashboard.blade.php and
 * draws a bare sparkline (no axes/legend/points) inside the "last 14 days" card.
 * No-ops silently if the page/data/canvases aren't present.
 */
export function refreshDashboardSparklines() {
    ['spark-revenue', 'spark-orders'].forEach((id) => {
        const canvas = document.getElementById(id);
        if (canvas) Chart.getChart(canvas)?.destroy();
    });

    initDashboardSparklines();
}

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
