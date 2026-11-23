<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>نقاط البيع | {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.shop_outlets.index')
</head>
<body>
@include('admin.includes.sidebar')
<main class="outlets-page">
    @if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
    <section class="hero">
        <div><span class="eyebrow">نقاط البيع</span><h1>نقاط بيع {{ $shop->name }}</h1><p>المحلات اللي بتبيع منتجاتك. بتظهر بصفحة الشركة مجمّعة حسب المنطقة، مع زر يفتحها على الخريطة.</p></div>
        <div class="hero-actions"><a class="btn btn-primary" target="_blank" href="{{ route('krembo.store', $shop) }}#outlets"><i class="ti ti-eye"></i> معاينة الصفحة</a></div>
    </section>

    <div class="layout">
        <section class="panel" id="outlet-form">
            <h2>{{ $editing ? 'تعديل نقطة بيع' : 'إضافة نقطة بيع' }}</h2>
            @if($errors->any())<ul class="errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
            @php($outlet = $editing)
            <form class="form" method="post" action="{{ $outlet ? route('shop-outlets.update', [$shop, $outlet]) : route('shop-outlets.store', $shop) }}">
                @csrf
                @if($outlet) @method('put') @endif
                <label>اسم المحل *<input name="name" required maxlength="255" value="{{ old('name', $outlet?->name) }}"></label>
                <label>المنطقة / المدينة *<input name="region" required maxlength="255" list="outlet-regions" value="{{ old('region', $outlet?->region) }}" placeholder="مثلاً: جنين"></label>
                <datalist id="outlet-regions">@foreach($outlets->pluck('region')->unique() as $region)<option value="{{ $region }}">@endforeach</datalist>
                <label>العنوان<input name="address" maxlength="255" value="{{ old('address', $outlet?->address) }}"></label>
                <label>رابط الخريطة <small>(اختياري)</small><input name="map_url" type="url" dir="ltr" maxlength="1000" value="{{ old('map_url', $outlet?->map_url) }}" placeholder="https://maps.app.goo.gl/..."></label>
                <div class="row">
                    <label>خط العرض <small>(اختياري)</small><input name="latitude" inputmode="decimal" dir="ltr" value="{{ old('latitude', $outlet?->latitude) }}" placeholder="32.4610"></label>
                    <label>خط الطول <small>(اختياري)</small><input name="longitude" inputmode="decimal" dir="ltr" value="{{ old('longitude', $outlet?->longitude) }}" placeholder="35.3000"></label>
                </div>
                <small>الإحداثيات بتخلّي المحل يظهر على خريطة "وين بتلاقينا" ويترتب حسب الأقرب للزبون.</small>
                <div class="row">
                    <label>الرقم <small>(اختياري)</small><input name="phone" type="tel" dir="ltr" maxlength="50" value="{{ old('phone', $outlet?->phone) }}"></label>
                    <label>الترتيب<input name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $outlet?->sort_order ?? 0) }}"></label>
                </div>
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $outlet?->is_active ?? true))> ظاهر بالصفحة</label>
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy"></i> {{ $outlet ? 'حفظ التعديل' : 'إضافة' }}</button>
                    @if($outlet)<a class="btn" href="{{ route('shop-outlets.index', $shop) }}">إلغاء</a>@endif
                </div>
            </form>
        </section>

        <section class="panel">
            <h2>نقاط البيع ({{ $outlets->count() }})</h2>
            @forelse($outlets->groupBy('region') as $region => $regionOutlets)
                <div class="region">
                    <h3><i class="ti ti-map-pin"></i> {{ $region }} <span>{{ $regionOutlets->count() }}</span></h3>
                    @foreach($regionOutlets as $item)
                        <article class="outlet {{ $item->is_active ? '' : 'is-hidden' }}">
                            <div>
                                <b>{{ $item->name }}@unless($item->is_active) <small>(مخفي)</small>@endunless</b>
                                @if($item->address)<p>{{ $item->address }}</p>@endif
                                @if($item->phone)<p dir="ltr" style="text-align:right">{{ $item->phone }}</p>@endif
                            </div>
                            <div class="outlet-actions">
                                @if($item->mapUrl())<a class="btn" target="_blank" rel="noopener noreferrer" href="{{ $item->mapUrl() }}" title="الخريطة"><i class="ti ti-map-2"></i></a>@endif
                                <a class="btn" href="{{ route('shop-outlets.edit', [$shop, $item]) }}#outlet-form" title="تعديل"><i class="ti ti-pencil"></i></a>
                                <form method="post" action="{{ route('shop-outlets.destroy', [$shop, $item]) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from('حذف '.$item->name.'؟') }})">@csrf @method('delete')<button class="btn btn-danger" type="submit" title="حذف"><i class="ti ti-trash"></i></button></form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @empty
                <div class="empty">لسا ما في نقاط بيع. ضيف أول محل من النموذج.</div>
            @endforelse
        </section>
    </div>
</main>
</body>
</html>
