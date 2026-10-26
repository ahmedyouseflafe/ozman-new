(() => {
    const config = window.OZMAN_NOTIFICATION_REWARD;
    if (!config) return;
    let token = '';
    try { token = localStorage.getItem(config.storageKey + '.token') || ''; } catch (_) {}
    if (!/^[a-f0-9]{64}$/.test(token)) {
        token = Array.from(crypto.getRandomValues(new Uint8Array(32)), value => value.toString(16).padStart(2, '0')).join('');
        try { localStorage.setItem(config.storageKey + '.token', token); } catch (_) {}
    }
    const enabled = () => window.OzmanOfferNotifications?.ready?.() === true;
    window.OzmanRestaurantCustomer = { token: () => enabled() ? token : '' };
    const button = document.getElementById('notificationGiftEnable');
    button.addEventListener('click', () => window.OzmanOfferNotifications?.prompt?.());
    const activated = () => {
        if (!enabled()) return;
        button.hidden = true;
        // Close the notification dialog before opening the wheel dialog.
        setTimeout(() => document.dispatchEvent(new Event('restaurant:registered')), 0);
    };
    window.addEventListener('ozman:offer-push-ready', activated);
    const init = () => {
        document.querySelector('.restaurant-hero-layout')?.after(button);
        // Preserve previously saved checkout details without requiring registration.
        try {
            const profile = JSON.parse(localStorage.getItem(config.storageKey) || 'null');
            if (profile) {
                for (const [id, value] of Object.entries({name:profile.name, phone:profile.whatsapp, address:profile.address})) {
                    const input = document.getElementById(id);if (input && !input.value && typeof value === 'string') input.value = value;
                }
            }
        } catch (_) {}
        activated();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once:true});else init();
})();
