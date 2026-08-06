<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --primary:#00e5ff; --accent:#7000ff; --text:#fff; --muted:rgba(255,255,255,.6); --border:rgba(0,229,255,.22); }
        html, body { min-height:100%; background:#020404; color:var(--text); font-family:'Cairo','Segoe UI',sans-serif; overflow-x:hidden; }
        body::before { content:''; position:fixed; inset:0; background:radial-gradient(circle at 50% 42%, rgba(0,229,255,.12), transparent 24%), radial-gradient(circle at 76% 82%, rgba(112,0,255,.10), transparent 20%); pointer-events:none; }
        .screen { min-height:100vh; position:relative; z-index:1; display:flex; flex-direction:column; }
        .topbar { min-height:138px; border-bottom:1px solid rgba(255,255,255,.08); display:grid; grid-template-columns:1fr auto; gap:26px; align-items:center; padding:20px 48px; }
        .ticker-box { min-height:96px; border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.035); border-radius:24px; overflow:hidden; display:flex; align-items:center; box-shadow:inset 0 0 35px rgba(0,229,255,.04); }
        .ticker { width:max-content; display:flex; gap:42px; white-space:nowrap; animation:slide 24s linear infinite; color:var(--primary); font-weight:900; font-size:18px; text-shadow:0 0 12px rgba(0,229,255,.72); }
        .ticker span { padding-inline:18px; }
        @keyframes slide { from { transform:translateX(0); } to { transform:translateX(50%); } }
        .brand { display:grid; place-items:center; gap:10px; }
        .brand-logo { width:96px; height:96px; border-radius:50%; border:2px solid var(--primary); box-shadow:0 0 24px rgba(0,229,255,.72); object-fit:cover; background:#061011; display:grid; place-items:center; color:var(--primary); font-size:40px; }
        .location { min-height:36px; border:1px solid var(--primary); border-radius:999px; padding:0 14px; display:inline-flex; align-items:center; gap:8px; color:var(--primary); font-weight:900; background:#020707; box-shadow:0 0 14px rgba(0,229,255,.34); }
        .stage { flex:1; display:grid; place-items:center; padding:72px 24px; }
        .carousel { width:min(520px, 80vw); display:grid; place-items:center; gap:18px; text-align:center; }
        .section-title { color:var(--primary); font-size:24px; font-weight:900; text-shadow:0 0 15px rgba(0,229,255,.78); }
        .media-card { width:min(330px, 74vw); aspect-ratio:3 / 4; border:2px solid var(--primary); border-radius:28px; background:#060606; box-shadow:0 0 42px rgba(0,229,255,.28); overflow:hidden; display:grid; place-items:center; }
        .media-card img, .media-card video { width:100%; height:100%; object-fit:cover; }
        .youtube { padding:22px; color:var(--primary); font-weight:900; overflow-wrap:anywhere; }
        .youtube i { display:block; font-size:64px; margin-bottom:14px; }
        .item-title { font-size:20px; font-weight:900; color:#fff; text-shadow:0 0 12px rgba(0,229,255,.32); }
        .empty { color:var(--muted); font-size:18px; font-weight:900; text-align:center; }
        .bottom-nav { min-height:92px; border-top:1px solid rgba(255,255,255,.08); display:flex; align-items:center; justify-content:space-between; gap:18px; padding:18px 10vw; }
        .order-btn { min-height:48px; border:0; border-radius:999px; padding:0 28px; background:var(--primary); color:#001014; font:inherit; font-weight:900; box-shadow:0 0 24px rgba(0,229,255,.58); }
        .nav-icons { display:flex; gap:22px; color:#fff; font-size:24px; }
        .nav-icons i { filter:drop-shadow(0 0 8px rgba(0,229,255,.5)); }
        @media(max-width:760px) { .topbar{grid-template-columns:1fr; padding:18px;} .brand{grid-row:1;} .stage{padding:44px 16px;} .bottom-nav{padding:18px;}.ticker{font-size:15px;} }
    </style>
</head>

<body>
    <div class="screen">
        <header class="topbar">
            <div class="ticker-box">
                <div class="ticker">
                    <span>أهلاً بك في هيبتي شوب</span>
                    <span>منتجات العناية الفاخرة</span>
                    <span>جمالك يستحق العناية الفائقة</span>
                    <span>{{ $title }}</span>
                </div>
            </div>

            <div class="brand">
                @if($shop?->logo)
                    <img src="{{ asset($shop->logo) }}" class="brand-logo" alt="{{ $shop->name }}">
                @else
                    <div class="brand-logo"><i class="ti ti-wolf"></i></div>
                @endif
                <div class="location"><i class="ti ti-map-pin"></i>{{ $shop ? 'حدد موقعك' : 'الشاشة الرئيسية' }}</div>
            </div>
        </header>

        <main class="stage">
            @if($items->isNotEmpty())
                <div class="carousel" id="carousel">
                    <div class="section-title">{{ $shop ? 'إعلانات المتجر' : 'الشاشة الرئيسية' }}</div>

                    @foreach($items as $index => $item)
                        <article class="slide" data-duration="{{ max((int) $item->duration, 1) * 1000 }}" style="{{ $index === 0 ? '' : 'display:none' }}">
                            <div class="media-card">
                                @if($item->type === 'image')
                                    <img src="{{ asset($item->media) }}" alt="{{ $item->title }}">
                                @elseif($item->type === 'video')
                                    <video src="{{ asset($item->media) }}" muted playsinline autoplay loop></video>
                                @else
                                    <div class="youtube">
                                        <i class="ti ti-brand-youtube"></i>
                                        {{ $item->media }}
                                    </div>
                                @endif
                            </div>
                            <div class="item-title">{{ $item->title }}</div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty">لا يوجد محتوى نشط للعرض حالياً.</div>
            @endif
        </main>

        <footer class="bottom-nav">
            <button class="order-btn">اطلب الآن</button>
            <div class="nav-icons">
                <i class="ti ti-brand-whatsapp"></i>
                <i class="ti ti-message-circle"></i>
                <i class="ti ti-shopping-cart"></i>
                <i class="ti ti-search"></i>
                <i class="ti ti-home"></i>
            </div>
        </footer>
    </div>

    <script>
        const slides = [...document.querySelectorAll('.slide')];
        let index = 0;

        function nextSlide() {
            if (slides.length <= 1) return;
            slides[index].style.display = 'none';
            index = (index + 1) % slides.length;
            slides[index].style.display = '';
            setTimeout(nextSlide, Number(slides[index].dataset.duration || 10000));
        }

        if (slides.length > 1) {
            setTimeout(nextSlide, Number(slides[0].dataset.duration || 10000));
        }
    </script>
</body>

</html>
