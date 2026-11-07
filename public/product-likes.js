// Product hearts and share buttons on every storefront, including cards built later by JS.
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
        // The heart sits in an overlay (.oz-actions) pinned to the card; the card must be positioned for that.
        const actions = button.closest('.oz-actions');
        const host = actions && !actions.classList.contains('oz-actions--inline') ? actions.parentElement : null;
        if (host && getComputedStyle(host).position === 'static') host.style.position = 'relative';
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
    // ---- Sharing -------------------------------------------------------------------------------
    const copy = config.share || {};
    const icons = {
        whatsapp: '<svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.3-.4.8-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3z"/></svg>',
        facebook: '<svg viewBox="0 0 24 24"><path d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.8c0-.9.3-1.5 1.6-1.5h1.7V4.4a22 22 0 0 0-2.5-.1c-2.4 0-4.1 1.5-4.1 4.2v2.3H7.5V14h2.7v8z"/></svg>',
        telegram: '<svg viewBox="0 0 24 24"><path d="M21.4 4.2 2.9 11.3c-1.3.5-1.2 1.2-.2 1.5l4.7 1.5 1.8 5.6c.2.6.4.8.9.8s.6-.2.9-.5l2.3-2.2 4.8 3.5c.9.5 1.5.2 1.7-.8L23 5.6c.3-1.3-.5-1.9-1.6-1.4zM9.1 14l9.6-6.1c.4-.3.9-.1.5.2l-8.1 7.3-.3 3.4z"/></svg>',
        x: '<svg viewBox="0 0 24 24"><path d="M17.8 3h3.1l-6.8 7.8L22 21h-6.2l-4.9-6.4L5.3 21H2.2l7.3-8.3L1.9 3h6.4l4.4 5.8zm-1.1 16.2h1.7L7.3 4.7H5.5z"/></svg>',
        copy: '<svg viewBox="0 0 24 24"><path d="M10.6 13.4a1 1 0 0 1 0-1.4l3.2-3.2a3 3 0 0 1 4.2 4.2l-2 2a1 1 0 1 1-1.4-1.4l2-2a1 1 0 0 0-1.4-1.4L12 13.4a1 1 0 0 1-1.4 0zm2.8-2.8a1 1 0 0 1 0 1.4l-3.2 3.2A3 3 0 0 1 6 11l2-2a1 1 0 1 1 1.4 1.4l-2 2a1 1 0 0 0 1.4 1.4L12 10.6a1 1 0 0 1 1.4 0z"/></svg>',
        more: '<svg viewBox="0 0 24 24"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>',
    };
    const esc = (v) => String(v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const closeSheet = () => {
        const sheet = document.querySelector('.oz-share-sheet');
        if (!sheet) return;
        sheet.classList.remove('is-open');
        setTimeout(() => sheet.remove(), 200);
    };
    const openShare = (button) => {
        const url = button.dataset.shareUrl || location.href, title = button.dataset.shareTitle || document.title;
        const text = `${title}\n${url}`, u = encodeURIComponent(url);
        const links = [
            ['whatsapp', '#25d366', `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`],
            ['facebook', '#1877f2', `https://www.facebook.com/sharer/sharer.php?u=${u}`],
            ['telegram', '#229ed9', `https://t.me/share/url?url=${u}&text=${encodeURIComponent(title)}`],
            ['x', '#111', `https://twitter.com/intent/tweet?url=${u}&text=${encodeURIComponent(title)}`],
        ];
        closeSheet();
        const sheet = document.createElement('div');
        sheet.className = 'oz-share-sheet'; sheet.setAttribute('role', 'dialog'); sheet.setAttribute('aria-modal', 'true');
        sheet.dir = document.documentElement.dir || 'rtl';
        sheet.innerHTML = `<div class="oz-share-card"><div class="oz-share-head"><div><b>${esc(copy.title || 'Share')}</b><small>${esc(title)}</small></div><button type="button" class="oz-share-close" aria-label="${esc(copy.close || 'Close')}">×</button></div>
            <div class="oz-share-grid">${links.map(([key, color, href]) => `<a href="${esc(href)}" target="_blank" rel="noopener"><i style="background:${color}">${icons[key]}</i>${esc(copy[key] || key)}</a>`).join('')}
            <button type="button" data-share-copy><i style="background:#6b6475">${icons.copy}</i><span>${esc(copy.copy || 'Copy link')}</span></button>
            ${navigator.share ? `<button type="button" data-share-native><i style="background:#7c4dff">${icons.more}</i>${esc(copy.more || 'More')}</button>` : ''}</div></div>`;
        document.body.appendChild(sheet);
        requestAnimationFrame(() => sheet.classList.add('is-open'));
        sheet.addEventListener('click', (event) => {
            if (event.target === sheet || event.target.closest('.oz-share-close')) { closeSheet(); return; }
            if (event.target.closest('a')) { setTimeout(closeSheet, 300); return; }
            const copyButton = event.target.closest('[data-share-copy]');
            if (copyButton) {
                const done = () => { copyButton.querySelector('span').textContent = copy.copied || 'Copied'; setTimeout(closeSheet, 900); };
                (navigator.clipboard?.writeText(url) || Promise.reject()).then(done).catch(() => { window.prompt(copy.copy || 'Copy link', url); closeSheet(); });
                return;
            }
            if (event.target.closest('[data-share-native]')) {
                navigator.share({ title, text: title, url }).catch(() => {});
                closeSheet();
            }
        });
        sheet.querySelector('.oz-share-close').focus();
    };
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeSheet(); });

    // Capture phase: these buttons must never open the product, follow a card link or add to cart.
    const ACTION = '[data-like-product], .oz-share, .oz-page-share';
    document.addEventListener('click', (event) => {
        const button = event.target.closest?.(ACTION);
        if (!button) return;
        event.preventDefault(); event.stopPropagation();
        button.matches('[data-like-product]') ? toggle(button) : openShare(button);
    }, true);
    ['pointerdown', 'mousedown', 'touchstart', 'keydown'].forEach((type) => document.addEventListener(type, (event) => {
        if (event.target.closest?.(ACTION)) event.stopPropagation();
    }, { capture: true, passive: true }));

    // A shared product link lands on the store with ?product=ID: show that product.
    const showSharedProduct = () => {
        const id = new URLSearchParams(location.search).get('product');
        if (!id || !/^\d+$/.test(id)) return;
        const actions = document.querySelector(`.oz-actions[data-oz-product="${id}"]`);
        if (!actions) return;
        const card = actions.closest('.meal, article, .card, .product, .service-card, .device') || actions.parentElement;
        if (card.matches('.meal[data-product-id]')) { card.click(); return; } // Restaurants open the meal itself.
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.classList.add('oz-share-highlight');
        setTimeout(() => card.classList.remove('oz-share-highlight'), 2700);
    };
    const start = () => {
        scan(document.body);
        setTimeout(showSharedProduct, 600);
        new MutationObserver((records) => records.forEach((r) => r.addedNodes.forEach(scan))).observe(document.body, { childList: true, subtree: true });
    };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', start, { once: true }) : start();
    window.OzmanLikes = { scan, refresh: load };
})();
