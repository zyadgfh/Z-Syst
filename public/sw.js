const CACHE_NAME = 'z-syst-v1';
const STATIC_ASSETS = [
    '/',
    '/css/design-system.css',
    '/assets/js/jquery-3.7.1.min.js',
    '/assets/js/bootstrap.bundle.min.js',
    '/assets/js/theme.js',
    '/assets/js/custom/custom.js',
    '/assets/images/icons/icon-192.png',
    '/assets/images/icons/icon-512.png',
];

// Install — cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch(() => {
                // Ignore individual failures
                console.log('Some assets failed to cache');
            });
        })
    );
    self.skipWaiting();
});

// Activate — clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

// Fetch — network first, fallback to cache
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip API calls and admin routes (always fresh)
    if (event.request.url.includes('/api/') ||
        event.request.url.includes('/admin/') ||
        event.request.url.includes('/login')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful responses
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, clone);
                    });
                }
                return response;
            })
            .catch(() => {
                // Fallback to cache
                return caches.match(event.request).then((cached) => {
                    return cached || caches.match('/');
                });
            })
    );
});

// Push notifications
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Z-Syst';
    const body = data.body || 'New notification';
    const icon = '/assets/images/icons/icon-192.png';

    event.waitUntil(
        self.registration.showNotification(title, {
            body,
            icon,
            badge: '/assets/images/icons/icon-192.png',
            vibrate: [200, 100, 200],
            data: data.url || '/admin',
        })
    );
});

// Notification click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data || '/admin')
    );
});
