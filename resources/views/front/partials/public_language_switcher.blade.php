@php
    $currentPublicLocale = app()->getLocale();
    $publicLanguageNames = ['ar' => 'العربية', 'he' => 'עברית', 'en' => 'English'];
@endphp

@unless($detectOnly ?? false)
<div class="languages public-language-switcher" aria-label="{{ ['ar' => 'اختيار اللغة', 'he' => 'בחירת שפה', 'en' => 'Choose language'][$currentPublicLocale] ?? 'Choose language' }}">
    <span class="public-language-icon" aria-hidden="true">🌐</span>
    @foreach($publicLanguageNames as $languageCode => $languageName)
        <a href="{{ route('lang.switch', $languageCode) }}"
           data-public-locale="{{ $languageCode }}"
           @class(['active' => $currentPublicLocale === $languageCode])
           lang="{{ $languageCode }}"
           hreflang="{{ $languageCode }}">{{ $languageName }}</a>
    @endforeach
</div>
@endunless

@once
    <style>
        .public-language-switcher{display:flex;align-items:center;gap:5px;padding:5px;border:1px solid rgba(20,215,240,.28);border-radius:13px;background:rgba(4,14,20,.9);backdrop-filter:blur(12px)}
        .public-language-icon{margin-inline:4px 2px;font-size:13px;line-height:1}
        .public-language-switcher a{padding:6px 8px;border:0!important;border-radius:8px!important;color:#9eb1bf;text-decoration:none;font-size:11px;font-weight:800}
        .public-language-switcher a.active{background:var(--cyan,#16d9f3);color:#001318}
        @media(max-width:520px){.public-language-switcher a{padding:5px 6px;font-size:10px}.public-language-icon{display:none}}
    </style>
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
                    window.location.replace(@json(url('/lang')).replace(/\/$/, '') + '/' + wantedLocale + '?source=device');
                }
            }
        })();
    </script>
@endonce
