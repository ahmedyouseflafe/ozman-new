<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>نقاط البيع | {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--bg:#071118;--panel:#0c1b24;--line:#1d3b49;--cyan:#16d3ee;--green:#55e9a0;--muted:#9eb4c2}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 85% 0,rgba(0,207,242,.12),transparent 28%),var(--bg);color:#f7fbff;font-family:Cairo,Arial,sans-serif}.outlets-page{width:min(1400px,calc(100% - 310px));margin:0 270px 0 auto;padding:28px 0 55px}.hero,.panel{border:1px solid var(--line);border-radius:24px;background:linear-gradient(145deg,rgba(15,37,48,.96),rgba(7,19,26,.98));box-shadow:0 16px 50px rgba(0,0,0,.2)}.hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:25px}.eyebrow{color:var(--cyan);font-size:11px;font-weight:900;letter-spacing:.08em}.hero h1{margin:5px 0;font-size:29px}.hero p{margin:0;color:var(--muted);font-size:12px}.hero-actions{display:flex;gap:8px;flex-wrap:wrap}.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:0 13px;border:1px solid #276377;border-radius:12px;background:#102b37;color:#e8fbff;font:inherit;font-size:11px;font-weight:900;text-decoration:none;cursor:pointer}.btn-primary{border:0;background:linear-gradient(135deg,#14d7ef,#358fff);color:#04202a}.btn-danger{border-color:#7a3444;background:#2a1118;color:#ff9aae}.layout{display:grid;grid-template-columns:minmax(0,380px) minmax(0,1fr);gap:14px;margin-top:16px;align-items:start}.panel{padding:18px}.panel h2{margin:0 0 14px;font-size:18px}.form{display:grid;gap:11px}.form label{display:grid;gap:5px;color:var(--muted);font-size:11px;font-weight:700}.form input{min-height:40px;padding:0 11px;border:1px solid var(--line);border-radius:10px;background:#07151d;color:#fff;font:inherit;font-size:12px}.form .row{display:grid;grid-template-columns:1fr 1fr;gap:9px}.form .check{display:flex;align-items:center;gap:8px;color:#e8fbff}.form .check input{min-height:0;width:17px;height:17px;accent-color:var(--cyan)}.form small{color:var(--muted);font-size:10px;font-weight:400}.form-actions{display:flex;gap:8px;flex-wrap:wrap}.notice{margin-bottom:14px;padding:11px 13px;border:1px solid #4fcf9c;border-radius:12px;background:rgba(63,198,144,.1);color:#8affc5;font-size:12px}.errors{margin:0 0 12px;padding:11px 13px 11px 28px;border:1px solid #a0475a;border-radius:12px;background:rgba(160,71,90,.12);color:#ffb1c0;font-size:11px}.region{margin-bottom:16px}.region:last-child{margin-bottom:0}.region h3{display:flex;align-items:center;gap:7px;margin:0 0 8px;color:var(--cyan);font-size:13px}.region h3 span{padding:1px 8px;border-radius:999px;background:#102b37;color:#e8fbff;font-size:10px}.outlet{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px;border:1px solid var(--line);border-radius:14px;background:#081720}.outlet+.outlet{margin-top:7px}.outlet.is-hidden{opacity:.55}.outlet b{display:block;font-size:13px}.outlet p{margin:2px 0 0;color:var(--muted);font-size:11px;overflow-wrap:anywhere}.outlet-actions{display:flex;gap:6px;flex-shrink:0}.outlet-actions form{margin:0}.empty{padding:35px;text-align:center;color:var(--muted)}@media(max-width:1050px){.outlets-page{width:calc(100% - 28px);margin:auto;padding-top:78px}.layout{grid-template-columns:1fr}}@media(max-width:620px){.hero{align-items:flex-start;flex-direction:column;padding:18px}.hero h1{font-size:23px}.panel{padding:12px}.outlet{align-items:flex-start;flex-direction:column}.form .row{grid-template-columns:1fr}}
    </style>
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
