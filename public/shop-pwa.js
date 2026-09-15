(() => {
    'use strict';

    const config = window.OZMAN_SHOP_PWA;
    if (!config) return;

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
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    let deferredInstallPrompt = null;

    const setStatus = (message, state = '') => {
        statusElements.forEach((element) => {
            element.textContent = message;
            element.dataset.state = state;
        });
    };

    const openInSupportedBrowser = () => {
        if (isAndroid) {
            const url = new URL(window.location.href);
            const scheme = url.protocol.replace(':', '');
            window.location.href = `intent://${url.host}${url.pathname}${url.search}#Intent;scheme=${scheme};package=com.android.chrome;end`;
            return;
        }

        setStatus(config.labels.iosInstructions, 'ready');
    };

    const install = async () => {
        if (isStandalone) {
            setStatus(config.labels.installed, 'success');
            return;
        }

        if (isEmbeddedBrowser) {
            openInSupportedBrowser();
            return;
        }

        if (!deferredInstallPrompt) {
            setStatus(isIos ? config.labels.iosInstructions : config.labels.browserInstructions, 'ready');
            return;
        }

        deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
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

    if (isEmbeddedBrowser) {
        installButtons.forEach((button) => {
            button.textContent = isAndroid ? config.labels.openChrome : config.labels.iosInstall;
        });
        setStatus(isAndroid ? config.labels.openChromeHelp : config.labels.iosInstructions, 'ready');
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
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
                if (isStandalone) {
                    installButtons.forEach((button) => button.hidden = true);
                    setStatus(config.labels.installed, 'success');
                    return;
                }

                if (!isEmbeddedBrowser) {
                    window.setTimeout(() => {
                        if (!deferredInstallPrompt) {
                            setStatus(isIos ? config.labels.iosInstructions : config.labels.browserInstructions, 'ready');
                        }
                    }, 1200);
                }
            })
            .catch((error) => {
                console.error('Shop app service worker registration failed.', error);
                setStatus(config.labels.error, 'error');
            });
    } else {
        setStatus(config.labels.unsupported, 'error');
    }

    window.OzmanShopPwa = { install, share };
})();
