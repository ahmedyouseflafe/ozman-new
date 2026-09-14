(() => {
    'use strict';

    const config = window.OZMAN_OFFER_NOTIFICATIONS;
    if (!config || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        return;
    }

    const snoozeKey = 'ozman_offer_notifications_snoozed_until';
    const registrationPromise = navigator.serviceWorker.register(config.serviceWorkerUrl, { scope: '/' });
    registrationPromise.catch((error) => console.error('Unable to register the offer notification service worker.', error));

    const urlBase64ToUint8Array = (value) => {
        const padding = '='.repeat((4 - value.length % 4) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(base64);
        return Uint8Array.from([...raw].map((character) => character.charCodeAt(0)));
    };

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    const storeSubscription = async (subscription) => {
        const serialized = subscription.toJSON();
        serialized.contentEncoding = window.PushManager?.supportedContentEncodings?.[0] || 'aes128gcm';

        const response = await fetch(config.subscribeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                source_shop_id: config.sourceShopId,
                subscription: serialized,
            }),
        });

        if (!response.ok) throw new Error(`offer-subscription:${response.status}`);
        return subscription;
    };

    const subscribe = async () => {
        const registration = await registrationPromise;
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(config.vapidPublicKey),
            });
        }

        await storeSubscription(subscription);
        return subscription;
    };

    const isSnoozed = () => {
        try {
            return Number(localStorage.getItem(snoozeKey) || 0) > Date.now();
        } catch (_) {
            return false;
        }
    };

    const snooze = () => {
        try {
            localStorage.setItem(snoozeKey, String(Date.now() + (3 * 24 * 60 * 60 * 1000)));
        } catch (_) {
            // Storage can be unavailable in private browsing; closing still works for this page.
        }
    };

    const mountPrompt = () => {
        if (!config.showPrompt || Notification.permission !== 'default' || isSnoozed() || document.querySelector('[data-offer-push-prompt]')) {
            return;
        }

        const style = document.createElement('style');
        style.textContent = `
            .offer-push-backdrop{position:fixed;z-index:2147483645;inset:0;display:grid;place-items:center;padding:18px;background:rgba(0,5,9,.68);backdrop-filter:blur(7px);font-family:Cairo,Arial,sans-serif;direction:rtl}
            .offer-push-card{position:relative;width:min(440px,100%);padding:27px 22px 21px;border:1px solid rgba(11,220,243,.38);border-radius:25px;background:linear-gradient(145deg,#0d2029,#071118);box-shadow:0 30px 90px rgba(0,0,0,.62);color:#f7fbfd;text-align:center}
            .offer-push-icon{display:grid;place-items:center;width:67px;height:67px;margin:0 auto 14px;border-radius:21px;background:rgba(11,220,243,.12);color:#0bdcf3;font-size:31px;box-shadow:0 0 32px rgba(11,220,243,.17)}
            .offer-push-card strong{display:block;font-size:21px;font-weight:900;line-height:1.45}
            .offer-push-card p{margin:9px 0 20px;color:#a9bdc7;font-size:13px;font-weight:700;line-height:1.85}
            .offer-push-actions{display:grid;grid-template-columns:1fr auto;gap:9px}
            .offer-push-actions button{min-height:49px;padding:10px 17px;border:1px solid #27434f;border-radius:15px;background:#0b1720;color:#dce9ee;font:800 13px Cairo,Arial,sans-serif;cursor:pointer}
            .offer-push-actions [data-offer-push-enable]{border:0;background:linear-gradient(135deg,#0bdcf3,#3eb8ff);color:#00151b;font-size:14px}
            .offer-push-actions button:disabled{opacity:.58;cursor:wait}
            .offer-push-status{min-height:20px;margin:12px 0 0!important;color:#8fa8b4!important;font-size:11px!important}
            .offer-push-status[data-state=error]{color:#ff8c98!important}
            .offer-push-status[data-state=success]{color:#35df91!important}
            @media(max-width:520px){.offer-push-backdrop{align-items:end;padding:10px}.offer-push-card{border-radius:25px 25px 18px 18px}.offer-push-actions{grid-template-columns:1fr}.offer-push-actions [data-offer-push-enable]{grid-row:1}}
        `;
        document.head.appendChild(style);

        const backdrop = document.createElement('div');
        backdrop.className = 'offer-push-backdrop';
        backdrop.dataset.offerPushPrompt = '';
        backdrop.setAttribute('role', 'dialog');
        backdrop.setAttribute('aria-modal', 'true');
        backdrop.setAttribute('aria-label', config.title);
        backdrop.innerHTML = `
            <section class="offer-push-card">
                <div class="offer-push-icon" aria-hidden="true">🔔</div>
                <strong>${config.title}</strong>
                <p>${config.body}</p>
                <div class="offer-push-actions">
                    <button type="button" data-offer-push-enable>${config.allowLabel}</button>
                    <button type="button" data-offer-push-later>${config.laterLabel}</button>
                </div>
                <p class="offer-push-status" data-offer-push-status></p>
            </section>
        `;
        document.body.appendChild(backdrop);

        const enableButton = backdrop.querySelector('[data-offer-push-enable]');
        const laterButton = backdrop.querySelector('[data-offer-push-later]');
        const status = backdrop.querySelector('[data-offer-push-status]');

        laterButton.addEventListener('click', () => {
            snooze();
            backdrop.remove();
        });

        enableButton.addEventListener('click', async () => {
            enableButton.disabled = true;
            laterButton.disabled = true;
            status.textContent = config.requestingLabel;

            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    status.dataset.state = 'error';
                    status.textContent = config.deniedLabel;
                    laterButton.disabled = false;
                    return;
                }

                await subscribe();
                status.dataset.state = 'success';
                status.textContent = config.successLabel;
                window.setTimeout(() => backdrop.remove(), 1200);
            } catch (error) {
                console.error('Unable to enable offer notifications.', error);
                status.dataset.state = 'error';
                status.textContent = config.errorLabel;
                enableButton.disabled = false;
                laterButton.disabled = false;
            }
        });
    };

    const start = () => {
        if (Notification.permission === 'granted') {
            subscribe().catch((error) => console.error('Unable to refresh offer notification subscription.', error));
            return;
        }

        window.setTimeout(mountPrompt, 900);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }

    window.OzmanOfferNotifications = { subscribe, registration: registrationPromise };
})();
