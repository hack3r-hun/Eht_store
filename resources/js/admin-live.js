import { refreshDashboardSparklines } from './admin-charts';

/**
 * Dashboard live refresh: re-fetches the dashboard in the background and swaps only the live
 * region (plus the sidebar badges) so new orders show up without reloading the whole page.
 * Optionally polls every 45 seconds while the tab is visible.
 */
const POLL_MS = 45000;
const STORAGE_KEY = 'ek_admin_dashboard_auto';

export function registerDashboardLive(Alpine) {
    Alpine.data('dashboardLive', () => ({
        loading: false,
        auto: true,
        updatedAt: new Date(),
        now: Date.now(),
        error: '',
        timer: null,
        ticker: null,

        init() {
            try {
                this.auto = localStorage.getItem(STORAGE_KEY) !== 'off';
            } catch {
                //
            }

            this.ticker = setInterval(() => { this.now = Date.now(); }, 15000);
            this.schedule();

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && this.auto && Date.now() - this.updatedAt.getTime() > POLL_MS) {
                    this.refresh(false);
                }
            });
        },

        get label() {
            const seconds = Math.max(0, Math.round((this.now - this.updatedAt.getTime()) / 1000));
            if (seconds < 20) return 'Updated just now';
            if (seconds < 90) return `Updated ${seconds}s ago`;

            return `Updated ${this.updatedAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
        },

        toggleAuto() {
            this.auto = !this.auto;
            try {
                localStorage.setItem(STORAGE_KEY, this.auto ? 'on' : 'off');
            } catch {
                //
            }
            this.schedule();
        },

        schedule() {
            clearInterval(this.timer);

            if (this.auto) {
                this.timer = setInterval(() => {
                    if (document.visibilityState === 'visible') this.refresh(false);
                }, POLL_MS);
            }
        },

        async refresh(manual = true) {
            if (this.loading) return;

            this.loading = true;
            this.error = '';
            const region = document.getElementById('dashboard-live');
            const before = Number(region?.dataset.ordersTotal ?? 0);

            try {
                const response = await fetch(`${window.location.pathname}?fresh=1`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                    credentials: 'same-origin',
                });

                if (response.redirected && response.url.includes('/login')) {
                    this.error = 'Your session expired — please reload the page and sign in again.';
                    return;
                }

                if (!response.ok) throw new Error(String(response.status));

                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                const fresh = doc.getElementById('dashboard-live');
                if (!fresh || !region) throw new Error('missing region');

                region.innerHTML = fresh.innerHTML;
                region.dataset.ordersTotal = fresh.dataset.ordersTotal;

                const nav = document.getElementById('admin-nav');
                const freshNav = doc.getElementById('admin-nav');
                if (nav && freshNav) nav.innerHTML = freshNav.innerHTML;

                refreshDashboardSparklines();
                this.updatedAt = new Date();
                this.now = Date.now();

                const added = Number(fresh.dataset.ordersTotal ?? 0) - before;
                if (added > 0) {
                    window.showToast?.(`${added} new ${added === 1 ? 'order' : 'orders'} received`, 'success');
                } else if (manual) {
                    window.showToast?.('Dashboard is up to date', 'info');
                }
            } catch {
                this.error = 'Could not refresh right now. Check your connection and try again.';
            } finally {
                this.loading = false;
            }
        },
    }));
}
