/* Isolated administration worker. Never cache authenticated pages or responses. */
'use strict';
self.addEventListener('install', event => event.waitUntil(self.skipWaiting()));
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
function adminUrl(value) {
    try {
        const url = new URL(value || '/admin/analytics', self.location.origin);
        if (url.origin === self.location.origin && url.pathname === '/admin/analytics') return url.href;
    } catch {}
    return new URL('/admin/analytics', self.location.origin).href;
}
self.addEventListener('push', event => {
    let payload = {};
    try { payload = event.data?.json() || {}; } catch {}
    event.waitUntil(self.registration.showNotification(String(payload.title || 'إدارة أوزمان'), {
        body: String(payload.body || 'نشاط جديد في المحلات. افتح مركز المتابعة للتفاصيل.'),
        icon: '/admin/analytics/icon/192.png', badge: '/admin/analytics/icon/192.png',
        tag: String(payload.tag || 'ozman-admin-'+Date.now()), silent: false,
        vibrate: [180, 80, 180], dir: 'rtl', lang: 'ar',
        data: {url: adminUrl(payload.url)},
    }));
});
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const url = adminUrl(event.notification.data?.url);
    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({type: 'window', includeUncontrolled: true});
        const existing = windows.find(client => {
            const current = new URL(client.url);
            return current.origin === self.location.origin && current.pathname === '/admin/analytics';
        });
        if (existing) {
            try { await existing.navigate(url); await existing.focus(); return; } catch {}
        }
        await self.clients.openWindow(url);
    })());
});
self.addEventListener('fetch', event => {
    if (event.request.mode !== 'navigate') return;
    event.respondWith(fetch(event.request).catch(() => new Response(
        '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>إدارة أوزمان</title><body style="font:18px Tahoma;padding:30px;background:white;color:#17283e"><h1>إدارة أوزمان</h1><p>أنت غير متصل الآن. اتصل بالإنترنت وافتح اللوحة مجددًا.</p><a href="/admin/analytics">إعادة المحاولة</a></body></html>',
        {status: 503, headers: {'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store'}}
    )));
});
