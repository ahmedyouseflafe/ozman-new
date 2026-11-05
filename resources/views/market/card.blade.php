<article class="market-card">
    <a href="{{ $listing->url() }}" class="market-card-media">
        @if($cover = $listing->coverUrl())<img src="{{ $cover }}" alt="{{ $listing->title }}" loading="lazy" decoding="async">@else<span class="market-noimg" aria-hidden="true">🛍</span>@endif
        <span class="market-badge">{{ $listing->conditionLabel() }}</span>
    </a>
    <div class="market-card-body">
        <strong class="market-price">{{ $listing->formattedPrice() }}</strong>
        <h3><a href="{{ $listing->url() }}">{{ $listing->title }}</a></h3>
        <p>{{ $listing->city }} · {{ $listing->categoryLabel() }}</p>
    </div>
</article>
