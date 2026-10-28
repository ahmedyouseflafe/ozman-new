'use strict';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Keep pages live (including authenticated dashboards) while providing a real
// navigation handler for browsers that require one for the install prompt.
// Never cache account data, orders, or failed network responses.
self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET' || request.mode !== 'navigate'
        || new URL(request.url).origin !== self.location.origin) return;

    event.respondWith(fetch(request).catch(() => new Response(`<!doctype html>
<html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>تحقق من الاتصال</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#071820;color:#fff;font:18px Arial,sans-serif;text-align:center}main{max-width:440px;padding:24px}p{line-height:1.8;color:#b9d0da}button{padding:14px 24px;border:0;border-radius:12px;background:#0bdcf3;font:inherit;cursor:pointer}</style></head>
<body><main><h1>تعذّر الاتصال بالمحل</h1><p>تحقق من اتصال الإنترنت ثم أعد المحاولة لعرض أحدث البيانات.</p><button onclick="location.reload()">إعادة المحاولة</button></main></body></html>`, {
        status: 503,
        headers: { 'Content-Type': 'text/html; charset=UTF-8', 'Cache-Control': 'no-store' },
    })));
});

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (_) {
        payload = { body: event.data ? event.data.text() : '' };
    }

    if (payload.closeNotificationTag) {
        event.waitUntil(
            self.registration.getNotifications({ tag: payload.closeNotificationTag })
                .then((notifications) => notifications.forEach((notification) => notification.close()))
        );
        return;
    }

    const title = payload.title || 'إشعار جديد';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/ozman-favicon.png',
        badge: payload.badge || payload.icon || '/ozman-favicon.png',
        tag: payload.tag || 'ozman-merchant-notification',
        renotify: true,
        silent: false,
        requireInteraction: Boolean(payload.requireInteraction),
        vibrate: [240, 90, 240, 90, 360],
        data: {
            url: payload.url || '/',
            payload: payload.data || {},
        },
        actions: [{ action: 'open', title: payload.actionTitle || 'فتح الطلب' }],
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
