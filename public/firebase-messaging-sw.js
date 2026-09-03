// Firebase Cloud Messaging Service Worker
// Handles push notifications when the app is in the background

// Import Firebase scripts
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

// Firebase config will be passed from the main app
let firebaseConfig = null;

// Listen for config messages from the main thread
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'FIREBASE_CONFIG') {
        firebaseConfig = event.data.config;
        initFirebase();
    }
});

function initFirebase() {
    if (!firebaseConfig || firebase.apps.length > 0) return;

    try {
        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();

        messaging.onBackgroundMessage((payload) => {
            console.log('[FCM Background]', payload);

            const title = payload.notification?.title || 'Z-Syst';
            const options = {
                body: payload.notification?.body || '',
                icon: '/logo.png',
                badge: '/logo.png',
                data: payload.data || {},
                actions: [
                    { action: 'view', title: 'عرض' },
                    { action: 'dismiss', title: 'إغلاق' },
                ],
                tag: payload.data?.alert_id || 'default',
                renotify: true,
            };

            self.registration.showNotification(title, options);
        });
    } catch (e) {
        console.error('[FCM] Init error:', e);
    }
}

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    if (event.action === 'dismiss') return;

    const url = event.notification?.data?.url || '/admin';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes('/admin') && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
