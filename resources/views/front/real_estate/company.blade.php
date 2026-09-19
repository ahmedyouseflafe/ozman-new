@php
    $locale = app()->getLocale();
    $rtl = in_array($locale, ['ar', 'he'], true);
    $subtitle = [
        'ar' => 'عقارات الشركة',
        'he' => 'נדל״ן החברה',
        'en' => 'Company properties',
    ][$locale] ?? 'عقارات الشركة';
    $content = [
        'ar' => [
            'eyebrow' => 'مجالات عملنا',
            'title' => 'حلول متنقلة مصممة لكل احتياج',
            'intro' => 'ننفّذ مساحات عملية بجودة عالية، من الوحدات الجاهزة إلى المشاريع المصممة خصيصًا حسب طلبك.',
            'meta' => 'تصميم · تجهيز · تركيب',
            'custom_badge' => 'حسب طلبك',
            'categories' => [
                ['number' => '01', 'title' => 'البيوت المتنقلة', 'description' => 'بيوت عملية ومريحة بتصاميم عصرية، جاهزة للنقل والتركيب في الموقع.'],
                ['number' => '02', 'title' => 'الحمّامات', 'description' => 'وحدات حمّامات مجهزة بعناية للاستخدام المؤقت أو الدائم وبمقاسات متعددة.'],
                ['number' => '03', 'title' => 'غرف الحراسة', 'description' => 'غرف آمنة وعملية للحراس ونقاط الاستقبال، مع إمكانية تجهيزها حسب الموقع.'],
                ['number' => '04', 'title' => 'الكرفانات', 'description' => 'كرفانات متعددة الاستخدام للسكن والعمل والمواقع، بتوزيعات داخلية مرنة.'],
                ['number' => '05', 'title' => 'تصنيع حسب الطلب', 'description' => 'عندك فكرة مختلفة؟ نصمم وننفّذ الوحدة بالمقاس والتقسيم والتجهيز الذي يناسبك.'],
            ],
        ],
        'he' => [
            'eyebrow' => 'תחומי הפעילות שלנו',
            'title' => 'פתרונות ניידים המותאמים לכל צורך',
            'intro' => 'אנו מייצרים חללים שימושיים ואיכותיים, מיחידות מוכנות ועד פרויקטים בהתאמה אישית.',
            'meta' => 'תכנון · ייצור · התקנה',
            'custom_badge' => 'בהתאמה אישית',
            'categories' => [
                ['number' => '01', 'title' => 'בתים ניידים', 'description' => 'בתים נוחים ומודרניים המוכנים להובלה ולהתקנה באתר.'],
                ['number' => '02', 'title' => 'יחידות שירותים', 'description' => 'יחידות שירותים מאובזרות לשימוש זמני או קבוע ובמגוון גדלים.'],
                ['number' => '03', 'title' => 'עמדות שמירה', 'description' => 'עמדות בטוחות ושימושיות לשומרים ולקבלה, בהתאמה לתנאי השטח.'],
                ['number' => '04', 'title' => 'קרוואנים', 'description' => 'קרוואנים למגורים, לעבודה ולאתרי פרויקט עם חלוקה פנימית גמישה.'],
                ['number' => '05', 'title' => 'ייצור לפי הזמנה', 'description' => 'יש לכם רעיון מיוחד? נתכנן ונייצר את היחידה במידות ובמפרט המתאימים לכם.'],
            ],
        ],
        'en' => [
            'eyebrow' => 'What we build',
            'title' => 'Mobile solutions designed for every need',
            'intro' => 'We create practical, high-quality spaces, from ready-made units to fully customized projects.',
            'meta' => 'Design · Build · Install',
            'custom_badge' => 'Made for you',
            'categories' => [
                ['number' => '01', 'title' => 'Mobile homes', 'description' => 'Comfortable modern homes, ready for transportation and on-site installation.'],
                ['number' => '02', 'title' => 'Bathroom units', 'description' => 'Fully equipped bathroom units for temporary or permanent use in multiple sizes.'],
                ['number' => '03', 'title' => 'Guard rooms', 'description' => 'Safe, practical guard and reception rooms, configured for each location.'],
                ['number' => '04', 'title' => 'Caravans', 'description' => 'Flexible caravans for living, work and project sites with adaptable interiors.'],
                ['number' => '05', 'title' => 'Custom builds', 'description' => 'Have a different idea? We design and build it to your dimensions, layout and specifications.'],
            ],
        ],
    ][$locale] ?? null;
    $content ??= [
        'eyebrow' => 'مجالات عملنا',
        'title' => 'حلول متنقلة مصممة لكل احتياج',
        'intro' => 'ننفّذ مساحات عملية بجودة عالية، من الوحدات الجاهزة إلى المشاريع المصممة خصيصًا حسب طلبك.',
        'meta' => 'تصميم · تجهيز · تركيب',
        'custom_badge' => 'حسب طلبك',
        'categories' => [],
    ];
    $logo = $shop->logo ? asset($shop->logo) : asset('ozman-favicon.png');
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    @include('front.partials.seo', [
        'title' => $shop->name,
        'description' => $shop->description ?: $subtitle.' - '.$shop->name,
        'canonical' => route('real-estate.company', $shop),
        'image' => $logo,
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#061019;--header:#081621;--line:#203b4c;--cyan:#16d9f3;--muted:#8fa6b5;--white:#f7fbff}
        *{box-sizing:border-box}
        html,body{min-height:100%;margin:0}
        body{background:var(--bg);color:var(--white);font-family:Cairo,Arial,Tahoma,sans-serif}
        .site-header{border-bottom:1px solid rgba(32,59,76,.5);background:var(--header)}
        .topbar{display:flex;align-items:center;justify-content:space-between;gap:20px;width:min(1720px,calc(100% - 40px));min-height:104px;margin:auto;padding:14px 0}
        .brand{display:flex;align-items:center;gap:13px;min-width:0;color:inherit;text-decoration:none}
        .brand img{flex:0 0 auto;width:58px;height:58px;border:1px solid rgba(255,255,255,.16);border-radius:15px;background:#fff;object-fit:cover}
        .brand-copy{min-width:0}
        .brand strong,.brand small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .brand strong{font-size:18px;font-weight:900}
        .brand small{margin-top:3px;color:var(--muted);font-size:13px;font-weight:600}
        .page-shell{width:min(1420px,calc(100% - 40px));margin:auto;padding:74px 0 100px}
        .section-heading{display:grid;grid-template-columns:minmax(0,.9fr) minmax(360px,.65fr);align-items:end;gap:50px;margin-bottom:34px}
        .eyebrow{display:flex;align-items:center;gap:9px;margin:0 0 10px;color:var(--cyan);font-size:13px;font-weight:900;letter-spacing:.04em}
        .eyebrow:before{content:'';width:34px;height:2px;border-radius:999px;background:var(--cyan);box-shadow:0 0 18px rgba(22,217,243,.65)}
        .section-heading h1{max-width:760px;margin:0;font-size:clamp(34px,4.3vw,62px);line-height:1.2;letter-spacing:-1.7px}
        .section-heading>p{margin:0;color:#a8bbc7;font-size:16px;line-height:2}
        .category-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}
        .category-card{--accent:#16d9f3;isolation:isolate;position:relative;display:flex;min-height:292px;flex-direction:column;justify-content:space-between;grid-column:span 4;overflow:hidden;padding:28px;border:1px solid rgba(89,122,141,.38);border-radius:26px;background:linear-gradient(145deg,rgba(17,36,48,.96),rgba(8,21,30,.98));box-shadow:0 20px 65px rgba(0,0,0,.18);transition:transform .25s ease,border-color .25s ease,box-shadow .25s ease}
        .category-card:nth-child(1){grid-column:span 7;min-height:348px;--accent:#23d7ec}
        .category-card:nth-child(2){grid-column:span 5;min-height:348px;--accent:#70a5ff}
        .category-card:nth-child(3){--accent:#57dfad}
        .category-card:nth-child(4){--accent:#f0bd67}
        .category-card:nth-child(5){--accent:#b68cff;background:linear-gradient(145deg,rgba(38,27,63,.96),rgba(11,22,34,.98))}
        .category-card:before{content:'';position:absolute;z-index:-2;width:250px;height:250px;inset-inline-end:-100px;top:-115px;border-radius:50%;background:var(--accent);filter:blur(4px);opacity:.13}
        .category-card:after{content:attr(data-number);position:absolute;z-index:-1;inset-inline-end:20px;bottom:-35px;color:transparent;font-size:146px;font-weight:900;line-height:1;-webkit-text-stroke:1px rgba(255,255,255,.055)}
        .category-card:hover{transform:translateY(-5px);border-color:color-mix(in srgb,var(--accent) 55%,transparent);box-shadow:0 30px 85px rgba(0,0,0,.3)}
        .card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
        .category-icon{display:grid;width:62px;height:62px;place-items:center;border:1px solid color-mix(in srgb,var(--accent) 48%,transparent);border-radius:19px;background:color-mix(in srgb,var(--accent) 10%,transparent);color:var(--accent);box-shadow:inset 0 0 24px color-mix(in srgb,var(--accent) 8%,transparent)}
        .category-icon svg{width:32px;height:32px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}
        .card-number{direction:ltr;color:rgba(255,255,255,.22);font-size:13px;font-weight:800;letter-spacing:.16em}
        .card-copy{position:relative;z-index:1}
        .card-copy h2{margin:0 0 9px;font-size:clamp(22px,2.1vw,31px);line-height:1.35}
        .card-copy p{max-width:540px;margin:0;color:#9fb2be;font-size:14px;line-height:1.9}
        .card-meta{display:flex;align-items:center;gap:9px;margin-top:20px;color:var(--accent);font-size:12px;font-weight:800}
        .card-meta:before{content:'';width:22px;height:1px;background:currentColor;opacity:.7}
        .custom-badge{position:absolute;top:28px;inset-inline-end:28px;padding:6px 11px;border:1px solid color-mix(in srgb,var(--accent) 50%,transparent);border-radius:999px;background:color-mix(in srgb,var(--accent) 12%,transparent);color:var(--accent);font-size:11px;font-weight:900}
        @media(max-width:600px){
            .topbar{width:min(100% - 22px,1720px);min-height:86px;gap:10px;padding:11px 0}
            .brand{gap:9px;min-width:0}
            .brand img{width:48px;height:48px;border-radius:12px}
            .brand strong{max-width:45vw;font-size:13px}
            .brand small{font-size:10px}
            .page-shell{width:min(100% - 22px,1420px);padding:46px 0 70px}
            .section-heading{display:block;margin-bottom:24px}
            .section-heading h1{font-size:32px;letter-spacing:-.8px}
            .section-heading>p{margin-top:14px;font-size:13px;line-height:1.9}
            .category-grid{display:grid;grid-template-columns:1fr;gap:12px}
            .category-card,.category-card:nth-child(1),.category-card:nth-child(2){grid-column:auto;min-height:250px;padding:22px;border-radius:21px}
            .category-icon{width:54px;height:54px;border-radius:16px}
            .category-icon svg{width:28px;height:28px}
            .card-copy h2{font-size:23px}
            .card-copy p{font-size:13px}
            .custom-badge{top:22px;inset-inline-end:22px}
        }
        @media(min-width:601px) and (max-width:980px){
            .page-shell{width:min(100% - 30px,1420px);padding-top:58px}
            .section-heading{grid-template-columns:1fr;gap:14px}
            .category-card,.category-card:nth-child(1),.category-card:nth-child(2){grid-column:span 6;min-height:300px}
            .category-card:nth-child(5){grid-column:span 12}
        }
    </style>
</head>
<body>
    <header class="site-header">
        <nav class="topbar" aria-label="{{ $shop->name }}">
            <a class="brand" href="{{ route('real-estate.company', $shop) }}">
                <img src="{{ $logo }}" alt="{{ $shop->name }}">
                <span class="brand-copy">
                    <strong>{{ $shop->name }}</strong>
                    <small>{{ $subtitle }}</small>
                </span>
            </a>
            @include('front.partials.public_language_switcher')
        </nav>
    </header>
    <main class="page-shell">
        <section aria-labelledby="company-categories-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">{{ $content['eyebrow'] }}</p>
                    <h1 id="company-categories-title">{{ $content['title'] }}</h1>
                </div>
                <p>{{ $content['intro'] }}</p>
            </div>

            <div class="category-grid">
                @foreach($content['categories'] as $index => $category)
                    <article class="category-card" data-number="{{ $category['number'] }}">
                        @if($index === 4)
                            <span class="custom-badge">{{ $content['custom_badge'] }}</span>
                        @endif
                        <div class="card-top">
                            <span class="category-icon" aria-hidden="true">
                                @switch($index)
                                    @case(0)
                                        <svg viewBox="0 0 32 32"><path d="M4 15 16 6l12 9"/><path d="M7 13v13h18V13M12 26v-7h8v7"/><path d="M3 27h26"/></svg>
                                        @break
                                    @case(1)
                                        <svg viewBox="0 0 32 32"><path d="M8 4v24M24 4v24M8 7h16M8 25h16"/><path d="M12 11h8v7a4 4 0 0 1-8 0v-7ZM16 7v4"/></svg>
                                        @break
                                    @case(2)
                                        <svg viewBox="0 0 32 32"><path d="M6 28V9l10-5 10 5v19M6 12h20"/><path d="M11 16h10v7H11zM16 23v5"/></svg>
                                        @break
                                    @case(3)
                                        <svg viewBox="0 0 32 32"><path d="M4 20V9h18l6 7v4H4Z"/><path d="M8 20v3M24 20v3M9 27a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM23 27a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M22 10v6h6"/></svg>
                                        @break
                                    @default
                                        <svg viewBox="0 0 32 32"><path d="m6 25 5-1 14-14-4-4L7 20l-1 5Z"/><path d="m18 9 4 4M5 28h22M9 7h6M12 4v6"/></svg>
                                @endswitch
                            </span>
                            <span class="card-number">{{ $category['number'] }}</span>
                        </div>
                        <div class="card-copy">
                            <h2>{{ $category['title'] }}</h2>
                            <p>{{ $category['description'] }}</p>
                            <span class="card-meta">{{ $content['meta'] }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
