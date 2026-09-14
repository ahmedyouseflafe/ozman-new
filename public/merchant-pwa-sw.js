'use strict';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (_) {
        payload = { body: event.data ? event.data.text() : '' };
    }

    const title = payload.title || 'إشعار جديد';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/ozman-favicon.png',
        badge: payload.badge || payload.icon || '/ozman-favicon.png',
        tag: payload.tag || 'ozman-merchant-notification',
        renotify: true,
        requireInteraction: Boolean(payload.requireInteraction),
        vibrate: [240, 90, 240, 90, 360],
        data: {
            url: payload.url || '/',
            payload: payload.data || {},
        },
        actions: [{ action: 'open', title: 'فتح الطلب' }],
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = new URL(event.notification.data?.url || '/', self.location.origin).href;

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const sameOriginWindow = windows.find((client) => new URL(client.url).origin === self.location.origin);

        if (sameOriginWindow) {
            await sameOriginWindow.navigate(targetUrl);
            return sameOriginWindow.focus();
        }

        return self.clients.openWindow(targetUrl);
    })());
});
