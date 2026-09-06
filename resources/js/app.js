import axios from 'axios';
import Alpine from 'alpinejs';
import { initScrollReveal } from './scroll-reveal';
import { registerToastStore } from './toast';
import { initBackButtons } from './back-button';
import { initHeroParallax } from './hero-parallax';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    registerToastStore(Alpine);
});

initScrollReveal();
initBackButtons();
initHeroParallax();
Alpine.start();

// Registering a service worker (even a no-op passthrough) is what makes the
// storefront installable as a home-screen "app" — no offline caching, no push.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
