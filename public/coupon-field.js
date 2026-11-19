/* A discount-code row for the website's checkouts: the customer types the shop's code, taps "apply",
   and sees what it gives before ordering. The server checks the code again when the order is sent. */
(() => {
    const config = window.OzmanCouponConfig;
    if (!config || window.OzmanCoupon) return;
    const rtl = (document.documentElement.dir || 'rtl') === 'rtl';
    const text = Object.assign({
        placeholder: 'عندك كود خصم؟', apply: 'طبّق', remove: 'إزالة', checking: '…',
        failed: 'الكود غير صحيح أو انتهى.', network: 'تعذّر فحص الكود. جرّب مرة ثانية.',
    }, config.text || {});
    let applied = null;
    const rows = [];

    const style = document.createElement('style');
    style.textContent = `.oz-coupon{margin:10px 0;font:inherit;color:inherit;direction:${rtl ? 'rtl' : 'ltr'}}
.oz-coupon-row{display:flex;gap:8px}
.oz-coupon .oz-coupon-row>input{flex:1 1 auto!important;width:auto!important;min-width:0!important;min-height:42px;margin:0!important;padding:0 12px;border:1px solid rgba(127,127,127,.4);border-radius:12px;background:rgba(127,127,127,.08);color:inherit;font:inherit;letter-spacing:1px}
.oz-coupon .oz-coupon-row>input:not(:placeholder-shown){text-transform:uppercase}
.oz-coupon .oz-coupon-row>input:disabled{opacity:.8}
.oz-coupon .oz-coupon-row>button{flex:0 0 auto!important;width:auto!important;min-height:42px;margin:0!important;padding:0 16px;border:1px solid currentColor;border-radius:12px;background:transparent!important;color:inherit;font:inherit;font-weight:800;cursor:pointer;white-space:nowrap;box-shadow:none}
.oz-coupon .oz-coupon-row>button:disabled{opacity:.6;cursor:wait}
.oz-coupon-note{min-height:0;margin-top:5px;font-size:.85em;font-weight:700}
.oz-coupon-note.ok{color:#2fbf71}.oz-coupon-note.bad{color:#e5484d}`;
    document.head.appendChild(style);

    const render = () => rows.forEach(({input, button, note}) => {
        input.disabled = !!applied;
        button.textContent = applied ? text.remove : text.apply;
        if (applied) { input.value = applied.code; note.className = 'oz-coupon-note ok'; note.textContent = '✓ ' + applied.label; }
    });

    const check = async (input, button, note) => {
        if (applied) { applied = null; note.textContent = ''; render(); input.focus(); return; }
        const code = input.value.trim();
        if (!code) { input.focus(); return; }
        button.disabled = true; button.textContent = text.checking; note.textContent = '';
        try {
            const send = () => fetch(config.checkUrl, {method: 'POST', credentials: 'same-origin',
                headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf},
                body: JSON.stringify({coupon_code: code})});
            let response = await send();
            if (response.status === 419 && config.csrfUrl) {
                const fresh = await fetch(config.csrfUrl, {credentials: 'same-origin', cache: 'no-store', headers: {Accept: 'application/json'}});
                if (fresh.ok) { config.csrf = (await fresh.json()).token; response = await send(); }
            }
            const data = await response.json().catch(() => ({}));
            if (!response.ok) { note.className = 'oz-coupon-note bad'; note.textContent = Object.values(data.errors || {}).flat()[0] || data.message || text.failed; return; }
            applied = data.coupon;
        } catch (error) {
            note.className = 'oz-coupon-note bad'; note.textContent = text.network;
        } finally {
            button.disabled = false; render();
        }
    };

    window.OzmanCoupon = {
        /** Puts the code row just before `anchor` (the checkout's send button, usually). */
        mount(anchor) {
            if (!anchor || anchor.previousElementSibling?.classList?.contains('oz-coupon')) return;
            const box = document.createElement('div');
            box.className = 'oz-coupon';
            box.innerHTML = '<div class="oz-coupon-row"><input type="text" maxlength="40" autocomplete="off" autocapitalize="characters" spellcheck="false"><button type="button"></button></div><div class="oz-coupon-note" aria-live="polite"></div>';
            const input = box.querySelector('input'), button = box.querySelector('button'), note = box.querySelector('.oz-coupon-note');
            input.placeholder = text.placeholder;
            input.setAttribute('aria-label', text.placeholder);
            button.addEventListener('click', () => check(input, button, note));
            input.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); check(input, button, note); } });
            rows.push({input, button, note});
            anchor.parentNode.insertBefore(box, anchor);
            render();
        },
        /** The code to send with the order: the applied one, or whatever was typed (the server checks it either way). */
        code() {
            if (applied) return applied.code;
            const typed = rows.map(row => row.input.value.trim()).find(Boolean);
            return typed || null;
        },
        /** After a successful order the code is used; clear it for the next basket. */
        reset() { applied = null; rows.forEach(row => { row.input.value = ''; row.note.textContent = ''; }); render(); },
    };
})();
