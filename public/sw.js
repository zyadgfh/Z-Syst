/* ==========================================================================
   sw.js — Z-Syst service worker (PWA / offline-first shell)
   Strategy:
   - Precache the static shell (offline page, design layer, core JS).
   - Navigations: network-first, fall back to cache, then /offline.html.
     ADMIN pages and auth pages are NEVER cached.
   - Static assets (css/js/img/fonts): cache-first with background refresh.
   - Sales POSTs are queued client-side by pos-offline.js (IndexedDB);
     this worker only helps pages and assets survive a dead network.
   ========================================================================== */

const VERSION = 'zsyst-v1';
const SHELL = [
    '/offline.html',
    '/manifest.json',
    '/assets/css/apple.css',
    '/assets/js/apple-motion.js',
    '/assets/js/ap-theme.js',
    '/assets/js/pos-offline.js',
    '/assets/js/pos-keys.js',
    '/assets/js/ap-live.js',
    '/assets/img/ap-icon.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(VERSION).then((cache) =>
            Promise.allSettled(SHELL.map((url) => cache.add(new Request(url, { cache: 'reload' }))))
        ).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// Clear all caches on logout — called from pos-offline.js / logout handler
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'CLEAR_CACHES') {
        event.waitUntil(caches.keys().then((keys) => Promise.all(keys.map((k) => caches.delete(k)))));
    }
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // NEVER cache admin, auth, or sensitive pages
    const noCachePrefixes = ['/admin', '/login', '/register', '/api/', '/storage/', '/live', '/dashboard'];
    if (noCachePrefixes.some((p) => url.pathname.startsWith(p))) return;

    // Page navigations: network first, cache second, offline page last.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Only cache successful HTML navigations for public pages
                    if (response && response.ok && response.headers.get('content-type')?.includes('text/html')) {
                        const copy = response.clone();
                        caches.open(VERSION).then((cache) => cache.put(request, copy));
                    }
                    return response;
                })
                .catch(() =>
                    caches.match(request).then((hit) => hit || caches.match('/offline.html'))
                )
        );
        return;
    }

    // Static assets: cache-first, refresh in the background (stale-while-revalidate).
    const isStatic = /\.(css|js|svg|png|jpg|jpeg|webp|woff2?)($|\?)/i.test(url.pathname)
        || url.pathname.startsWith('/assets/');
    if (isStatic) {
        event.respondWith(
            caches.match(request).then((hit) => {
                const network = fetch(request)
                    .then((response) => {
                        if (response && response.ok) {
                            const copy = response.clone();
                            caches.open(VERSION).then((cache) => cache.put(request, copy));
                        }
                        return response;
                    })
                    .catch(() => hit);
                return hit || network;
            })
        );
    }
});
