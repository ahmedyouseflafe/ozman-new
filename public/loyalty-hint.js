(() => {
    // Reads the total each cart already renders, so the checkout scripts stay untouched.
    const parseAmount = text => {
        const western = String(text || '')
            .replace(/[٠-٩]/g, d => String(d.charCodeAt(0) - 0x0660))
            .replace(/[۰-۹]/g, d => String(d.charCodeAt(0) - 0x06F0))
            .replace(/[٬,\s]/g, '')
            .replace('٫', '.');
        const match = western.match(/\d+(?:\.\d+)?/);
        return match ? parseFloat(match[0]) : 0;
    };

    document.querySelectorAll('[data-loyalty-hint]').forEach(hint => {
        const source = document.querySelector(hint.dataset.total);
        if (!source) return;
        const step = Number(hint.dataset.amount) || 0;
        const perStep = Number(hint.dataset.points) || 0;

        const render = () => {
            const points = step > 0 ? Math.floor(parseAmount(source.textContent) / step) * perStep : 0;
            hint.hidden = points <= 0;
            if (points <= 0) return;
            const text = hint.dataset.text.replace(':points', points.toLocaleString('en'));
            if (hint.dataset.login) {
                const link = hint.querySelector('a') || hint.appendChild(document.createElement('a'));
                link.href = hint.dataset.login;
                link.textContent = text;
            } else {
                hint.textContent = text;
            }
        };

        new MutationObserver(render).observe(source, {childList: true, characterData: true, subtree: true});
        render();
    });
})();
