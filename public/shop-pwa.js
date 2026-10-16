(() => {
    'use strict';

    const config = window.OZMAN_SHOP_PWA;
    if (!config) return;

    const isStandalone = ['standalone', 'fullscreen', 'minimal-ui', 'window-controls-overlay']
        .some(mode => window.matchMedia(`(display-mode: ${mode})`).matches) || window.navigator.standalone === true;

    const installButtons = [...document.querySelectorAll('[data-shop-pwa-install]')];
    const shareButtons = [...document.querySelectorAll('[data-shop-pwa-share]')];
    const statusElements = [...document.querySelectorAll('[data-shop-pwa-status]')];
    const userAgent = navigator.userAgent || '';
    const isAndroid = /android/i.test(userAgent);
    const isIos = /iphone|ipad|ipod/i.test(userAgent);
    const isSamsungInternet = /SamsungBrowser/i.test(userAgent);
    const isEmbeddedBrowser = /(?:;\s*wv\)|\bwv\b|FBAN|FBAV|Instagram|Line\/|OzmanApp)/i.test(userAgent)
        || isSamsungInternet
        || (isIos && !/Safari/i.test(userAgent));
    let deferredInstallPrompt = null;

    const setStatus = (message, state = '') => {
        statusElements.forEach((element) => {
            element.textContent = message;
            element.dataset.state = state;
        });
    };

    const openInSupportedBrowser = () => {
        if (isAndroid) {
            const url = new URL(config.installUrl || config.shareUrl, window.location.origin);
            const scheme = url.protocol.replace(':', '');
            setStatus(config.labels.openChromeHelp, 'ready');
            window.location.href = `intent://${url.host}${url.pathname}${url.search}#Intent;scheme=${scheme};package=com.android.chrome;S.browser_fallback_url=${encodeURIComponent(url.href)};end`;
            return;
        }

        setStatus(isIos ? config.labels.iosInstructions : config.labels.browserInstructions, 'ready');
    };

    const install = async () => {
        if (isEmbeddedBrowser || isStandalone) {
            openInSupportedBrowser();
            return;
        }

        if (!deferredInstallPrompt) {
            setStatus(isIos ? config.labels.iosInstructions : config.labels.browserInstructions, 'ready');
            return;
        }

        const prompt = deferredInstallPrompt;
        deferredInstallPrompt = null;
        try {
            await prompt.prompt();
            const choice = await prompt.userChoice;
            if (choice.outcome !== 'accepted') setStatus(config.labels.cancelled, 'ready');
        } catch (error) {
            console.error('Shop app installation failed.', error);
            setStatus(config.labels.browserInstructions, 'ready');
        }
    };

    const share = async () => {
        const shareData = {
            title: config.shopName,
            text: config.labels.shareText,
            url: config.shareUrl,
        };

        try {
            if (navigator.share) {
                await navigator.share(shareData);
            } else {
                await navigator.clipboard.writeText(config.shareUrl);
                setStatus(config.labels.copied, 'success');
            }
        } catch (error) {
            if (error?.name !== 'AbortError') {
                setStatus(config.labels.copyFailed, 'error');
            }
        }
    };

    installButtons.forEach((button) => button.addEventListener('click', install));
    shareButtons.forEach((button) => button.addEventListener('click', share));
    document.querySelectorAll('[data-shop-pwa-copy]').forEach((button) => button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(config.installUrl || config.shareUrl);
            setStatus(config.labels.copied, 'success');
        } catch (_) {
            setStatus(`${config.labels.copyFailed} ${config.installUrl || config.shareUrl}`, 'ready');
        }
    }));

    if (isEmbeddedBrowser || isStandalone) {
        installButtons.forEach((button) => {
            button.textContent = isAndroid ? config.labels.openChrome : config.labels.iosInstall;
        });
        setStatus(isAndroid ? config.labels.openChromeHelp : config.labels.iosInstructions, 'ready');
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        if (isStandalone || isEmbeddedBrowser) return;
        deferredInstallPrompt = event;
        setStatus(config.labels.ready, 'ready');
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        installButtons.forEach((button) => button.hidden = true);
        setStatus(config.labels.installed, 'success');
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(config.serviceWorkerUrl, { scope: '/' })
            .then(() => {
                if (!isEmbeddedBrowser && !isStandalone) {
                    window.setTimeout(() => {
                        if (!deferredInstallPrompt) {
                            setStatus(isIos ? config.labels.iosInstructions : config.labels.browserInstructions, 'ready');
                        }
                    }, 1200);
                }
            })
            .catch((error) => {
                console.error('Shop app service worker registration failed.', error);
                if (!isEmbeddedBrowser && !isStandalone) setStatus(config.labels.error, 'error');
            });
    } else {
        if (!isEmbeddedBrowser && !isStandalone) setStatus(config.labels.unsupported, 'error');
    }

    window.OzmanShopPwa = { install, share };
})();
