<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('screens.main')
</head>

<body>
    <div class="screen">
        <header class="topbar">
            <div class="ticker-box">
                <div class="ticker">
                    <span>{{ __('أهلاً بك في هيبتي شوب') }}</span>
                    <span>{{ __('منتجات العناية الفاخرة') }}</span>
                    <span>{{ __('جمالك يستحق العناية الفائقة') }}</span>
                    <span>{{ $title }}</span>
                </div>
            </div>

            <div class="brand">
                @if($shop?->logo)
                    <img src="{{ asset($shop->logo) }}" class="brand-logo" alt="{{ $shop->name }}">
                @else
                    <div class="brand-logo"><i class="ti ti-wolf"></i></div>
                @endif
                <div class="location"><i class="ti ti-map-pin"></i>{{ $shop ? __('حدد موقعك') : __('الشاشة الرئيسية') }}</div>
            </div>
        </header>

        <main class="stage">
            @if($items->isNotEmpty())
                <div class="carousel" id="carousel">
                    <div class="section-title">{{ $shop ? __('إعلانات المتجر') : __('الشاشة الرئيسية') }}</div>

                    @foreach($items as $index => $item)
                        <article class="slide" data-duration="{{ max((int) $item->duration, 1) * 1000 }}" style="{{ $index === 0 ? '' : 'display:none' }}">
                            <div class="media-frame">
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
                                @if($item->type === 'video')
                                    <button type="button" class="sound-toggle" data-sound-toggle aria-label="{{ __('تفعيل صوت الفيديو') }}" aria-pressed="false" title="{{ __('تفعيل الصوت') }}">
                                        <i class="ti ti-volume-off" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                            <div class="item-title">{{ $item->title }}</div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty">{{ __('لا يوجد محتوى نشط للعرض حالياً.') }}</div>
            @endif
        </main>

        <footer class="bottom-nav">
            <button class="order-btn">{{ __('اطلب الآن') }}</button>
            <div class="nav-icons">
                <i class="ti ti-brand-whatsapp"></i>
                <i class="ti ti-message-circle"></i>
                <i class="ti ti-shopping-cart"></i>
                <i class="ti ti-search"></i>
                <i class="ti ti-home"></i>
            </div>
        </footer>
    </div>

    @viewScript('screens.main')
</body>

</html>
