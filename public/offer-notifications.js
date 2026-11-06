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

    let subscribing, permissionRequest, ready = false;
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
            ready = true;
            window.dispatchEvent(new Event('ozman:offer-push-ready'));
            return subscription;
        })().finally(() => { subscribing = null; });
        return subscribing;
    };

    // Dismissing hides the card for a few days; browsing is never blocked by it.
    const snoozeKey = 'ozman.offer-push.snoozed-until';
    const snoozed = () => { try { return Number(localStorage.getItem(snoozeKey) || 0) > Date.now(); } catch (_) { return false; } };
    const snooze = (days) => { try { localStorage.setItem(snoozeKey, String(Date.now() + days * 864e5)); sessionStorage.setItem(skipKey, '1'); } catch (_) { /* Storage may be blocked. */ } };

    const mountPrompt = (initialError, force = false) => {
        if ((!config.showPrompt && !force) || (!force && (iosSkipped() || snoozed())) || document.querySelector('[data-offer-push-prompt]')) {
            return;
        }

        const style = document.createElement('style');
        style.textContent = `
            .offer-push-sheet{position:fixed;z-index:2147483645;inset-inline:14px;bottom:calc(84px + env(safe-area-inset-bottom,0px));display:flex;justify-content:center;pointer-events:none;font-family:Cairo,Arial,sans-serif;direction:rtl}
            .offer-push-card{pointer-events:auto;box-sizing:border-box;width:min(460px,100%);padding:16px 16px 14px;border:1px solid rgba(11,220,243,.38);border-radius:22px;background:linear-gradient(145deg,#0d2029,#071118);box-shadow:0 24px 70px rgba(0,0,0,.55);color:#f7fbfd;transform:translateY(130%);transition:transform .35s ease}
            .offer-push-sheet.is-open .offer-push-card{transform:none}
            .offer-push-row{display:flex;gap:12px;align-items:flex-start}
            .offer-push-icon{flex:0 0 46px;display:grid;place-items:center;height:46px;border-radius:15px;background:rgba(11,220,243,.12);font-size:22px}
            .offer-push-card strong{display:block;font-size:16px;font-weight:900;line-height:1.5}
            .offer-push-card p{margin:2px 0 0;color:#a9bdc7;font-size:12.5px;font-weight:700;line-height:1.7}
            .offer-push-close{margin-inline-start:auto;flex:0 0 32px;height:32px;border:0;border-radius:10px;background:transparent;color:#8fa8b4;font-size:20px;cursor:pointer}
            .offer-push-actions{display:grid;grid-template-columns:1.4fr 1fr;gap:8px;margin-top:12px}
            .offer-push-actions button{min-height:44px;padding:8px 14px;border:1px solid #27434f;border-radius:14px;background:#0b1720;color:#dce9ee;font:800 13px Cairo,Arial,sans-serif;cursor:pointer}
            .offer-push-actions [data-offer-push-enable]{border:0;background:linear-gradient(135deg,#0bdcf3,#3eb8ff);color:#00151b;font-size:14px}
            .offer-push-actions button:disabled{opacity:.6;cursor:wait}
            .offer-push-actions button[hidden]{display:none!important}
            .offer-push-status{min-height:0;margin:10px 0 0!important;font-size:12px!important;color:#8fa8b4!important}
            .offer-push-status:empty{display:none}
            .offer-push-status[data-state=error]{color:#ff8c98!important}
            .offer-push-status[data-state=success]{color:#35df91!important}
            .offer-push-help{margin:8px 0 0!important;padding:10px 12px;border-radius:12px;background:rgba(255,209,102,.1);color:#ffd98a!important;font-size:12px!important}
            @media(prefers-reduced-motion:reduce){.offer-push-card{transition:none}}
        `;
        document.head.appendChild(style);

        const sheet = document.createElement('div');
        sheet.className = 'offer-push-sheet';
        sheet.dataset.offerPushPrompt = '';
        sheet.setAttribute('role', 'region');
        sheet.setAttribute('aria-label', config.title);
        sheet.innerHTML = `
            <section class="offer-push-card">
                <div class="offer-push-row">
                    <div class="offer-push-icon" aria-hidden="true">🔔</div>
                    <div><strong>${config.title}</strong><p>${ios ? config.iosBodyLabel : config.body}</p></div>
                    <button type="button" class="offer-push-close" data-offer-push-close aria-label="${config.laterLabel}">×</button>
                </div>
                <div class="offer-push-actions">
                    <button type="button" data-offer-push-enable>${config.allowLabel}</button>
                    <button type="button" data-offer-push-skip>${config.laterLabel}</button>
                </div>
                <p class="offer-push-status" data-offer-push-status role="status" aria-live="polite"></p>
            </section>
        `;
        document.body.appendChild(sheet);
        requestAnimationFrame(() => requestAnimationFrame(() => sheet.classList.add('is-open')));
        let finished = false;
        let attempt = 0, attemptTimer, helpTimer;
        const finish = () => {
            if (finished) return;
            finished = true;
            attempt++; clearTimeout(attemptTimer); clearTimeout(helpTimer);
            window.removeEventListener('focus', recheck);
            document.removeEventListener('visibilitychange', recheck);
            window.removeEventListener('ozman:offer-push-ready', done);
            sheet.classList.remove('is-open');
            setTimeout(() => { sheet.remove(); style.remove(); }, 400);
        };
        const later = () => { snooze(3); finish(); };
        const done = () => setTimeout(finish, 1600);
        sheet.querySelector('[data-offer-push-skip]').addEventListener('click', later);
        sheet.querySelector('[data-offer-push-close]').addEventListener('click', later);
        document.addEventListener('keydown', function esc(event) { if (event.key === 'Escape' && !finished) { document.removeEventListener('keydown', esc); later(); } });
        window.addEventListener('ozman:offer-push-ready', done, { once: true });

        const enableButton = sheet.querySelector('[data-offer-push-enable]');
        const skipButton = sheet.querySelector('[data-offer-push-skip]');
        const status = sheet.querySelector('[data-offer-push-status]');
        const help = document.createElement('p');
        help.className = 'offer-push-help';
        help.hidden = true;
        help.setAttribute('role', 'alert');
        status.after(help);
        const showHelp = (text) => { help.textContent = text; help.hidden = false; };

        const explain = () => {
            if (ios && (!standalone || !supported)) {
                status.textContent = config.iosLabel;
                enableButton.hidden = true;
                return true;
            }
            if (!supported) {
                status.textContent = config.unsupportedLabel;
                enableButton.hidden = true;
                return true;
            }
            if (permission() === 'denied') {
                status.dataset.state = 'error';
                status.textContent = config.deniedLabel;
                enableButton.textContent = config.recheckLabel;
                return true;
            }
            enableButton.textContent = config.allowLabel;
            return false;
        };
        function recheck() {
            if (!finished && !enableButton.disabled && !document.hidden) {
                if (permission() === 'granted') enableButton.click();
                else explain();
            }
        }
        window.addEventListener('focus', recheck);
        document.addEventListener('visibilitychange', recheck);
        explain();
        if (initialError && supported && permission() === 'granted') {
            status.dataset.state = 'error';
            status.textContent = push.message(initialError);
        }

        enableButton.addEventListener('click', async () => {
            if (!supported || (ios && !standalone)) { later(); return; }
            if (explain()) {
                // A website can't lift a browser block; show how to do it instead.
                showHelp(config.blockedHelpLabel || config.deniedLabel);
                return;
            }
            help.hidden = true;
            enableButton.disabled = true;
            status.dataset.state = '';
            status.textContent = config.requestingLabel;
            const currentAttempt = ++attempt;
            // Chrome's "quiet" permission UI keeps the request pending without showing a dialog.
            helpTimer = setTimeout(() => {
                if (!finished && currentAttempt === attempt && permission() === 'default') showHelp(config.pendingHelpLabel || config.timeoutLabel);
            }, 4000);
            attemptTimer = setTimeout(() => {
                if (finished || currentAttempt !== attempt) return;
                attempt++;
                enableButton.disabled = false;
                status.dataset.state = 'error';
                status.textContent = config.timeoutLabel || config.errorLabel;
            }, 45000);

            try {
                const result = permission() === 'granted' ? 'granted' : await requestPermission();
                if (finished || currentAttempt !== attempt) return;
                help.hidden = true;
                if (result !== 'granted') {
                    status.dataset.state = 'error';
                    status.textContent = result === 'denied' ? config.deniedLabel : (config.dismissedLabel || config.deniedLabel);
                    if (result === 'denied') showHelp(config.blockedHelpLabel || config.deniedLabel);
                    return;
                }

                await subscribe();
                if (finished || currentAttempt !== attempt) return;
                status.dataset.state = 'success';
                status.textContent = config.successLabel;
                enableButton.hidden = true; skipButton.hidden = true;
            } catch (error) {
                if (finished || currentAttempt !== attempt) return;
                console.error('Unable to enable offer notifications.', error);
                status.dataset.state = 'error';
                status.textContent = push.message(error);
            } finally {
                if (currentAttempt === attempt) {
                    clearTimeout(attemptTimer); clearTimeout(helpTimer);
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

        // A short delay lets the visitor see the page first; browsers also trust prompts after engagement.
        window.setTimeout(mountPrompt, Number(config.promptDelayMs ?? 6000));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }

    window.OzmanOfferNotifications = { subscribe, registration: registrationPromise, ready: () => ready && permission() === 'granted', prompt: () => {
        if (ready && permission() === 'granted') {
            window.dispatchEvent(new Event('ozman:offer-push-ready'));
            return;
        }
        mountPrompt(null, true);
    } };
})();
