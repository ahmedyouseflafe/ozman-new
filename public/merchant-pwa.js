(() => {
    'use strict';

    const config = window.OZMAN_MERCHANT_PWA;
    if (!config) return;

    const installButtons = [...document.querySelectorAll('[data-pwa-install]')];
    const notificationButtons = [...document.querySelectorAll('[data-pwa-notifications]')];
    const statusElements = [...document.querySelectorAll('[data-pwa-status]')];
    const userAgent = navigator.userAgent || '';
    const isAndroid = /android/i.test(userAgent);
    const isIos = /iphone|ipad|ipod/i.test(userAgent);
    const isEmbeddedBrowser = /(?:;\s*wv\)|\bwv\b|FBAN|FBAV|Instagram|Line\/|OzmanApp)/i.test(userAgent)
        || (isIos && !/Safari/i.test(userAgent));
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    let deferredInstallPrompt = null;

    const mountStandaloneNotificationPrompt = () => {
        if (!isStandalone || !('Notification' in window) || Notification.permission === 'granted') return;

        const style = document.createElement('style');
        style.textContent = '.merchant-pwa-notification-prompt{position:fixed;z-index:2147483646;right:max(14px,env(safe-area-inset-right));left:max(14px,env(safe-area-inset-left));bottom:max(14px,env(safe-area-inset-bottom));display:flex;align-items:center;justify-content:center;gap:10px;padding:12px;border:1px solid rgba(11,220,243,.38);border-radius:18px;background:rgba(5,14,19,.96);box-shadow:0 18px 55px rgba(0,0,0,.48);font-family:Cairo,Arial,sans-serif;direction:rtl}.merchant-pwa-notification-prompt button{min-height:44px;padding:9px 15px;border:0;border-radius:13px;background:#0bdcf3;color:#001318;font:800 13px Cairo,Arial,sans-serif}.merchant-pwa-notification-prompt p{margin:0;color:#d9e8ee;font-size:11px;font-weight:700;line-height:1.55}.merchant-pwa-notification-prompt p[data-state=error]{color:#ff8b98}@media(min-width:700px){.merchant-pwa-notification-prompt{right:20px;left:auto;max-width:430px}}';
        document.head.appendChild(style);

        const prompt = document.createElement('aside');
        prompt.className = 'merchant-pwa-notification-prompt';
        prompt.dataset.pwaNotificationPrompt = '';
        prompt.innerHTML = '<button type="button" data-pwa-notifications>تفعيل الإشعارات</button><p data-pwa-status>فعّل إشعارات الطلبات الجديدة لهذا المحل.</p>';
        document.body.appendChild(prompt);

        notificationButtons.push(prompt.querySelector('[data-pwa-notifications]'));
        statusElements.push(prompt.querySelector('[data-pwa-status]'));
    };

    mountStandaloneNotificationPrompt();

    const setStatus = (message, state = '') => {
        statusElements.forEach((element) => {
            element.textContent = message;
            element.dataset.state = state;
        });
    };

    const openInChrome = () => {
        if (!isAndroid) {
            setStatus('افتح هذه الصفحة في Safari، ثم اضغط مشاركة واختر «إضافة إلى الشاشة الرئيسية».', 'ready');
            return;
        }

        const url = new URL(window.location.href);
        const scheme = url.protocol.replace(':', '');
        window.location.href = `intent://${url.host}${url.pathname}${url.search}#Intent;scheme=${scheme};package=com.android.chrome;end`;
    };

    if (isEmbeddedBrowser) {
        installButtons.forEach((button) => {
            button.textContent = isAndroid ? 'فتح في Chrome للتثبيت' : 'طريقة التثبيت على iPhone';
            button.addEventListener('click', openInChrome);
        });
        notificationButtons.forEach((button) => {
            button.addEventListener('click', () => setStatus('ثبّت التطبيق من Chrome أو Safari أولاً، ثم فعّل الإشعارات من النسخة المثبّتة.', 'ready'));
        });
        setStatus(
            isAndroid
                ? 'أنت داخل تطبيق Ozman. اضغط «فتح في Chrome للتثبيت» لإكمال التثبيت.'
                : 'أنت داخل تطبيق Ozman. افتح الصفحة في Safari ثم أضفها إلى الشاشة الرئيسية.',
            'ready',
        );
        return;
    }

    if (!('serviceWorker' in navigator)) {
        setStatus('هذا المتصفح لا يدعم تثبيت التطبيق. افتح الصفحة في Chrome أو Safari.', 'error');
        installButtons.forEach((button) => button.addEventListener('click', openInChrome));
        return;
    }

    const registrationPromise = navigator.serviceWorker.register(config.serviceWorkerUrl, { scope: '/' });

    const urlBase64ToUint8Array = (value) => {
        const padding = '='.repeat((4 - value.length % 4) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(base64);
        return Uint8Array.from([...raw].map((character) => character.charCodeAt(0)));
    };

    const storeSubscription = async (subscription) => {
        const serialized = subscription.toJSON();
        serialized.contentEncoding = window.PushManager?.supportedContentEncodings?.[0] || 'aes128gcm';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const response = await fetch(config.subscribeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ shop_id: config.shopId, subscription: serialized }),
        });

        if (!response.ok) throw new Error(`subscription:${response.status}`);
        return subscription;
    };

    const subscribeToNotifications = async (askPermission = true) => {
        if (!('Notification' in window) || !('PushManager' in window)) {
            setStatus('هذا الجهاز لا يدعم إشعارات الويب.', 'error');
            return null;
        }

        let permission = Notification.permission;
        if (askPermission && permission === 'default') permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            setStatus('يجب السماح بالإشعارات من إعدادات المتصفح.', 'error');
            return null;
        }

        setStatus('جارٍ ربط الإشعارات بهذا المحل…', 'loading');
        const registration = await registrationPromise;
        let subscription = await registration.pushManager.getSubscription();
        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(config.vapidPublicKey),
            });
        }

        await storeSubscription(subscription);
        notificationButtons.forEach((button) => {
            button.classList.add('enabled');
            button.innerHTML = '<span aria-hidden="true">✓</span> الإشعارات مفعّلة';
        });
        setStatus('التطبيق مربوط بمحلك والإشعارات مفعّلة.', 'success');
        window.setTimeout(() => {
            document.querySelector('[data-pwa-notification-prompt]')?.remove();
        }, 1200);
        return subscription;
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;
        installButtons.forEach((button) => button.hidden = false);
        setStatus('التطبيق جاهز للتثبيت على هذا الجهاز.', 'ready');
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        installButtons.forEach((button) => {
            button.hidden = true;
        });
        setStatus('تم تثبيت تطبيق المحل بنجاح.', 'success');
    });

    installButtons.forEach((button) => button.addEventListener('click', async () => {
        if (isStandalone) {
            setStatus('تطبيق المحل مثبت ومفتوح الآن.', 'success');
            return;
        }

        if (!deferredInstallPrompt) {
            setStatus(
                isIos
                    ? 'على iPhone: اضغط مشاركة ثم «إضافة إلى الشاشة الرئيسية».'
                    : 'افتح قائمة المتصفح واختر «تثبيت التطبيق» أو «إضافة إلى الشاشة الرئيسية».',
                'ready',
            );
            return;
        }

        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
    }));

    notificationButtons.forEach((button) => button.addEventListener('click', async () => {
        try {
            await subscribeToNotifications(true);
        } catch (error) {
            console.error('Unable to enable merchant PWA notifications.', error);
            setStatus('تعذر تفعيل الإشعارات. تحقق من الاتصال وحاول مجددًا.', 'error');
        }
    }));

    registrationPromise.then(() => {
        if (isStandalone) {
            installButtons.forEach((button) => button.hidden = true);
            setStatus('تطبيق المحل مثبت على هذا الجهاز.', 'success');
        }

        if ('Notification' in window && Notification.permission === 'granted') {
            subscribeToNotifications(false).catch((error) => {
                console.error('Unable to refresh merchant PWA subscription.', error);
            });
        }

        if (!isStandalone && !deferredInstallPrompt) {
            window.setTimeout(() => {
                if (!deferredInstallPrompt) {
                    setStatus(
                        isIos
                            ? 'التطبيق جاهز. اضغط مشاركة ثم اختر «إضافة إلى الشاشة الرئيسية».'
                            : 'تم تجهيز التطبيق. اضغط زر التثبيت، أو اختر «تثبيت التطبيق» من قائمة Chrome.',
                        'ready',
                    );
                }
            }, 1500);
        }
    }).catch((error) => {
        console.error('Merchant PWA service worker registration failed.', error);
        setStatus('تعذر تجهيز التطبيق للتثبيت على هذا الجهاز.', 'error');
    });

    window.OzmanMerchantPwa = { subscribeToNotifications, registration: registrationPromise };
})();
