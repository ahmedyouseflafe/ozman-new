(() => {
    const TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    const MAX_BYTES = 5 * 1024 * 1024;

    document.querySelectorAll('[data-avatar-picker]').forEach(picker => {
        const input = picker.querySelector('[data-avatar-input]');
        const preview = picker.querySelector('[data-avatar-preview]');
        const empty = picker.querySelector('[data-avatar-empty]');
        const remove = picker.querySelector('[data-avatar-remove]');
        const error = picker.querySelector('[data-avatar-error]');
        // On the account page the save button only appears once a new photo is chosen.
        const save = picker.closest('form')?.querySelector('[data-avatar-save]');
        const original = preview.getAttribute('src') || '';
        let t = {};
        try { t = JSON.parse(picker.dataset.i18n || '{}'); } catch (_) {}
        let objectUrl = null;

        const show = src => {
            preview.hidden = !src;
            if (src) preview.src = src; else preview.removeAttribute('src');
            empty.hidden = !!src;
        };
        const reset = () => {
            if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
            input.value = '';
            show(original);
            remove.hidden = true;
            if (save) save.hidden = true;
        };
        const fail = message => {
            reset();
            error.textContent = message || '';
            picker.classList.add('has-error');
        };

        input.addEventListener('change', () => {
            const file = input.files[0];
            error.textContent = '';
            picker.classList.remove('has-error');
            if (!file) return reset();
            // Some Android pickers leave the type empty; fall back to the extension.
            const typeOk = TYPES.includes(file.type) || (!file.type && /\.(jpe?g|png|webp)$/i.test(file.name));
            if (!typeOk) return fail(t.type);
            if (file.size > MAX_BYTES) return fail(t.size);
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            show(objectUrl);
            remove.hidden = false;
            if (save) save.hidden = false;
        });

        remove.addEventListener('click', () => {
            error.textContent = '';
            picker.classList.remove('has-error');
            reset();
            input.focus();
        });
    });
})();
