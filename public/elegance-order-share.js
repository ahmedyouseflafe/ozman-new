(() => {
    'use strict';
    const config = window.OZMAN_ELEGANCE_CHECKOUT;
    if (!config) return;
    const $ = id => document.getElementById(id);
    const key = `ozman.cosmetics.${config.shopId}.photo-order`;
    const photos = new Map();
    let endpoint = '', payload = null, files = [], batch = 0, busy = false, preparing = null, revision = 0;
    const batchSize = 10;
    const status = (text, target) => { (target || $('eleganceCheckoutStatus')).textContent = text; };
    function refreshButton() {
        if (endpoint && !window.OzmanBeautyCart?.length) $('beautyWhatsappOrder')?.classList.remove('disabled');
    }
    function remember(url) {
        if (!url) return;
        const parsed = new URL(url, location.href);
        if (parsed.origin !== location.origin) throw new Error('رابط الطلب غير صالح.');
        if (endpoint !== parsed.href) { payload = null; files = []; batch = 0; }
        endpoint = parsed.href;
        try { sessionStorage.setItem(key, endpoint); } catch (_) {}
        refreshButton();
    }
    try { remember(sessionStorage.getItem(key)); } catch (_) {}
    async function request(url, json = false) {
        const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 20000);
        try {
            const response = await fetch(url, {signal:controller.signal, cache:json ? 'no-store' : 'default', credentials:json ? 'same-origin' : 'omit'});
            if (!response.ok) throw new Error('تعذّر تحميل الطلب أو صورته.');
            if (json) return await response.json();
            const blob = await response.blob();
            if (!blob.type.startsWith('image/') || blob.size > 20 * 1024 * 1024) throw new Error('صورة المنتج غير صالحة.');
            return blob;
        } finally { clearTimeout(timer); }
    }
    function photo(url) {
        if (photos.has(url)) return photos.get(url);
        const promise = (async () => {
            const blob = await request(url), objectUrl = URL.createObjectURL(blob);
            try {
                const image = new Image(); image.src = objectUrl; await image.decode();
                const scale = Math.min(1, 1600 / Math.max(image.naturalWidth, image.naturalHeight));
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
                canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
                const ctx = canvas.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
                const jpeg = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .9));
                if (!jpeg) throw new Error('تعذّر تجهيز الصورة.');
                return jpeg;
            } finally { URL.revokeObjectURL(objectUrl); }
        })();
        photos.set(url, promise);
        promise.catch(() => { if (photos.get(url) === promise) photos.delete(url); });
        return promise;
    }
    // Prepare selected images while the customer fills checkout, before the send tap.
    document.addEventListener('beauty:cart-changed', () => {
        refreshButton();
        let cursor = 0;
        const urls = [...new Set((window.OzmanBeautyCart || []).map(item => config.productImages?.[item.id]).filter(Boolean))];
        for (let worker = 0; worker < Math.min(3, urls.length); worker++) (async () => {
            while (cursor < urls.length) { const url = urls[cursor++]; try { await photo(url); } catch (_) {} }
        })();
    });
    async function prepare(url, data) {
        remember(url);
        const turn = ++revision;
        payload = null; files = []; batch = 0;
        const task = (async () => {
            const next = data || await request(endpoint, true);
            if (Number(next.shop_id) !== Number(config.shopId)) throw new Error('هذا الطلب تابع لمحل آخر.');
            const items = next.items.filter(item => item.image_url);
            const prepared = new Array(items.length);
            let cursor = 0;
            await Promise.all(Array.from({length:Math.min(3, items.length)}, async () => {
                while (cursor < items.length) {
                    const index = cursor++, item = items[index];
                    prepared[index] = new File([await photo(item.image_url)], `product-${item.product_id || index + 1}.jpg`, {type:'image/jpeg'});
                }
            }));
            if (turn !== revision) return;
            payload = next; files = prepared; batch = 0;
        })();
        preparing = task;
        try { await task; } finally { if (preparing === task) preparing = null; }
    }
    async function send(url = endpoint, target = null) {
        if (busy) return;
        busy = true;
        try {
            if (url !== endpoint || !payload) {
                status('جارٍ تجهيز الطلب والصور…', target);
                if (url === endpoint && preparing) await preparing;
                else await prepare(url);
            }
            const current = files.slice(batch * batchSize, (batch + 1) * batchSize);
            if (!current.length) {
                status('المنتجات في هذا الطلب ليس لها صور محفوظة.', target);
                return;
            }
            if (!navigator.share || !navigator.canShare?.({files:current})) {
                status('هذا المتصفح لا يدعم إرسال الصور. افتح المحل على الجوال بمتصفح يدعم مشاركة الصور؛ الطلب محفوظ.', target);
                return;
            }
            // No await before this call for an already prepared order/retry/wheel.
            await navigator.share({files:current, title:`طلب ${payload.order_number}`, text:payload.text});
            if ((batch + 1) * batchSize < files.length) {
                batch++;
                status(`اضغط إرسال الطلب لإرسال باقي الصور — دفعة ${batch + 1} من ${Math.ceil(files.length / batchSize)}.`, target);
            } else {
                batch = 0;
                status('اختار واتساب ومحادثة المحل وأكمل الإرسال هناك. لإعادة المشاركة اضغط إرسال الطلب.', target);
            }
        } catch (error) {
            if (error.name === 'AbortError') status('أُلغيت المشاركة. اضغط إرسال الطلب للمحاولة ثانية؛ طلبك محفوظ.', target);
            else if (error.name === 'NotAllowedError') status('الطلب والصور جاهزين. اضغط إرسال الطلب مرة ثانية لفتح المشاركة.', target);
            else status('تعذّر تجهيز أو مشاركة الصور. اضغط إرسال الطلب للمحاولة ثانية؛ لن يتكرر طلبك المحفوظ.', target);
        } finally { busy = false; refreshButton(); }
    }
    window.OzmanOrderPhotoShare = {remember, prepare, send, hasPending:() => !!endpoint};
})();
