@php
    $locale = app()->getLocale();
    $rtl = in_array($locale, ['ar', 'he'], true);
    $subtitle = [
        'ar' => 'عقارات الشركة',
        'he' => 'נדל״ן החברה',
        'en' => 'Company properties',
    ][$locale] ?? 'عقارات الشركة';
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
        @media(max-width:600px){
            .topbar{width:min(100% - 22px,1720px);min-height:86px;gap:10px;padding:11px 0}
            .brand{gap:9px;min-width:0}
            .brand img{width:48px;height:48px;border-radius:12px}
            .brand strong{max-width:45vw;font-size:13px}
            .brand small{font-size:10px}
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
</body>
</html>
