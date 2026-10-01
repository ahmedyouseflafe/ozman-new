<script>
    (() => {
        const translateUrl = @json(route('translations.suggest'));
        const csrfToken = @json(csrf_token());
        const sourceSelector = '[data-auto-translate-source]';
        const pendingTimers = new WeakMap();
        const translationCachePrefix = 'ozman:auto-translation:v2:';
        let translationQueue = Promise.resolve();

        const pause = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

        function translationCacheKey(text, source, locale) {
            let hash = 0;

            for (let index = 0; index < text.length; index += 1) {
                hash = ((hash << 5) - hash) + text.charCodeAt(index);
                hash |= 0;
            }

            return `${translationCachePrefix}${source}:${locale}:${text.length}:${hash}`;
        }

        function readCachedTranslation(text, source, locale) {
            try {
                const cacheKey = translationCacheKey(text, source, locale);
                const stored = JSON.parse(localStorage.getItem(cacheKey) || 'null');

                if (stored?.value && stored?.expiresAt > Date.now()) {
                    return stored.value;
                }

                localStorage.removeItem(cacheKey);
            } catch (_) {
                // Private browsing or storage restrictions should not stop translation.
            }

            return null;
        }

        function cacheTranslation(text, source, locale, value) {
            try {
                localStorage.setItem(translationCacheKey(text, source, locale), JSON.stringify({
                    value,
                    expiresAt: Date.now() + (1000 * 60 * 60 * 24 * 30),
                }));
            } catch (_) {
                // Translation still works when browser storage is unavailable.
            }
        }

        function queueTranslation(request) {
            const queuedRequest = translationQueue.then(async () => {
                // Keep provider requests gentle and avoid burst rate limits.
                await pause(500);

                return request();
            });

            // Keep the queue alive when one individual translation fails.
            translationQueue = queuedRequest.catch(() => undefined);

            return queuedRequest;
        }

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

            for (const locale of locales) {
                if (locale === source) {
                    translations[locale] = text;
                    continue;
                }

                const cachedTranslation = readCachedTranslation(text, source, locale);

                if (cachedTranslation) {
                    translations[locale] = cachedTranslation;
                    continue;
                }

                const requestTranslation = async () => {
                    const params = new URLSearchParams({
                        q: text,
                        client: 'gtx',
                        sl: source,
                        tl: locale,
                        dt: 't',
                    });
                    const response = await fetch(`https://translate.googleapis.com/translate_a/single?${params}`, {
                        headers: { Accept: 'application/json' },
                    });

                    if (!response.ok) {
                        const error = new Error(`Translation request failed (${response.status})`);
                        error.status = response.status;
                        throw error;
                    }

                    const data = await response.json();
                    const translation = Array.isArray(data?.[0])
                        ? data[0].map((segment) => segment?.[0] || '').join('')
                        : null;

                    if (typeof translation !== 'string' || !translation.trim()) {
                        throw new Error('Translation response was empty');
                    }

                    return translation.trim();
                };

                let translation = null;

                for (let attempt = 0; attempt < 2 && !translation; attempt += 1) {
                    try {
                        translation = await queueTranslation(requestTranslation);
                    } catch (error) {
                        // Retry once after a provider rate-limit or temporary outage.
                        if (attempt === 0 && [429, 503].includes(error?.status)) {
                            await pause(1400);
                            continue;
                        }
                    }
                }

                if (translation) {
                    translations[locale] = translation;
                    cacheTranslation(text, source, locale, translation);
                }
            }

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
