<!doctype html>
<html lang="{{ $alert->locale }}" dir="{{ in_array($alert->locale, ['ar','he'], true) ? 'rtl' : 'ltr' }}">
<body style="margin:0;background:#071018;color:#f7fbff;font-family:Arial,Tahoma,sans-serif">
<div style="max-width:620px;margin:auto;padding:28px">
    <div style="padding:26px;border:1px solid #263a48;border-radius:18px;background:#101b25">
        <p style="color:#16d9f3">مرحباً {{ $alert->name }}</p>
        <h1 style="font-size:25px">عقار يطابق بحثك</h1>
        @if($property->images->first())
            <img src="{{ url(Storage::url($property->images->first()->path)) }}" alt="{{ $property->localized('title') }}" style="width:100%;max-height:330px;object-fit:cover;border-radius:14px">
        @endif
        <h2>{{ $property->localized('title') }}</h2>
        <p style="color:#a9bdc9">📍 {{ implode('، ', array_filter([$property->city, $property->neighborhood])) }}</p>
        <p style="font-size:22px;color:#16d9f3;font-weight:bold">{{ number_format((float) $property->price) }} {{ $property->currency }}</p>
        <p>{{ $property->rooms ? $property->rooms.' غرف · ' : '' }}{{ $property->area ? number_format((float) $property->area).' م²' : '' }}</p>
        <a href="{{ $property->publicUrl() }}" style="display:block;padding:14px;border-radius:11px;background:#16d9f3;color:#04202a;text-align:center;text-decoration:none;font-weight:bold">عرض العقار والتواصل</a>
    </div>
    <p style="color:#7f96a5;font-size:12px;text-align:center">تم إرسال الرسالة لأنك حفظت تنبيهاً للعقارات في Ozman.</p>
</div>
</body>
</html>
