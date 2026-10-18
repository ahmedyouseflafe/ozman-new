@if($watchProducts->isNotEmpty())
    <section class="watch-catalog" dir="rtl" aria-labelledby="watchCatalogTitle">
        <img id="watchCatalogImage" src="{{ $mediaUrl($watchProducts->first()->main_image) }}" alt="{{ $watchProducts->first()->localized('name') }}">
        <div>
            <h2 id="watchCatalogTitle">شوف الساعة على إيدك</h2>
            <p>اختار ساعة من المحل، وصوّر معصمك أو ارفع صورته لتجربة شكلها عليك.</p>
            <label for="watchCatalogSelect">الساعة اللي بدك تجربها</label>
            <select id="watchCatalogSelect">
                @foreach($watchProducts as $watchProduct)
                    <option value="{{ $watchProduct->id }}" data-image="{{ $mediaUrl($watchProduct->main_image) }}">{{ $watchProduct->localized('name') }}</option>
                @endforeach
            </select>
            <button type="button" id="watchTryOnOpen">جرّب الساعة على إيدك</button>
            <small>معاينة مولّدة للصورة؛ قد تختلف بعض التفاصيل، ولا تحدد قياس الساعة أو السوار.</small>
        </div>
    </section>
@endif
