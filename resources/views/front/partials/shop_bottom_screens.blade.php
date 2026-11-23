@php
    $bottomShopScreens = $shop->activeScreens->filter(fn ($screen) => ($screen->placement ?: 'top') === ($screenPlacement ?? 'bottom'));
@endphp
@if($bottomShopScreens->isNotEmpty())
<section data-shop-bottom-screens aria-label="{{ __('شاشة عروض :shop', ['shop' => $shop->name]) }}" style="max-width:1200px;margin:24px auto 100px;padding:0 16px">
    @foreach($bottomShopScreens as $item)
        <article data-screen-slide data-duration="{{ max(1, (int) $item->duration) * 1000 }}" @if(!$loop->first) hidden @endif>
            @if($item->type === 'video')
                <video src="{{ asset($item->media) }}" controls muted playsinline loop preload="metadata" style="width:100%;max-height:65vh;background:#000;border-radius:20px"></video>
            @elseif($item->type === 'youtube')
                @php
                    $youtubeId = null;
                    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?.*?v=|embed/|shorts/))([a-zA-Z0-9_-]{11})~', $item->media, $match)) $youtubeId = $match[1];
                @endphp
                @if($youtubeId)
                    <iframe data-src="https://www.youtube.com/embed/{{ $youtubeId }}?mute=1&amp;playsinline=1&amp;rel=0" title="{{ $item->title }}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen style="width:100%;aspect-ratio:16/9;border:0;border-radius:20px"></iframe>
                @endif
            @else
                <img src="{{ asset($item->media) }}" alt="{{ $item->title }}" loading="lazy" style="width:100%;max-height:65vh;object-fit:contain;background:#000;border-radius:20px">
            @endif
        </article>
    @endforeach
</section>
@viewScript('front.partials.shop_bottom_screens')
@endif
