// Firebase Cloud Messaging Service Worker
// Handles push notifications when the app is in the background

// Import Firebase scripts
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

// The browser-visible Firebase configuration is encoded in the worker URL so
// it is available again when the browser restarts this worker for a push.
try {
    const config = JSON.parse(new URL(self.location.href).searchParams.get('config') || '{}');
    if (config.apiKey && config.projectId && config.messagingSenderId && config.appId) {
        firebase.initializeApp(config);
        // FCM displays notification payloads automatically in the background.
    }
} catch (error) {
    console.error('[FCM] Init error:', error);
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
