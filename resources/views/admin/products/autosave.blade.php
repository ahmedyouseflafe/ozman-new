<script>
    (() => {
        const form = document.querySelector('[data-product-autosave]');

        if (!form || !window.localStorage) {
            return;
        }

        const storageKey = `ozman:${form.dataset.productAutosave}`;
        const ignoredTypes = new Set(['file', 'password', 'submit', 'button', 'reset']);

        function storableFields() {
            return [...form.elements].filter((field) => {
                return field.name &&
                    !field.disabled &&
                    !field.name.startsWith('_') &&
                    !ignoredTypes.has((field.type || '').toLowerCase());
            });
        }

        function saveDraft() {
            const fields = {};

            storableFields().forEach((field) => {
                const type = (field.type || '').toLowerCase();

                if (type === 'checkbox') {
                    if (field.name.endsWith('[]')) {
                        fields[field.name] = fields[field.name] || [];

                        if (field.checked) {
                            fields[field.name].push(field.value);
                        }
                    } else {
                        fields[field.name] = field.checked;
                    }

                    return;
                }

                if (type === 'radio') {
                    if (field.checked) {
                        fields[field.name] = field.value;
                    }

                    return;
                }

                if (field.multiple) {
                    fields[field.name] = [...field.selectedOptions].map((option) => option.value);
                    return;
                }

                fields[field.name] = field.value;
            });

            localStorage.setItem(storageKey, JSON.stringify({
                savedAt: Date.now(),
                fields,
            }));
        }

        function campaignMaxIndex(fields) {
            return Object.keys(fields).reduce((max, name) => {
                const match = name.match(/^campaigns\[(\d+)]/);
                return match ? Math.max(max, Number.parseInt(match[1], 10)) : max;
            }, -1);
        }

        function restoreDraft() {
            const raw = localStorage.getItem(storageKey);

            if (!raw) {
                return;
            }

            let draft;
            try {
                draft = JSON.parse(raw);
            } catch (error) {
                localStorage.removeItem(storageKey);
                return;
            }

            const fields = draft.fields || {};
            const maxCampaignIndex = campaignMaxIndex(fields);

            if (maxCampaignIndex >= 0 && typeof window.ensureProductCampaignCards === 'function') {
                window.ensureProductCampaignCards(maxCampaignIndex);
            }

            storableFields().forEach((field) => {
                if (!Object.prototype.hasOwnProperty.call(fields, field.name)) {
                    return;
                }

                const type = (field.type || '').toLowerCase();
                const value = fields[field.name];

                if (type === 'checkbox') {
                    field.checked = Array.isArray(value)
                        ? value.includes(field.value)
                        : Boolean(value);
                } else if (type === 'radio') {
                    field.checked = field.value === value;
                } else if (field.multiple && Array.isArray(value)) {
                    [...field.options].forEach((option) => {
                        option.selected = value.includes(option.value);
                    });
                } else {
                    field.value = value;
                }

                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        form.addEventListener('input', saveDraft);
        form.addEventListener('change', saveDraft);
        form.addEventListener('submit', () => localStorage.removeItem(storageKey));

        document.addEventListener('product-campaigns-updated', saveDraft);
        restoreDraft();
    })();
</script>
