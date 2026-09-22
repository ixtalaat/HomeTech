importScripts('https://www.gstatic.com/firebasejs/10.14.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.14.0/firebase-messaging-compat.js');

const ready = fetch('/firebase-config')
    .then((response) => response.json())
    .then((config) => {
        if (!config || !config.apiKey) {
            return null;
        }

        firebase.initializeApp({
            apiKey: config.apiKey,
            authDomain: config.authDomain,
            projectId: config.projectId,
            messagingSenderId: config.senderId,
            appId: config.appId,
        });

        return firebase.messaging();
    })
    .catch(() => null);

ready.then((messaging) => {
    if (!messaging) {
        return;
    }

    messaging.onBackgroundMessage((payload) => {
        const notification = payload.notification || {};
        const data = payload.data || {};

        self.registration.showNotification(notification.title || 'HomeTech', {
            body: notification.body || '',
            icon: '/favicon.svg',
            data: { url: data.url || '/notifications' },
        });
    });
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = (event.notification.data && event.notification.data.url) || '/notifications';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (new URL(client.url).pathname === url && 'focus' in client) {
                    return client.focus();
                }
            }

            return clients.openWindow(url);
        })
    );
});
