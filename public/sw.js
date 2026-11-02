self.addEventListener('push', (event) => {
    let payload = {
        title: 'GWTracks',
        body: 'You have a new notification.',
        data: {},
    };

    if (event.data) {
        try {
            payload = { ...payload, ...event.data.json() };
        } catch (error) {
            payload.body = event.data.text();
        }
    }

    const title = payload.title || 'GWTracks';
    const options = {
        body: payload.body || '',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        data: payload.data || {},
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/notifications';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if ('focus' in client) {
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }

            return undefined;
        }),
    );
});
