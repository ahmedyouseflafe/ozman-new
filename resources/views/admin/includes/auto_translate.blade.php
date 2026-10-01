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

        function sourceLocale(text) {
            if (/[\u0600-\u06FF]/u.test(text)) {
                return 'ar';
            }

            if (/[\u0590-\u05FF]/u.test(text)) {
                return 'he';
            }

            return 'en';
        }

        async function translateInBrowser(text, source, locales) {
            const translations = {};

            await Promise.all(locales.map(async (locale) => {
                if (locale === source) {
                    translations[locale] = text;
                    return;
                }

                const params = new URLSearchParams({
                    q: text,
                    langpair: `${source}|${locale}`,
                });
                const response = await fetch(`https://api.mymemory.translated.net/get?${params}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const translation = data?.responseData?.translatedText;

                if (typeof translation === 'string' && translation.trim()) {
                    translations[locale] = translation.trim();
                }
            }));

            return translations;
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
                const source = sourceLocale(text);
                let translations = {};

                // This request runs in the browser because this provider permits CORS.
                // It keeps auto-translation working even if PHP outbound connections fail.
                try {
                    translations = await translateInBrowser(text, source, targets.map(([locale]) => locale));
                } catch (_) {
                    translations = {};
                }

                const remainingTargets = targets
                    .map(([locale]) => locale)
                    .filter((locale) => !translations[locale]);

                if (remainingTargets.length) {
                    try {
                        const response = await fetch(translateUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ text, source, targets: remainingTargets }),
                        });

                        if (response.ok) {
                            const data = await response.json();
                            translations = { ...translations, ...(data.translations || {}) };
                        }
                    } catch (_) {
                        // Browser translation above remains available if the server fallback fails.
                    }
                }

                targets.forEach(([locale, target]) => {
                    if (!target.value.trim() && translations[locale]) {
                        target.value = translations[locale];
                        target.dataset.autoTranslatedValue = translations[locale];
                        target.dispatchEvent(new Event('input', { bubbles: true }));
                    } else if (target.dataset.autoTranslatedValue && target.value === target.dataset.autoTranslatedValue && translations[locale]) {
                        target.value = translations[locale];
                        target.dataset.autoTranslatedValue = translations[locale];
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

        // Existing products and restored browser drafts can have Arabic content while
        // their English/Hebrew fields are empty. Translate those fields on load too,
        // not only after the merchant edits the source again.
        function fillMissingTranslations() {
            document.querySelectorAll(sourceSelector).forEach((source) => {
                if (source.value.trim().length < 2) {
                    return;
                }

                const hasEmptyTarget = ['en', 'he'].some((locale) => {
                    const target = findTarget(source, locale);

                    return target && !target.value.trim();
                });

                if (hasEmptyTarget) {
                    schedule(source);
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => setTimeout(fillMissingTranslations, 120), { once: true });
        } else {
            setTimeout(fillMissingTranslations, 120);
        }
    })();
</script>
