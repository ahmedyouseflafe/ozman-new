@if($watchProducts->isNotEmpty())
    <section class="watch-catalog" dir="rtl" aria-labelledby="watchCatalogTitle">
        <img id="watchCatalogImage" src="{{ $mediaUrl($watchProducts->first()->main_image) }}" alt="{{ $watchProducts->first()->localized('name') }}">
        <div class="watch-catalog-intro">
            <h2 id="watchCatalogTitle">شوف الساعة على إيدك</h2>
            <p>اختار ساعة من المحل، وصوّر معصمك أو ارفع صورته لتجربة شكلها عليك.</p>
        </div>
        <div class="watch-catalog-controls">
            <label for="watchCatalogSelect">الساعة اللي بدك تجربها</label>
            <select id="watchCatalogSelect" aria-describedby="watchCatalogName">
                @foreach($watchProducts as $watchProduct)
                    <option value="{{ $watchProduct->id }}" data-image="{{ $mediaUrl($watchProduct->main_image) }}">{{ $watchProduct->localized('name') }}</option>
                @endforeach
            </select>
            <p id="watchCatalogName" dir="auto" aria-live="polite">{{ $watchProducts->first()->localized('name') }}</p>
            <button type="button" id="watchTryOnOpen">جرّب الساعة على إيدك</button>
            <small>معاينة مولّدة للصورة؛ قد تختلف بعض التفاصيل، ولا تحدد قياس الساعة أو السوار.</small>
        </div>
    </section>
@endif
