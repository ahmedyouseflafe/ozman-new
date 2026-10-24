(() => {
    'use strict';

    const config = window.OZMAN_OFFER_NOTIFICATIONS;
    if (!config) {
        return;
    }

    const supported = window.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const permission = () => supported ? Notification.permission : 'unsupported';
    const iosSkipKey = 'ozman.offer-push.ios-skipped';
    const skipKey = 'ozman.offer-push.skipped';
    const iosSkipped = () => {
        try { return sessionStorage.getItem(skipKey) === '1' || (ios && sessionStorage.getItem(iosSkipKey) === '1'); } catch (_) { return false; }
    };
    const push = window.OzmanWebPush;
    const registrationPromise = supported ? push.register(config.serviceWorkerUrl) : Promise.resolve(null);
    registrationPromise.catch((error) => console.error('Unable to register the offer notification service worker.', error));

    let subscribing, permissionRequest;
    const requestPermission = () => {
        // Keep the native call inside the click gesture; never stack prompts.
        if (!permissionRequest) {
            permissionRequest = Promise.resolve(Notification.requestPermission())
                .finally(() => { permissionRequest = null; });
        }
        return permissionRequest;
    };
    const subscribe = async () => {
        if (!supported) throw new Error('offer-push-unsupported');
        if (subscribing) return subscribing;
        subscribing = (async () => {
            const registration = await push.register(config.serviceWorkerUrl);
            const subscription = await push.subscription(registration, config.vapidPublicKey);
            await push.save(config.subscribeUrl, subscription, { source_shop_id: config.sourceShopId });
            window.dispatchEvent(new Event('ozman:offer-push-ready'));
            return subscription;
        })().finally(() => { subscribing = null; });
        return subscribing;
    };

    const mountPrompt = (initialError) => {
        if (!config.showPrompt || iosSkipped() || document.querySelector('[data-offer-push-prompt]')) {
            return;
        }

        const style = document.createElement('style');
        style.textContent = `
            .offer-push-backdrop{position:fixed;z-index:2147483645;inset:0;margin:0;width:100%;max-width:none;height:100%;height:100dvh;max-height:none;border:0;display:grid;place-items:center;padding:18px;background:rgba(0,5,9,.68);backdrop-filter:blur(7px);font-family:Cairo,Arial,sans-serif;direction:rtl;box-sizing:border-box;overflow:auto;overscroll-behavior:contain}
            .offer-push-backdrop::backdrop{background:rgba(0,5,9,.6)}
            .offer-push-card{box-sizing:border-box;position:relative;width:min(440px,100%);padding:27px 22px 21px;border:1px solid rgba(11,220,243,.38);border-radius:25px;background:linear-gradient(145deg,#0d2029,#071118);box-shadow:0 30px 90px rgba(0,0,0,.62);color:#f7fbfd;text-align:center}
            .offer-push-icon{display:grid;place-items:center;width:67px;height:67px;margin:0 auto 14px;border-radius:21px;background:rgba(11,220,243,.12);color:#0bdcf3;font-size:31px;box-shadow:0 0 32px rgba(11,220,243,.17)}
            .offer-push-card strong{display:block;font-size:21px;font-weight:900;line-height:1.45}
            .offer-push-card p{margin:9px 0 20px;color:#a9bdc7;font-size:13px;font-weight:700;line-height:1.85}
            .offer-push-actions{display:grid;grid-template-columns:1fr;gap:9px}
            .offer-push-actions button{min-height:49px;padding:10px 17px;border:1px solid #27434f;border-radius:15px;background:#0b1720;color:#dce9ee;font:800 13px Cairo,Arial,sans-serif;cursor:pointer}
            .offer-push-actions [data-offer-push-enable]{border:0;background:linear-gradient(135deg,#0bdcf3,#3eb8ff);color:#00151b;font-size:14px}
            .offer-push-actions button:disabled{opacity:.58;cursor:wait}
            .offer-push-status{min-height:20px;margin:12px 0 0!important;color:#8fa8b4!important;font-size:11px!important}
            .offer-push-status[data-state=error]{color:#ff8c98!important}
            .offer-push-status[data-state=success]{color:#35df91!important}
            @media(max-width:520px){.offer-push-backdrop{align-items:end;padding:10px}.offer-push-card{border-radius:25px 25px 18px 18px}.offer-push-actions{grid-template-columns:1fr}.offer-push-actions [data-offer-push-enable]{grid-row:1}}
        `;
        document.head.appendChild(style);

        const backdrop = document.createElement('dialog');
        backdrop.className = 'offer-push-backdrop';
        backdrop.dataset.offerPushPrompt = '';
        backdrop.setAttribute('role', 'dialog');
        backdrop.setAttribute('aria-modal', 'true');
        backdrop.setAttribute('aria-label', config.title);
        backdrop.innerHTML = `
            <section class="offer-push-card">
                <div class="offer-push-icon" aria-hidden="true">🔔</div>
                <strong>${config.title}</strong>
                <p>${ios ? config.iosBodyLabel : config.body}</p>
                <div class="offer-push-actions">
                    <button type="button" data-offer-push-enable>${config.allowLabel}</button>
                    <button type="button" data-offer-push-skip ${ios ? '' : 'hidden'}>${config.skipLabel}</button>
                </div>
                <p class="offer-push-status" data-offer-push-status role="status" aria-live="polite"></p>
            </section>
        `;
        document.body.appendChild(backdrop);
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        backdrop.showModal();
        let finished = false;
        let attempt = 0, attemptTimer;
        const finish = () => {
            if (finished) return;
            finished = true;
            attempt++;clearTimeout(attemptTimer);
            window.removeEventListener('focus', recheck);
            document.removeEventListener('visibilitychange', recheck);
            backdrop.close();backdrop.remove();document.body.style.overflow = previousOverflow;window.removeEventListener('ozman:offer-push-ready', finish);
        };
        const skip = () => {
            try { sessionStorage.setItem(skipKey, '1'); } catch (_) { /* Browsing still resumes without storage. */ }
            finish();
        };
        backdrop.querySelector('[data-offer-push-skip]')?.addEventListener('click', skip);
        backdrop.addEventListener('cancel', event => { event.preventDefault();if (!skipButton.hidden) skip(); });
        window.addEventListener('ozman:offer-push-ready', finish, { once: true });

        const enableButton = backdrop.querySelector('[data-offer-push-enable]');
        const skipButton = backdrop.querySelector('[data-offer-push-skip]');
        const status = backdrop.querySelector('[data-offer-push-status]');

        const explain = () => {
            if (ios && (!standalone || !supported)) {
                status.textContent = config.iosLabel;
                enableButton.hidden = true;
                skipButton.hidden = false;
                return true;
            }
            if (!supported) {
                status.textContent = config.unsupportedLabel;
                enableButton.hidden = true;
                skipButton.hidden = false;
                return true;
            }
            if (permission() === 'denied') {
                status.textContent = config.deniedLabel;
                enableButton.textContent = config.recheckLabel;
                skipButton.hidden = false;
                return true;
            }
            enableButton.textContent = config.allowLabel;
            return false;
        };
        function recheck() {
            if (!finished && !enableButton.disabled && !document.hidden) explain();
        }
        window.addEventListener('focus', recheck);
        document.addEventListener('visibilitychange', recheck);
        explain();
        if (initialError && supported && permission() === 'granted') {
            status.dataset.state = 'error';
            status.textContent = push.message(initialError);
            skipButton.hidden = false;
        }

        enableButton.addEventListener('click', async () => {
            if (!supported || (ios && !standalone)) { window.location.reload();return; }
            if (explain()) return;
            enableButton.disabled = true;
            skipButton.hidden = false;
            status.dataset.state = '';
            status.textContent = config.requestingLabel;
            const currentAttempt = ++attempt;
            // Native permission prompts can remain unresolved indefinitely.
            // Release the UI even when the browser/provider cannot be cancelled.
            attemptTimer = setTimeout(() => {
                if (finished || currentAttempt !== attempt) return;
                attempt++;
                enableButton.disabled = false;
                status.dataset.state = 'error';
                status.textContent = config.timeoutLabel || config.errorLabel;
            }, 20000);

            try {
                const result = permission() === 'granted' ? 'granted' : await requestPermission();
                if (finished || currentAttempt !== attempt) return;
                if (result !== 'granted') {
                    status.dataset.state = 'error';
                    status.textContent = result === 'denied' ? config.deniedLabel : (config.dismissedLabel || config.deniedLabel);
                    return;
                }

                await subscribe();
                if (finished || currentAttempt !== attempt) return;
                status.dataset.state = 'success';
                status.textContent = config.successLabel;
            } catch (error) {
                if (finished || currentAttempt !== attempt) return;
                console.error('Unable to enable offer notifications.', error);
                status.dataset.state = 'error';
                status.textContent = push.message(error);
            } finally {
                if (currentAttempt === attempt) {
                    clearTimeout(attemptTimer);
                    enableButton.disabled = false;
                }
            }
        });
    };

    const start = () => {
        if (permission() === 'granted') {
            // Browser permission alone does not mean the server has a subscription.
            subscribe().catch((error) => { console.error('Unable to refresh offer notification subscription.', error);mountPrompt(error); });
            return;
        }

        // Open after existing welcome dialogs so the notification request is visible.
        window.setTimeout(mountPrompt, 0);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }

    window.OzmanOfferNotifications = { subscribe, registration: registrationPromise };
})();
