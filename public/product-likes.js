// Product hearts on every storefront: any [data-like-product] button, including cards built later by JS.
(() => {
    'use strict';
    if (window.OzmanLikes) return;
    const config = window.OZMAN_LIKES;
    if (!config) return;
    const counts = {}, liked = new Set(), queued = new Set();
    let timer = null;
    const short = (n) => n >= 1000 ? `${(n / 1000).toFixed(n >= 10000 ? 0 : 1).replace(/\.0$/, '')}k` : String(n);
    const buttons = (id) => document.querySelectorAll(`[data-like-product="${CSS.escape(String(id))}"]`);
    const paint = (button) => {
        const id = button.dataset.likeProduct, on = liked.has(id), count = counts[id] || 0;
        button.classList.toggle('is-liked', on);
        button.setAttribute('aria-pressed', on ? 'true' : 'false');
        const label = button.querySelector('.oz-like-count');
        if (label) label.textContent = count > 0 ? short(count) : '';
    };
    const paintAll = (id) => buttons(id).forEach(paint);
    const load = () => {
        const ids = [...queued]; queued.clear();
        if (!ids.length) return;
        fetch(`${config.stateUrl}?ids=${ids.join(',')}`, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then((r) => r.ok ? r.json() : null)
            .then((data) => {
                if (!data) return;
                Object.entries(data.counts || {}).forEach(([id, n]) => { counts[id] = Number(n) || 0; });
                (data.liked || []).forEach((id) => liked.add(String(id)));
                ids.forEach(paintAll);
            })
            .catch(() => {});
    };
    const hydrate = (button) => {
        if (button.dataset.likeReady) return;
        button.dataset.likeReady = '1';
        const id = button.dataset.likeProduct;
        if (!id) return;
        const host = button.parentElement;
        if (button.dataset.likeVariant !== 'inline' && host && getComputedStyle(host).position === 'static') host.style.position = 'relative';
        if (!(id in counts) && button.dataset.likeCount) counts[id] = Number(button.dataset.likeCount) || 0;
        paint(button);
        queued.add(id);
        clearTimeout(timer); timer = setTimeout(load, 80);
    };
    const scan = (root) => {
        if (root.nodeType !== 1) return;
        if (root.matches('[data-like-product]')) hydrate(root);
        root.querySelectorAll('[data-like-product]').forEach(hydrate);
    };
    const toggle = async (button) => {
        const id = button.dataset.likeProduct;
        if (button.dataset.busy) return;
        button.dataset.busy = '1';
        const was = liked.has(id);
        // Show the change right away; undo it if the server says no.
        was ? liked.delete(id) : liked.add(id);
        counts[id] = Math.max(0, (counts[id] || 0) + (was ? -1 : 1));
        paintAll(id);
        if (!was) { button.classList.remove('pop'); void button.offsetWidth; button.classList.add('pop'); }
        try {
            const response = await fetch(config.toggleUrl.replace('__ID__', encodeURIComponent(id)), {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf, 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error(String(response.status));
            const data = await response.json();
            data.liked ? liked.add(id) : liked.delete(id);
            counts[id] = Number(data.count) || 0;
        } catch (_) {
            was ? liked.add(id) : liked.delete(id);
            counts[id] = Math.max(0, (counts[id] || 0) + (was ? 1 : -1));
        } finally {
            delete button.dataset.busy;
            paintAll(id);
        }
    };
    // Capture phase: the heart must never open the product, follow a card link or add to cart.
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-like-product]');
        if (!button) return;
        event.preventDefault(); event.stopPropagation();
        toggle(button);
    }, true);
    ['pointerdown', 'mousedown', 'touchstart', 'keydown'].forEach((type) => document.addEventListener(type, (event) => {
        if (event.target.closest?.('[data-like-product]')) event.stopPropagation();
    }, { capture: true, passive: true }));
    const start = () => {
        scan(document.body);
        new MutationObserver((records) => records.forEach((r) => r.addedNodes.forEach(scan))).observe(document.body, { childList: true, subtree: true });
    };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start, { once: true }) : start();
    window.OzmanLikes = { scan, refresh: load };
})();
