import axios from 'axios';
import Alpine from 'alpinejs';
import 'cropperjs/dist/cropper.css';
import { registerAdminImageCropper } from './admin-image-cropper';
import { registerPageBuilder } from './admin-page-builder';
import { registerDashboardLive } from './admin-live';
import { initScrollReveal } from './scroll-reveal';
import { registerToastStore } from './toast';
import { initBackButtons } from './back-button';
import { initStatisticsCharts, initDashboardSparklines } from './admin-charts';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}

/**
 * Admin bundle owns Alpine start so the cropper registers before any
 * x-data="adminImageCropper" runs (avoids the dual-entry race with app.js).
 */
window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    registerToastStore(Alpine);
});

registerAdminImageCropper(Alpine);
registerPageBuilder(Alpine);
registerDashboardLive(Alpine);
initScrollReveal();
initBackButtons();
initStatisticsCharts();
initDashboardSparklines();
Alpine.start();
