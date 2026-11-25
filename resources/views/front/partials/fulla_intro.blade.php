{{-- Fulla introduces herself the first time someone opens Ozman, in words that fit them: a visitor, a customer,
     a promoter or a shop owner. Once per kind of account in this browser (signing in as a shop owner later brings
     the shop owner's introduction). On the dashboard only shop owners see it. --}}
@php
    $fiUser = auth()->user();
    $fiKind = ! $fiUser ? 'guest' : ($fiUser->isShopOwner() ? 'merchant' : ($fiUser->isPromoter() ? 'promoter' : ($fiUser->isCustomer() ? 'customer' : null)));
    if (($onDashboard ?? false) && $fiKind !== 'merchant') {
        $fiKind = null;
    }
    $fiFirst = $fiUser ? \Illuminate\Support\Str::before(trim((string) $fiUser->name), ' ') : '';
    $fiShop = $fiKind === 'merchant' ? $fiUser->shops()->where('is_active', true)->orderBy('id')->first() : null;
    $fiContent = match ($fiKind) {
        'merchant' => [
            'hello' => $fiFirst !== '' ? __('أهلين :name! أنا فُلّة، مساعدتك بالمحل 🌸', ['name' => $fiFirst]) : __('أهلين! أنا فُلّة، مساعدتك بالمحل 🌸'),
            'intro' => __('بشتغل معك على محلك بالحكي، وما بغيّر إشي إلا لما توافق.'),
            'badge' => __('مساعدة المحل الذكية'),
            'skills' => [
                ['insights', 'cyan', __('مبيعاتك بسؤال'), __('«قديش بعت هالأسبوع؟ وشو أكثر إشي انطلب؟»')],
                ['edit', 'warm', __('بعدّل منتجاتك وأسعارك'), __('«نزّل سعر الشاورما لـ 15 شيكل»')],
                ['tag', 'violet', __('كوبونات وعروض'), __('«اعمليلي خصم 10% لآخر الأسبوع»')],
                ['receipt', 'green', __('الطلبات والتقييمات'), __('«شو الطلبات اللي بتستنى؟ وردّي على آخر تقييم»')],
            ],
            'cta' => __('افتح مساعدتي'),
            'href' => $fiShop ? route('shop-assistant.index', $fiShop) : null,
        ],
        'promoter' => [
            'hello' => $fiFirst !== '' ? __('أهلين :name! أنا فُلّة 🌸', ['name' => $fiFirst]) : __('أهلين! أنا فُلّة 🌸'),
            'intro' => __('بلاقيلك شو تروّج، وبجاوبك عن أي محل أو منتج على أوزمان.'),
            'badge' => __('مساعدتك الذكية على أوزمان'),
            'skills' => [
                ['flame', 'warm', __('أقوى العروض اليوم'), __('«شو أحلى العروض اليوم بالمطاعم؟»')],
                ['search', 'cyan', __('منتجات حسب الوصف'), __('«منتجات عناية جديدة تحت 100 شيكل»')],
                ['shop', 'violet', __('كل إشي عن أي محل'), __('«أيمتى بيفتح؟ ووين موقعه؟»')],
                ['mic', 'green', __('احكي معي بصوتك'), __('اكبس المايك واحكي، وأنا بردّ عليك بصوتي')],
            ],
            'cta' => __('احكي مع فُلّة'),
            'href' => null,
        ],
        'customer' => [
            'hello' => $fiFirst !== '' ? __('أهلين :name! أنا فُلّة 🌸', ['name' => $fiFirst]) : __('أهلين! أنا فُلّة 🌸'),
            'intro' => __('أنا معك بكل مشوار تسوّق على أوزمان، احكيلي شو بدك وعليّ الباقي.'),
            'badge' => __('مساعدتك الذكية على أوزمان'),
            'skills' => [
                ['search', 'cyan', __('بلاقيلك اللي بدك'), __('«بدي هدية لأختي تحت 150 شيكل»')],
                ['replay', 'warm', __('«زي المرة الماضية»'), __('بتذكّر طلباتك وبجهّزلك السلة، حتى من كذا محل')],
                ['truck', 'green', __('طلبك ونقاطك'), __('«وين صار طلبي؟ وقديش نقطة عندي؟»')],
                ['calendar', 'violet', __('مواعيد الصالونات'), __('«احجزيلي بكرا الساعة 5»')],
            ],
            'cta' => __('احكي مع فُلّة'),
            'href' => null,
        ],
        'guest' => [
            'hello' => __('أهلاً فيك بأوزمان! أنا فُلّة 🌸'),
            'intro' => __('مساعدتك الذكية. بلاقيلك اللي بدك إياه من كل محلات بلدك، بس احكيلي.'),
            'badge' => __('مساعدتك الذكية على أوزمان'),
            'skills' => [
                ['search', 'cyan', __('احكيلي شو بدك وأنا بلاقيه'), __('«بدي غدا لأربعة تحت 100 شيكل»')],
                ['flame', 'warm', __('عروض اليوم بكل المحلات'), __('«شو العروض اليوم؟»')],
                ['basket', 'violet', __('سلة وحدة من كذا محل'), __('وكل محل بيوصّلك طلبه لحاله')],
                ['star', 'green', __('نقاط على كل شراء'), __('من التطبيق، وكمان لما تشتري من المحل نفسه')],
            ],
            'cta' => __('سجّل وخليني أساعدك'),
            'href' => route('community.login'),
        ],
        default => null,
    };
    $fiIcons = [
        'insights' => '<path d="M4 19V5M4 19h16M8 15l3-4 3 2 5-6"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/>',
        'tag' => '<path d="M3 12V4h8l10 10-8 8L3 12z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3zM9 8h6M9 12h6M9 16h3"/>',
        'flame' => '<path d="M12 3c1 4 5 5 5 10a5 5 0 0 1-10 0c0-3 2-4 2-7 1 1 2 2 3 4 1-2 0-5 0-7z"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="M20 20l-4.5-4.5"/>',
        'shop' => '<path d="M4 9l2-5h12l2 5M4 9v11h16V9M4 9h16M9 20v-6h6v6"/>',
        'mic' => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/>',
        'replay' => '<path d="M4 12a8 8 0 1 0 3-6.2M4 4v4h4"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4M9 15l2 2 4-4"/>',
        'basket' => '<path d="M4 10h16l-2 10H6L4 10zM8 10l4-6 4 6"/>',
        'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
    ];
@endphp
@if($fiContent)
<div class="oz-fi" id="ozFullaIntro" role="dialog" aria-modal="true" aria-labelledby="ozFiHello" hidden
     data-kind="{{ $fiKind }}" data-user="{{ $fiUser?->id ?? 'guest' }}" data-lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
    <div class="oz-fi-backdrop" data-fi-close></div>
    <div class="oz-fi-card">
        <div class="oz-fi-face">
            <span class="oz-fi-glow"></span>
            @include('front.partials.assistant_avatar', ['size' => 124, 'class' => 'oz-fi-av'])
        </div>
        <div class="oz-fi-inner">
            <span class="oz-fi-badge">✦ {{ $fiContent['badge'] }}</span>
            <h2 id="ozFiHello" class="oz-fi-hello" data-text="{{ $fiContent['hello'] }}"></h2>
            <p class="oz-fi-intro" data-text="{{ $fiContent['intro'] }}"></p>
            <ul class="oz-fi-skills">
                @foreach($fiContent['skills'] as $i => [$icon, $color, $title, $example])
                <li style="--d:{{ $i * 110 }}ms">
                    <span class="oz-fi-ic {{ $color }}"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $fiIcons[$icon] !!}</svg></span>
                    <span><b>{{ $title }}</b><small>{{ $example }}</small></span>
                </li>
                @endforeach
            </ul>
            <div class="oz-fi-actions">
                @if($fiContent['href'])
                <a class="oz-fi-cta" href="{{ $fiContent['href'] }}" data-fi-go>{{ $fiContent['cta'] }}</a>
                @else
                <button type="button" class="oz-fi-cta" data-fi-go data-fi-chat>{{ $fiContent['cta'] }}</button>
                @endif
                <div class="oz-fi-small">
                    <button type="button" class="oz-fi-link" data-fi-listen hidden>🔊 {{ __('اسمعها') }}</button>
                    <button type="button" class="oz-fi-link muted" data-fi-close>{{ __('بعدين') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
@viewStyle('front.partials.fulla_intro')
<script>
(() => {
    const box = document.getElementById('ozFullaIntro');
    if (!box) return;
    const key = `ozFullaIntro:v1:${box.dataset.kind}:${box.dataset.user}`;
    try { if (localStorage.getItem(key)) return; localStorage.setItem(key, '1'); } catch (_) { return; }
    const av = box.querySelector('.oz-fi-av');
    const hello = box.querySelector('.oz-fi-hello'), intro = box.querySelector('.oz-fi-intro');
    const full = [hello.dataset.text, intro.dataset.text];
    const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let timer = null;
    const done = () => {
        clearInterval(timer);
        hello.textContent = full[0]; intro.textContent = full[1];
        box.classList.add('typed');
        if (av && av.dataset.state === 'speaking') av.dataset.state = 'idle';
    };
    const close = () => {
        done();
        window.OzmanFullaIntroOpen = false;
        window.dispatchEvent(new Event('ozman:fulla-intro-closed'));
        window.OzmanVoice?.stopSpeaking?.();
        box.classList.remove('open');
        setTimeout(() => { box.hidden = true; }, 320);
        document.removeEventListener('keydown', onKey);
    };
    const onKey = (e) => { if (e.key === 'Escape') close(); };

    // Known before it shows, so other pop-ups (offers notifications) wait their turn.
    window.OzmanFullaIntroOpen = true;
    function open() {
        box.hidden = false;
        requestAnimationFrame(() => box.classList.add('open'));
        document.addEventListener('keydown', onKey);
        if (calm) return done();
        // She "says" it: the words appear as if spoken, while her mouth moves.
        if (av) av.dataset.state = 'speaking';
        // Counted in letters as people see them, so an emoji (🌸) is never cut in half while typing.
        const letters = Array.from(full.join('\n'));
        let n = 0;
        timer = setInterval(() => {
            n += 1;
            const shown = letters.slice(0, n).join(''), cut = shown.indexOf('\n');
            hello.textContent = cut < 0 ? shown : shown.slice(0, cut);
            intro.textContent = cut < 0 ? '' : shown.slice(cut + 1);
            if (n >= letters.length) done();
        }, 24);
    }

    box.querySelectorAll('[data-fi-close]').forEach((el) => el.addEventListener('click', close));
    box.querySelector('.oz-fi-inner').addEventListener('click', (e) => { if (!box.classList.contains('typed') && !e.target.closest('button,a')) done(); });
    const chat = box.querySelector('[data-fi-chat]');
    chat?.addEventListener('click', () => { close(); document.getElementById('ozSaFab')?.click(); });
    const listen = box.querySelector('[data-fi-listen]');
    if (window.OzmanVoice && listen) {
        listen.hidden = false;
        listen.addEventListener('click', () => {
            done();
            if (av) av.dataset.state = 'speaking';
            Promise.resolve(window.OzmanVoice.speak(full.join('. '), box.dataset.lang)).finally(() => { if (av) av.dataset.state = 'idle'; });
        });
    }
    // A moment after the page settles, so it doesn't jump at people mid-load.
    const start = () => setTimeout(open, 900);
    document.readyState === 'complete' ? start() : window.addEventListener('load', start, { once: true });
})();
</script>
@endif
