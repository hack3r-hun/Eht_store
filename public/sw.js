// Minimal service worker — exists only to satisfy "Add to Home Screen"
// installability checks. No caching strategy: every request passes straight
// through to the network so the storefront never serves stale content.
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});
