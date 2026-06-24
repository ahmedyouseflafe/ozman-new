<script>
    (() => {
        const translateUrl = @json(route('translations.suggest'));
        const csrfToken = @json(csrf_token());
        const sourceSelector = '[data-auto-translate-source]';
        const pendingTimers = new WeakMap();

        function findTarget(source, locale) {
            const sourceName = source.getAttribute('name');
            const explicit = source.dataset[`translate${locale.toUpperCase()}`];

            if (explicit) {
                return document.querySelector(explicit);
            }

            if (!sourceName) {
                return null;
            }

            const targetName = sourceName.endsWith(']')
                ? sourceName.replace(/\]$/, `_${locale}]`)
                : `${sourceName}_${locale}`;

            return document.querySelector(`[name="${CSS.escape(targetName)}"]`)
                || null;
        }

        function setBusy(target, busy) {
            target.style.opacity = busy ? '.72' : '';
            target.placeholder = busy ? 'جاري الترجمة...' : target.dataset.originalPlaceholder || '';
        }

        async function translateField(source) {
            const text = source.value.trim();
            const allTargets = ['en', 'he']
                .map((locale) => [locale, findTarget(source, locale)])
                .filter(([, target]) => target);

            if (text.length < 2) {
                allTargets.forEach(([, target]) => {
                    if (target.dataset.autoTranslatedValue && target.value === target.dataset.autoTranslatedValue) {
                        target.value = '';
                        delete target.dataset.autoTranslatedValue;
                    }
                });

                return;
            }

            const targets = allTargets.filter(([, target]) => {
                const autoValue = target.dataset.autoTranslatedValue;

                return !target.value.trim() || (autoValue && target.value === autoValue);
            });

            if (!targets.length) {
                return;
            }

            targets.forEach(([, target]) => {
                target.dataset.originalPlaceholder ??= target.placeholder;
                setBusy(target, true);
            });

            try {
                const response = await fetch(translateUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        text,
                        targets: targets.map(([locale]) => locale),
                    }),
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                targets.forEach(([locale, target]) => {
                    if (!target.value.trim() && data.translations?.[locale]) {
                        target.value = data.translations[locale];
                        target.dataset.autoTranslatedValue = data.translations[locale];
                        target.dispatchEvent(new Event('input', { bubbles: true }));
                    } else if (target.dataset.autoTranslatedValue && target.value === target.dataset.autoTranslatedValue && data.translations?.[locale]) {
                        target.value = data.translations[locale];
                        target.dataset.autoTranslatedValue = data.translations[locale];
                        target.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            } finally {
                targets.forEach(([, target]) => setBusy(target, false));
            }
        }

        function schedule(source) {
            clearTimeout(pendingTimers.get(source));
            pendingTimers.set(source, setTimeout(() => translateField(source), 650));
        }

        document.addEventListener('input', (event) => {
            if (event.target.matches(sourceSelector)) {
                schedule(event.target);

                return;
            }

            if (event.target.dataset.autoTranslatedValue && event.target.value !== event.target.dataset.autoTranslatedValue) {
                delete event.target.dataset.autoTranslatedValue;
            }
        });

        document.addEventListener('blur', (event) => {
            if (event.target.matches(sourceSelector)) {
                translateField(event.target);
            }
        }, true);
    })();
</script>
