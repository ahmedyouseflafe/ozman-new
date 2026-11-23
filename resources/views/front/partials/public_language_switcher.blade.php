@php
    $currentPublicLocale = app()->getLocale();
    $publicLanguageNames = ['ar' => 'العربية', 'he' => 'עברית', 'en' => 'English'];
@endphp

@unless($detectOnly ?? false)
<div class="languages public-language-switcher" aria-label="{{ ['ar' => 'اختيار اللغة', 'he' => 'בחירת שפה', 'en' => 'Choose language'][$currentPublicLocale] ?? 'Choose language' }}">
    <span class="public-language-icon" aria-hidden="true">🌐</span>
    @foreach($publicLanguageNames as $languageCode => $languageName)
        <a href="{{ route('lang.switch', ['locale' => $languageCode, 'return_to' => request()->getRequestUri()]) }}"
           data-public-locale="{{ $languageCode }}"
           @class(['active' => $currentPublicLocale === $languageCode])
           lang="{{ $languageCode }}"
           hreflang="{{ $languageCode }}">{{ $languageName }}</a>
    @endforeach
</div>
@endunless

@once
    @viewStyle('front.partials.public_language_switcher')
    <script>
        (() => {
            const localeLinks = document.querySelectorAll('[data-public-locale]');
            localeLinks.forEach(link => link.addEventListener('click', () => {
                try {
                    localStorage.setItem('ozman.public-locale', link.dataset.publicLocale);
                } catch (_) {}
            }));

            let savedLocale = document.cookie.match(/(?:^|;\s*)ozman_public_locale=([^;]+)/)?.[1] || null;
            try {
                savedLocale = localStorage.getItem('ozman.public-locale') || savedLocale;
            } catch (_) {}

            const supported = ['ar', 'he', 'en'];
            const normalize = value => {
                const locale = String(value || '').toLowerCase().split('-')[0];
                return locale === 'iw' ? 'he' : locale;
            };
            const deviceLocale = (navigator.languages || [navigator.language])
                .map(normalize)
                .find(locale => supported.includes(locale));
            const wantedLocale = supported.includes(savedLocale) ? savedLocale : deviceLocale;
            const currentLocale = document.documentElement.lang;

            if (wantedLocale && wantedLocale !== currentLocale) {
                const redirectKey = `ozman.locale-redirect.${wantedLocale}`;
                if (!sessionStorage.getItem(redirectKey)) {
                    sessionStorage.setItem(redirectKey, '1');
                    const destination = new URL(@json(url('/lang')).replace(/\/$/, '') + '/' + wantedLocale);
                    destination.searchParams.set('source', 'device');
                    destination.searchParams.set('return_to', location.pathname + location.search + location.hash);
                    window.location.replace(destination.href);
                }
            }
        })();
    </script>
@endonce
