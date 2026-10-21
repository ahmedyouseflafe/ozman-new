(() => {
    'use strict';
    const config = window.OZMAN_ELEGANCE_CHECKOUT;
    if (!config) return;
    const $ = id => document.getElementById(id), form = $('eleganceCheckoutForm');
    const orderButton = $('beautyWhatsappOrder'), status = $('eleganceCheckoutStatus');
    orderButton.textContent = 'تأكيد الطلب وتجهيز الصور';
    const key = `ozman.cosmetics.${config.shopId}`;
    const read = name => { try { return localStorage.getItem(name); } catch (_) { return null; } };
    const write = (name, value) => { try { localStorage.setItem(name, value); } catch (_) {} };
    let token = read(`${key}.guest-token`) || read(`${config.legacyKey}.token`) || '';
    if (!/^[a-f0-9]{64}$/.test(token)) {
        token = Array.from(crypto.getRandomValues(new Uint8Array(32)), byte => byte.toString(16).padStart(2, '0')).join('');
    }
    write(`${key}.guest-token`, token);
    // Keep the shared wheel's token accessor without loading the registration form.
    window.OzmanRestaurantCustomer = {token: () => token};
    document.querySelector('.cart-actions').before(form);
    const bar = document.createElement('div'); bar.className = 'cosmetics-checkout-bar'; document.body.append(bar);
    bar.append(document.querySelector('.beauty-cart'), document.querySelector('.cosmetics-purchase-control'));
    document.querySelector('.cosmetics-hero')?.after($('signupWheelReopen'));

    let saved = {};
    try { saved = JSON.parse(read(`${key}.checkout`) || 'null') || {}; } catch (_) {}
    if (!saved.customer_name) {
        try {
            const old = JSON.parse(read(config.legacyKey) || 'null');
            if (old) saved = {customer_name: old.name, customer_phone: old.whatsapp, customer_whatsapp: old.whatsapp, customer_address: old.address};
        } catch (_) {}
    }
    for (const name of ['customer_name', 'customer_phone', 'customer_whatsapp', 'customer_address']) {
        form.elements.namedItem(name).value = typeof saved[name] === 'string' ? saved[name] : '';
    }
    let point = null, locationRequest = 0, sending = false;
    const update = () => {
        const subtotal = (window.OzmanBeautyCart || []).reduce((sum, item) => sum + item.price * item.qty, 0);
        const discount = $('eleganceSkipReward').checked ? 0 : (window.OzmanSignupReward?.discount(subtotal) || 0);
        const gift = $('eleganceSkipReward').checked ? null : window.OzmanSignupReward?.gift();
        $('eleganceRewardSummary').textContent = gift ? `هدية مع أول طلب: ${gift.label}` : discount ? `خصم الترحيب: ${discount.toFixed(2)} ₪` : '';
        $('beautyCartSheetTotal').textContent = `${(subtotal - discount).toFixed(2)} ₪`;
        $('beautyCartTotal').textContent = `${(subtotal - discount).toFixed(2)} ₪`;
    };
    document.addEventListener('restaurant:reward-changed', update);
    document.addEventListener('beauty:cart-changed', update);
    $('eleganceSkipReward').addEventListener('change', update);
    $('eleganceLocation').addEventListener('click', () => {
        const request = ++locationRequest;
        const button = $('eleganceLocation'), message = $('eleganceLocationStatus');
        if (!navigator.geolocation) { message.textContent = 'تحديد الموقع غير متاح. يمكنك إرسال الطلب بدونه.'; return; }
        button.disabled = true; message.textContent = 'جارٍ تحديد موقعك…';
        navigator.geolocation.getCurrentPosition(position => {
            if (request !== locationRequest) return;
            const {latitude, longitude} = position.coords;
            button.disabled = false;
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || Math.abs(latitude) > 90 || Math.abs(longitude) > 180) {
                message.textContent = 'تعذّر تحديد الموقع. يمكنك إرسال الطلب بدونه.'; return;
            }
            point = {latitude, longitude};
            $('eleganceLocationClear').hidden = false;
            message.textContent = 'تمت إضافة الموقع إلى الطلب.';
        }, () => {
            if (request !== locationRequest) return;
            button.disabled = false;
            message.textContent = point ? 'تعذّر تحديث الموقع؛ الموقع السابق ما زال مرفقًا ويمكن إزالته.' : 'لم تتم إضافة الموقع. يمكنك إرسال الطلب بدونه.';
        }, {enableHighAccuracy: true, timeout: 12000, maximumAge: 60000});
    });
    $('eleganceLocationClear').addEventListener('click', () => {
        locationRequest++; point = null; $('eleganceLocation').disabled = false;
        $('eleganceLocationClear').hidden = true;
        $('eleganceLocationStatus').textContent = 'يمكنك إرسال الطلب بدون إضافة اللوكيشن.';
    });

    async function submit(payload) {
        const controller = new AbortController(), timeout = setTimeout(() => controller.abort(), 20000);
        try {
            const send = () => fetch(config.orderUrl, {method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                body: JSON.stringify(payload)});
            let response = await send();
            if (response.status === 419) {
                const refreshed = await fetch(config.csrfUrl, {credentials: 'same-origin', cache: 'no-store', signal: controller.signal});
                const fresh = await refreshed.json();
                if (refreshed.ok && fresh.token) { document.querySelector('meta[name="csrf-token"]').content = fresh.token; response = await send(); }
            }
            const data = await response.json();
            if (!response.ok) {
                if (data.errors?.welcome_reward) $('eleganceSkipRewardLabel').hidden = false;
                throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'تعذّر إرسال الطلب.');
            }
            return data;
        } finally { clearTimeout(timeout); }
    }
    orderButton.addEventListener('click', event => {
        event.preventDefault(); event.stopImmediatePropagation();
        if (!sending && window.OzmanBeautyCart?.length) form.requestSubmit();
    }, true);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (sending || !window.OzmanBeautyCart?.length || !form.reportValidity()) return;
        sending = true; orderButton.setAttribute('aria-disabled', 'true'); status.textContent = '';
        try {
            const skip = $('eleganceSkipReward').checked;
            if (!skip && !await window.OzmanSignupReward.beforeOrder()) return;
            const details = Object.fromEntries(['customer_name', 'customer_phone', 'customer_whatsapp', 'customer_address'].map(name => [name, form.elements.namedItem(name).value.trim()]));
            const data = await submit({...details, registration_token: token, skip_signup_reward: skip,
                latitude: point?.latitude ?? null, longitude: point?.longitude ?? null,
                items: window.OzmanBeautyCart.map(item => ({product_id: item.id, qty: item.qty}))});
            write(`${key}.checkout`, JSON.stringify(details));
            window.OzmanSignupReward.consumed();
            window.OzmanBeautyCart.splice(0); window.OzmanBeautyCartSync();
            status.textContent = `تم حفظ طلبك ${data.order_number} — ${Number(data.total).toFixed(2)} ₪`;
            $('closeBeautyCart').click();
            window.OzmanOrderPhotoShare.remember(data.order_share_url);
            if (!window.RestaurantPurchaseWheel?.ordered(data)) window.OzmanOrderPhotoShare.openFromUrl(data.order_share_url);
        } catch (error) {
            status.textContent = error.name === 'AbortError' ? 'تأخر تأكيد الطلب. تحقق من المحل قبل إعادة الإرسال.' : error.message;
        } finally { sending = false; orderButton.removeAttribute('aria-disabled'); }
    });
    document.dispatchEvent(new Event('restaurant:registered'));
})();
