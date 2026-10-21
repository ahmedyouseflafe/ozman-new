(() => {
    'use strict';
    const config = window.OZMAN_ELEGANCE_CHECKOUT;
    if (!config) return;
    const $ = id => document.getElementById(id);
    const dialog = $('eleganceOrderShareDialog'), status = $('eleganceShareStatus');
    const shareButton = $('eleganceSharePhotos'), resume = $('eleganceReshareOrder');
    const key = `ozman.cosmetics.${config.shopId}.photo-order`;
    let endpoint = '', payload = null, files = [], previews = [], batch = 0, busy = false, generation = 0;
    const batchSize = 10;
    document.querySelector('.cart-card h2').after(resume);
    const close = () => window.OzmanBeautyOverlay.close(dialog);
    $('eleganceShareClose').onclick = close;
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
    dialog.addEventListener('close', () => { if (!dialog.open && dialog.classList.contains('open')) close(); });
    function remember(url) {
        if (!url) return;
        const parsed = new URL(url, location.href);
        if (parsed.origin !== location.origin) return;
        endpoint = parsed.href; resume.hidden = false;
        try { sessionStorage.setItem(key, endpoint); } catch (_) {}
    }
    try { remember(sessionStorage.getItem(key)); } catch (_) {}
    resume.onclick = () => {
        $('closeBeautyCart').click();
        openFromUrl(endpoint);
    };
    async function request(url, json = false) {
        const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 20000);
        try {
            const response = await fetch(url, {signal:controller.signal, cache:'no-store', credentials:json ? 'same-origin' : 'omit'});
            if (!response.ok) throw new Error('تعذّر تحميل بيانات الطلب أو إحدى الصور.');
            if (json) return await response.json();
            const blob = await response.blob();
            if (!blob.type.startsWith('image/') || blob.size > 20 * 1024 * 1024) throw new Error('صورة المنتج غير صالحة للمشاركة.');
            return blob;
        } finally { clearTimeout(timer); }
    }
    async function photo(item, index) {
        const blob = await request(item.image_url), url = URL.createObjectURL(blob);
        try {
            const image = new Image(); image.src = url; await image.decode();
            const scale = Math.min(1, 1600 / Math.max(image.naturalWidth, image.naturalHeight));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
            canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
            const context = canvas.getContext('2d');
            context.fillStyle = '#fff'; context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0, canvas.width, canvas.height);
            const jpeg = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .9));
            if (!jpeg) throw new Error('تعذّر تجهيز الصورة.');
            return new File([jpeg], `product-${item.product_id || index + 1}.jpg`, {type:'image/jpeg'});
        } finally { URL.revokeObjectURL(url); }
    }
    function currentFiles() { return files.slice(batch * batchSize, (batch + 1) * batchSize); }
    function ready() {
        const supported = !!files.length && !!navigator.share && !!navigator.canShare?.({files:currentFiles()});
        shareButton.disabled = !supported;
        shareButton.textContent = files.length > batchSize
            ? `مشاركة الصور — دفعة ${batch + 1} من ${Math.ceil(files.length / batchSize)}` : 'مشاركة الصور والطلب';
        if (!supported && files.length) status.textContent = 'المتصفح الحالي لا يدعم مشاركة الصور. احفظ الصور من القائمة وأرفقها في واتساب، أو افتح المحل في متصفح جوالك الذي يدعم المشاركة.';
    }
    async function openFromUrl(url) {
        remember(url);
        if (!endpoint) return;
        const turn = ++generation;
        payload = null; files = []; batch = 0;
        previews.forEach(URL.revokeObjectURL); previews = [];
        $('eleganceShareImages').replaceChildren(); $('eleganceShareMessage').value = '';
        shareButton.disabled = true; shareButton.textContent = 'تجهيز الصور…';
        $('eleganceShareCopy').disabled = true; $('eleganceShareRetry').hidden = true; $('eleganceShareText').hidden = true;
        window.OzmanBeautyOverlay.open(dialog);
        status.textContent = 'تم حفظ الطلب. جارٍ تحميل صوره…';
        try {
            const data = await request(endpoint, true);
            if (turn !== generation) return;
            if (Number(data.shop_id) !== Number(config.shopId)) throw new Error('هذا الطلب تابع لمحل آخر.');
            payload = data;
            $('eleganceOrderShareTitle').textContent = `صور الطلب ${data.order_number}`;
            $('eleganceShareMessage').value = data.text;
            $('eleganceShareCopy').disabled = false;
            $('eleganceShareText').href = data.whatsapp_url || '#';
            $('eleganceShareText').hidden = !data.whatsapp_url;
            const withImages = data.items.filter(item => item.image_url);
            const prepared = new Array(withImages.length);
            let cursor = 0;
            // Limit simultaneous decoding on phones; keep product order stable.
            await Promise.all(Array.from({length:Math.min(3, withImages.length)}, async () => {
                while (cursor < withImages.length && turn === generation) {
                    const index = cursor++;
                    prepared[index] = await photo(withImages[index], index);
                }
            }));
            if (turn !== generation) return;
            files = prepared;
            files.forEach((file, index) => {
                const url = URL.createObjectURL(file); previews.push(url);
                const link = document.createElement('a'); link.href = url; link.download = file.name;
                const img = document.createElement('img'); img.src = url; img.alt = withImages[index].name;
                const label = document.createElement('span'); label.textContent = `${withImages[index].name} × ${withImages[index].qty} — حفظ الصورة`;
                link.append(img, label); $('eleganceShareImages').append(link);
            });
            const missing = data.items.filter(item => !item.image_url).map(item => item.name);
            status.textContent = files.length ? `جاهز: ${files.length} صورة. اختار واتساب ثم محادثة المحل.` : 'المنتجات في هذا الطلب ليس لها صور محفوظة.';
            ready();
            if (missing.length) status.textContent += ` المنتجات بدون صورة: ${missing.join('، ')}.`;
        } catch (_) {
            if (turn !== generation) return;
            files = []; shareButton.disabled = true;
            status.textContent = 'تعذّر تجهيز الصور. طلبك محفوظ؛ أعد المحاولة بدون إنشاء طلب جديد. إذا انتهت صلاحية الطلب القديم، تواصل مع المحل.';
            $('eleganceShareRetry').hidden = false;
        }
    }
    $('eleganceShareRetry').onclick = () => openFromUrl(endpoint);
    shareButton.onclick = async () => {
        if (busy || !payload || !currentFiles().length) return;
        busy = true; shareButton.disabled = true;
        try {
            // Call synchronously from the tap: fetching here would lose iOS user activation.
            await navigator.share({files:currentFiles(), title:`طلب ${payload.order_number}`, text:payload.text});
            if ((batch + 1) * batchSize < files.length) {
                batch++; status.textContent = 'تابع بمشاركة الدفعة التالية في نفس محادثة واتساب.';
            } else {
                batch = 0; status.textContent = 'تم فتح المشاركة. تأكد من إرسال الصور وتفاصيل الطلب داخل واتساب. يمكنك مشاركتها مرة ثانية من هنا.';
            }
        } catch (error) {
            status.textContent = error.name === 'AbortError' ? 'تم إلغاء المشاركة. الصور والطلب جاهزان للمحاولة مرة ثانية.' : 'تعذّرت مشاركة الصور. جرّب مرة ثانية أو احفظ الصور وأرفقها يدويًا.';
        } finally { busy = false; ready(); }
    };
    $('eleganceShareCopy').onclick = async () => {
        if (!payload) return;
        try { await navigator.clipboard.writeText(payload.text); status.textContent = 'تم نسخ تفاصيل الطلب ورابط المحل.'; }
        catch (_) {
            $('eleganceShareMessage').closest('details').open = true;
            $('eleganceShareMessage').focus(); $('eleganceShareMessage').select();
            status.textContent = 'حدّد النص وانسخه لإرساله مع الصور.';
        }
    };
    window.OzmanOrderPhotoShare = {remember, openFromUrl};
})();
