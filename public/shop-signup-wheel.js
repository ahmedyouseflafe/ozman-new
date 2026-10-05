(() => {
    const $ = id => document.getElementById(id);
    const dialog = $('shopSignupWheel');
    if (!dialog) return;
    const ui = window.OZMAN_SIGNUP_WHEEL_COPY;
    let reward = null, busy = false, failed = false, loading = null;
    const notify = () => document.dispatchEvent(new Event('restaurant:reward-changed'));
    const error = message => { $('signupWheelError').textContent = message; $('signupWheelError').hidden = !message; };
    const request = async spin => {
        const token = window.OzmanRestaurantCustomer?.token();
        if (!token) return null;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(dialog.dataset.url, {method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
                body: JSON.stringify({registration_token: token, spin})});
            if (!response.ok) throw new Error('reward');
            const data = await response.json();
            return data.reward || null;
        } finally { clearTimeout(timeout); }
    };
    const open = () => { if (!dialog.open) dialog.showModal(); };
    const draw = () => {
        $('signupWheelTitle').textContent = reward?.title || ui.open;
        $('signupWheelWrap').hidden = !reward;
        $('signupWheelReopen').hidden = !reward && !failed;
        $('signupWheelSpin').hidden = Boolean(reward && reward.selected_index !== null);
        $('signupWheelSpin').textContent = failed ? ui.retry : ui.spin;
        $('signupWheelResult').hidden = !reward || reward.selected_index === null;
        $('signupWheelGiftImage').hidden = true;
        if (!reward) return;
        const segments = reward.segments, step = 360 / segments.length;
        const disc = $('signupWheelDisc');
        disc.replaceChildren();
        disc.style.background = `conic-gradient(${segments.map((s, i) => `${/^#[0-9a-f]{6}$/i.test(s.color) ? s.color : '#00cfe8'} ${step*i}deg ${step*(i+1)}deg`).join(',')})`;
        segments.forEach((segment, index) => {
            const label = document.createElement('span');label.className = 'signup-wheel-label';label.dir = 'auto';label.textContent = segment.label;
            label.style.transform = `translateY(-50%) rotate(${step*(index+.5)-90}deg)`;
            if (segment.discount_type === 'gift' && segment.gift_image) {
                label.classList.add('has-gift-image');
                if (segments.length > 8) label.classList.add('dense');
                const angle = step*(index+.5)*Math.PI/180;
                label.style.left = `${50 + 32*Math.sin(angle)}%`;
                label.style.top = `${50 - 32*Math.cos(angle)}%`;
                label.style.transform = 'translate(-50%,-50%)';
                const picture = document.createElement('img');picture.src = segment.gift_image;picture.alt = '';
                picture.addEventListener('error', () => {picture.hidden = true;});
                const caption = document.createElement('span');caption.textContent = segment.label;
                label.replaceChildren(picture, caption);
            }
            disc.append(label);
        });
        if (reward.selected_index !== null) {
            disc.style.transform = `rotate(${1800 - step*(reward.selected_index+.5)}deg)`;
            const selected = segments[reward.selected_index];
            const value = selected.discount_type === 'gift' ? '' : ` — ${selected.discount_value}${selected.discount_type === 'percent' ? '%' : ' ₪'}`;
            $('signupWheelResult').textContent = `${ui.result} ${selected.label}${value}. ${ui.saved}`;
            if (selected.discount_type === 'gift' && selected.gift_image) {
                $('signupWheelGiftImage').src = selected.gift_image;
                $('signupWheelGiftImage').alt = selected.label;
                $('signupWheelGiftImage').hidden = false;
            }
        }
    };
    const refresh = (autoOpen = false) => {
        if (loading) return loading;
        loading = (async () => {
            try { reward = await request(false);failed = false;error('');draw();notify();if(autoOpen && reward?.selected_index === null)open();return true; }
            catch (_) { failed = true;error(ui.error);draw();if(autoOpen)open();return false; }
            finally { loading = null; }
        })();
        return loading;
    };
    $('signupWheelSpin').addEventListener('click', async () => {
        if (busy) return;
        if (failed) { await refresh(true); return; }
        busy = true;$('signupWheelSpin').disabled = true;$('signupWheelClose').disabled = true;error('');
        $('signupWheelSpin').textContent = ui.busy;
        try {
            const result = await request(true);
            if (!result) {reward = null;draw();notify();dialog.close();return;}
            const step = 360 / result.segments.length;
            $('signupWheelDisc').style.transform = `rotate(${1800 - step*(result.selected_index+.5)}deg)`;
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            await new Promise(resolve => setTimeout(resolve, reduce ? 0 : 3700));
            reward = result;failed = false;draw();notify();
        } catch (_) {failed = true;error(ui.error);draw();}
        finally {busy = false;$('signupWheelSpin').disabled = false;$('signupWheelClose').disabled = false;}
    });
    $('signupWheelClose').addEventListener('click', () => { if(!busy)dialog.close(); });
    dialog.addEventListener('cancel', event => { if(busy)event.preventDefault(); });
    $('signupWheelReopen').addEventListener('click', () => {draw();open();});
    document.addEventListener('restaurant:registered', () => refresh(true));
    // Give the reminder its own row below the header, clear of the logo and cart.
    document.querySelector('.restaurant-hero-layout')?.after($('signupWheelReopen'));
    window.OzmanSignupReward = {
        discountLabel: ui.discount,
        gift: () => {
            const selected = reward?.segments[reward?.selected_index];
            return selected?.discount_type === 'gift' ? selected : null;
        },
        discount: subtotal => {
            const segment = reward?.selected_index !== null ? reward?.segments[reward?.selected_index] : null;
            if (!segment || segment.discount_type === 'gift') return 0;
            return Math.round(Math.min(subtotal, segment.discount_type === 'percent' ? subtotal*segment.discount_value/100 : segment.discount_value)*100)/100;
        },
        beforeOrder: async () => {
            if (!window.OzmanRestaurantCustomer?.token()) return true;
            if (busy) {open();return false;}
            if (!await refresh()) {open();return false;}
            if (reward?.selected_index === null) {error(ui.pending);open();return false;}
            return true;
        },
        consumed: () => {reward = null;failed = false;draw();notify();},
    };
})();
