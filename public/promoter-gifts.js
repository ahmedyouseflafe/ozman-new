(() => {
    const dialog = document.getElementById('promoterGiftDialog');
    if (!dialog) return;

    const i18n = JSON.parse(dialog.dataset.i18n || '{}');
    const status = document.getElementById('giftStatus');
    const balanceEl = document.getElementById('giftBalance');
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    let sending = false;

    const setStatus = (text, isError = false) => {
        if (!status) return;
        status.textContent = text || '';
        status.classList.toggle('is-error', Boolean(isError));
    };

    const refreshAffordability = (balance) => {
        dialog.querySelectorAll('[data-gift-id]').forEach((button) => {
            button.disabled = Number(button.dataset.points) > balance;
        });
    };

    document.querySelectorAll('[data-gift-open]').forEach((button) => button.addEventListener('click', () => {
        setStatus('');
        // Opened from the post viewer: the gift is tied to that post.
        dialog.dataset.postId = button.dataset.postId || '';
        if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', '');
    }));
    dialog.querySelectorAll('[data-gift-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });

    const celebrate = (icon) => {
        const burst = document.createElement('span');
        burst.className = 'gift-burst';
        burst.setAttribute('aria-hidden', 'true');
        burst.textContent = icon;
        document.body.appendChild(burst);
        setTimeout(() => burst.remove(), 1600);
    };

    dialog.querySelectorAll('[data-gift-id]').forEach((button) => button.addEventListener('click', async () => {
        if (sending || button.disabled) return;
        if (dialog.dataset.signedIn !== '1') { window.location.href = dialog.dataset.login; return; }
        sending = true;
        setStatus(i18n.sending || '…');
        dialog.querySelectorAll('[data-gift-id]').forEach((b) => b.setAttribute('aria-busy', 'true'));
        try {
            const response = await fetch(dialog.dataset.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ gift_id: Number(button.dataset.giftId), post_id: dialog.dataset.postId ? Number(dialog.dataset.postId) : null }),
            });
            const data = await response.json().catch(() => ({}));
            if (response.status === 401 && data.login) { window.location.href = data.login; return; }
            if (response.status === 419) { setStatus(i18n.expired, true); return; }
            if (response.status === 429) { setStatus(i18n.tooMany, true); return; }
            if (!response.ok) {
                const first = data.errors ? Object.values(data.errors).flat()[0] : null;
                setStatus(first || data.message || i18n.failed, true);
                return;
            }
            setStatus(data.message || '');
            celebrate(button.querySelector('.gift-icon')?.textContent || '🎁');
            if (balanceEl && typeof data.balance === 'number') {
                balanceEl.dataset.balance = String(data.balance);
                balanceEl.textContent = `⭐ ${data.balance.toLocaleString()}`;
                refreshAffordability(data.balance);
            }
            const headerPoints = document.querySelector('.account-points');
            if (headerPoints && typeof data.balance === 'number') headerPoints.textContent = `⭐ ${data.balance.toLocaleString()}`;
            const points = document.getElementById('promoterGiftPoints');
            if (points && typeof data.gift_points === 'number') points.textContent = data.gift_points.toLocaleString();
            const badge = document.getElementById('promoterLevelBadge');
            if (badge && data.level) {
                badge.className = `level-badge level-${data.level.key}`;
                badge.textContent = `${data.level.icon} ${data.level.name}`;
            }
        } catch (error) {
            setStatus(i18n.failed, true);
        } finally {
            sending = false;
            dialog.querySelectorAll('[data-gift-id]').forEach((b) => b.removeAttribute('aria-busy'));
        }
    }));
})();
