(() => {
    'use strict';
    const key = 'ozman.promoter.theme';
    const root = document.documentElement;
    function apply(theme) {
        const dark = theme === 'dark';
        root.dataset.theme = dark ? 'dark' : 'light';
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#17131d' : '#faf9f6');
        const button = document.getElementById('promoterThemeToggle');
        if (!button) return;
        button.setAttribute('aria-pressed', String(dark));
        button.title = dark ? 'التبديل للوضع الفاتح' : 'التبديل للوضع الداكن';
        button.querySelector('[aria-hidden]').textContent = dark ? '☀' : '☾';
        button.querySelector('[data-theme-label]').textContent = dark ? 'Light' : 'Dark';
    }
    let saved = 'light';
    try { saved = localStorage.getItem(key) || saved; } catch (_) { /* Storage may be blocked. */ }
    apply(saved);
    document.addEventListener('DOMContentLoaded', () => {
        apply(root.dataset.theme);
        document.getElementById('promoterThemeToggle')?.addEventListener('click', () => {
            const theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            apply(theme);
            try { localStorage.setItem(key, theme); } catch (_) { /* The toggle still works for this page. */ }
        });
    });
    window.addEventListener('storage', event => { if (event.key === key || event.key === null) apply(event.newValue || 'light'); });
})();
