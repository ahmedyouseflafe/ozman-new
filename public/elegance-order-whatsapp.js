(() => {
    'use strict';
    const config = window.OZMAN_ELEGANCE_CHECKOUT;
    if (!config) return;
    const $ = id => document.getElementById(id);
    // Retain the signed lookup for retrying an existing order after returning/reloading.
    const key = `ozman.cosmetics.${config.shopId}.photo-order`;
    let endpoint = '', whatsappUrl = '', busy = false;
    function refreshButton() {
        if (endpoint && !window.OzmanBeautyCart?.length) $('beautyWhatsappOrder')?.classList.remove('disabled');
    }
    function validWhatsapp(value) {
        const url = new URL(value);
        if (url.protocol !== 'https:' || url.hostname !== 'wa.me' || !/^\/\d+$/.test(url.pathname)) throw new Error('رابط واتساب غير صالح.');
        return url.href;
    }
    function remember(url, directUrl = null) {
        if (!url) return;
        const parsed = new URL(url, location.href);
        if (parsed.origin !== location.origin) throw new Error('رابط الطلب غير صالح.');
        if (endpoint !== parsed.href) whatsappUrl = '';
        endpoint = parsed.href;
        if (directUrl) whatsappUrl = validWhatsapp(directUrl);
        try { sessionStorage.setItem(key, endpoint); } catch (_) {}
        refreshButton();
    }
    try { remember(sessionStorage.getItem(key)); } catch (_) {}
    document.addEventListener('beauty:cart-changed', refreshButton);
    async function send(target = null) {
        if (busy || !endpoint) return;
        busy = true;
        const status = target || $('eleganceCheckoutStatus');
        try {
            if (!whatsappUrl) {
                const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 20000);
                try {
                    const response = await fetch(endpoint, {signal:controller.signal, credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json'}});
                    if (!response.ok) throw new Error('تعذّر تحميل الطلب.');
                    const data = await response.json();
                    if (Number(data.shop_id) !== Number(config.shopId)) throw new Error('الطلب تابع لمحل آخر.');
                    whatsappUrl = validWhatsapp(data.whatsapp_url);
                } finally { clearTimeout(timer); }
            }
            status.textContent = 'جارٍ فتح واتساب المحل بالطلب. اضغط إرسال داخل واتساب لإتمامه.';
            // Same-tab navigation opens the specified shop conversation on mobile and desktop.
            window.location.assign(whatsappUrl);
        } catch (_) {
            status.textContent = 'طلبك محفوظ. تعذّر فتح واتساب المحل؛ اضغط إرسال الطلب للمحاولة ثانية بدون تكرار الطلب.';
        } finally { busy = false; refreshButton(); }
    }
    window.OzmanOrderWhatsapp = {remember, send, hasPending:() => !!endpoint};
})();
