<div class="hero-media" data-nutella-display aria-label="شاشة عروض {{ $shop->name }}">
    @forelse($displayItems as $item)
        <article class="slide {{ $loop->first ? 'active' : '' }}" data-nutella-slide data-duration="{{ max(1, (int) ($item->duration ?? 8)) * 1000 }}" @if(!$loop->first) hidden @endif>
            @if($item->type === 'video' || $isVideo($item->media))
                <video src="{{ $mediaUrl($item->media) }}" muted playsinline loop preload="metadata"></video>
            @elseif($item->type === 'youtube')
                @php
                    $youtubeId = null;
                    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?.*?v=|embed/|shorts/))([a-zA-Z0-9_-]{11})~', $item->media, $match)) $youtubeId = $match[1];
                @endphp
                @if($youtubeId)
                    <iframe data-src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=1&amp;mute=1&amp;playsinline=1&amp;rel=0" title="{{ $item->title ?: $shop->name }}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                @endif
            @else
                <img src="{{ $mediaUrl($item->media) }}" alt="{{ $item->title ?: $shop->name }}">
            @endif
        </article>
    @empty
        <article class="slide active display-placeholder"><img src="{{ $shopBanner }}" alt="{{ $shop->name }}"></article>
    @endforelse
    @include('front.partials.display_sound_toggle')
</div>
<style>
.hero.hero--split{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,355px);gap:16px;min-height:0;overflow:visible;border:0;border-radius:0;background:none;box-shadow:none;direction:ltr}
.hero--split .hero-media{position:relative;inset:auto;min-width:0;min-height:360px;overflow:hidden;border:1px solid var(--line);border-radius:28px;background:#100705}
.hero--split .hero-media:after{inset:0;border:5px solid #30140e;border-radius:inherit;background:none;box-shadow:inset 0 0 0 1px #f2c26c55}
.hero--split .slide{pointer-events:none}
.hero--split .slide.active{pointer-events:auto}
.hero--split .slide[hidden]{display:none}
.hero--split .slide img,.hero--split .slide video,.hero--split .slide iframe{display:block;width:100%;height:100%;border:0;object-fit:contain;background:#100705}
.hero--split .display-placeholder img{padding:24px}
.hero--split .brand{position:relative;inset:auto;width:100%;min-width:0;min-height:360px;padding:64px 20px 24px;align-self:stretch;justify-content:flex-start;border-radius:28px;box-shadow:none;transform:none}
.hero--split .brand:before,.hero--split .brand:after{display:none}
.hero--split .tools{top:14px;left:12px;right:12px;flex-wrap:wrap;gap:6px}
.hero--split .brand h1{overflow-wrap:anywhere}
.hero--split .storefront-display-sound-toggle{border-color:#f2c26c;color:#f2c26c;background:#2b120de8;box-shadow:0 0 16px #f2c26c33}
@media(max-width:720px){
 .hero.hero--split{grid-template-columns:minmax(0,1fr) 145px;gap:8px}
 .hero--split .hero-media,.hero--split .brand{min-height:330px;border-radius:20px}
 .hero--split .brand{padding:85px 10px 16px;gap:10px}
 .hero--split .tools{top:10px;left:6px;right:6px;justify-content:center}
 .hero--split .tools .back{padding:4px 8px;font-size:9px}
 .hero--split .tools .public-language-switcher{max-width:100%;padding:3px}
 .hero--split .tools .public-language-switcher a{padding:4px;font-size:9px}
 .hero--split .tools .public-language-icon{display:none}
 .hero--split .logo{width:82px;height:82px;flex-shrink:0}
 .hero--split .brand h1{font-size:19px;line-height:1.5}
 .hero--split .brand p{font-size:10px}
 .hero--split .open{font-size:9px;padding:6px}
 .hero--split .cta{min-height:40px;padding:6px 9px;font-size:10px;line-height:1.6}
 .hero--split .display-placeholder img{padding:12px}
}
</style>
<script>
(() => {
    const screen = document.querySelector('[data-nutella-display]');
    const slides = [...screen.querySelectorAll('[data-nutella-slide]')];
    if (!slides.length) return;
    let index = 0, timer;
    function show() {
        clearTimeout(timer);
        slides.forEach((slide, i) => {
            const active = i === index;
            slide.hidden = !active; slide.classList.toggle('active', active);
            const video = slide.querySelector('video'), frame = slide.querySelector('iframe');
            if (video) {
                if (active && !document.hidden) video.play().catch(() => {});
                else { video.pause(); if (!active) video.currentTime = 0; }
            }
            if (frame) {
                if (active && !document.hidden) { if (!frame.hasAttribute('src')) frame.src = frame.dataset.src; }
                else frame.removeAttribute('src');
            }
        });
        if (slides.length > 1 && !document.hidden) timer = setTimeout(() => { index = (index + 1) % slides.length; show(); }, Number(slides[index].dataset.duration));
    }
    document.addEventListener('visibilitychange', show);
    window.addEventListener('pagehide', () => clearTimeout(timer));
    window.addEventListener('pageshow', show);
    show();
})();
</script>
