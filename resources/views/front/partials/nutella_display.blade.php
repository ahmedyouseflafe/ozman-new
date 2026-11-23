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
@viewStyle('front.partials.nutella_display')
@viewScript('front.partials.nutella_display')
