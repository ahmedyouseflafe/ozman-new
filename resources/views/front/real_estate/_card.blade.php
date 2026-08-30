@php
    $cover = $property->images->first();
    $coverUrl = $cover ? asset($cover->path) : null;
    $propertyUrl = route('real-estate.property', [$property->shop, $property]);
    $purposeLabel = $property->purpose === 'rent' ? $labels['rent'] : $labels['sale'];
@endphp
<article class="property-card" data-property-id="{{ $property->id }}">
    <a class="property-media" href="{{ $propertyUrl }}">
        @if($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $cover->alt_text ?: $property->localized('title') }}" loading="lazy">
        @else
            <span class="no-image">{{ $labels['no_image'] }}</span>
        @endif
        <span class="purpose-badge">{{ $purposeLabel }}</span>
        @if($property->is_featured)<span class="featured-badge">★ {{ $labels['featured'] }}</span>@endif
    </a>
    <div class="property-card-body">
        <div class="company-line">{{ $property->shop->name }}</div>
        <a class="property-title" href="{{ $propertyUrl }}">{{ $property->localized('title') }}</a>
        <div class="property-location">📍 {{ $property->city }}{{ $property->neighborhood ? '، '.$property->neighborhood : '' }}</div>
        <div class="property-facts">
            @if($property->rooms)<span>🛏 {{ $property->rooms }} {{ $labels['rooms'] }}</span>@endif
            @if($property->bathrooms)<span>🚿 {{ $property->bathrooms }}</span>@endif
            @if($property->area)<span>▦ {{ number_format((float)$property->area) }} م²</span>@endif
        </div>
        <div class="property-card-footer">
            <strong>{{ number_format((float)$property->price) }} {{ $property->currency }}</strong>
            <div class="card-actions">
                <button type="button" class="icon-action favorite-button" data-favorite="{{ $property->id }}" aria-label="{{ $labels['favorite'] }}">♡</button>
                <label class="compare-toggle"><input type="checkbox" value="{{ $property->id }}" data-compare> {{ $labels['compare'] }}</label>
            </div>
        </div>
    </div>
</article>
