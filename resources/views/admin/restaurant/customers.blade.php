<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>العملاء المسجّلون | {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 85% 8%,#00dcf418,transparent 35%),radial-gradient(circle at 10% 15%,#7000ff1a,transparent 35%),#060b12;color:#effaff;font-family:Cairo,Arial,sans-serif}
        .customer-page{margin-right:245px;padding:30px;min-width:0}.customer-panel{border:1px solid #203944;border-radius:25px;background:linear-gradient(135deg,#151524,#091d23);padding:26px;margin-bottom:22px}
        .customer-head{display:flex;justify-content:space-between;align-items:center;gap:20px}.customer-kicker{color:#08dcf4;font-size:11px;font-weight:900}.customer-head h1{margin:6px 0;font-size:clamp(24px,3vw,36px)}.customer-head p{color:#9daeb9;font-size:13px;margin:8px 0 0;line-height:1.9}
        .customer-actions{display:flex;gap:9px;flex-wrap:wrap}.customer-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:43px;padding:10px 15px;border:1px solid #275263;border-radius:13px;background:#0b202a;color:#31dff2;text-decoration:none;font:800 12px Cairo,Arial,sans-serif;cursor:pointer}.customer-btn.primary{background:#0bcfe9;color:#00202a;border-color:#0bcfe9}
        .customer-toolbar{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px}.customer-count{font-size:14px;color:#9fb6c1}.customer-count strong{color:#0de0f3;font-size:28px;margin-inline-end:8px}.customer-search{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.customer-search input{min-height:44px;width:280px;max-width:100%;padding:10px 13px;border:1px solid #294650;border-radius:12px;background:#060f17;color:#fff;font:inherit;font-size:12px}.customer-search label{display:block;color:#9fb6c1;font-size:11px;margin-bottom:4px}
        .customer-table{width:100%;min-width:880px;border-collapse:collapse;font-size:12px}.customer-table th,.customer-table td{padding:17px 14px;text-align:right;border-bottom:1px solid #24353e;vertical-align:top}.customer-table th{color:#19d8ed;background:#09212a;font-size:11px}.customer-table td{color:#c5d3dc}.customer-table .customer-name{font-weight:900;color:#f3fbff}.customer-table a{color:#28e4ef;text-decoration:none}.customer-table small{display:block;color:#91a8b4;margin-top:6px;font-size:10px}.customer-table .customer-address{max-width:250px;overflow-wrap:anywhere}.customer-table time{white-space:nowrap}.customer-table .customer-name{max-width:220px;overflow-wrap:anywhere}.customer-empty{text-align:center!important;padding:50px 20px!important;color:#9cb2be!important}.customer-empty i{display:block;color:#0ed5e9;font-size:32px;margin-bottom:12px}
        .customer-pagination{display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:14px;margin-top:20px;font-size:12px;color:#a4bac4}.customer-results{color:#a4bac4;font-size:12px}.customer-page :focus-visible{outline:2px solid #08d9ef;outline-offset:3px}
        @media(max-width:900px){.customer-page{margin-right:0;padding:18px 14px 100px}.customer-head,.customer-toolbar{align-items:flex-start;flex-direction:column}.customer-panel{padding:20px 16px;border-radius:20px}.customer-search{width:100%}.customer-search>div{width:100%}.customer-search input{width:100%}.customer-head h1{font-size:25px}}
    </style>
</head>
<body>
@include('admin.includes.sidebar')
<main class="customer-page">
    <header class="customer-panel customer-head">
        <div>
            <span class="customer-kicker">{{ $shop->name }}</span>
            <h1>العملاء المسجّلون</h1>
            <p>بيانات العملاء الذين حفظوا تسجيلهم من صفحة مطعمك، حتى لو لم يرسلوا طلبًا بعد.</p>
        </div>
        <div class="customer-actions">
            @if(auth()->user()->canAccessRouteName('restaurant.dashboard'))
                <a class="customer-btn" href="{{ route('restaurant.dashboard', $shop) }}"><i class="ti ti-arrow-right" aria-hidden="true"></i> لوحة المطعم</a>
            @endif
            <a class="customer-btn primary" href="{{ route('restaurant.customers.index', ['shop' => $shop, 'search' => $search]) }}"><i class="ti ti-refresh" aria-hidden="true"></i> تحديث القائمة</a>
        </div>
    </header>
    <section class="customer-panel" aria-label="قائمة العملاء">
        <div class="customer-toolbar">
            <div class="customer-count"><strong>{{ $customersCount }}</strong> تسجيل لدى المطعم</div>
            <form class="customer-search" method="get" action="{{ route('restaurant.customers.index', $shop) }}">
                <div><label for="customerSearch">بحث بالاسم، رقم الواتساب أو العنوان</label><input type="search" id="customerSearch" name="search" value="{{ $search }}" maxlength="120" placeholder="ابحث عن عميل"></div>
                <button class="customer-btn primary" type="submit"><i class="ti ti-search" aria-hidden="true"></i> بحث</button>
                @if($search !== '')<a class="customer-btn" href="{{ route('restaurant.customers.index', $shop) }}">مسح البحث</a>@endif
            </form>
        </div>
        @if($search !== '')<p class="customer-results">{{ $customers->total() }} نتيجة للبحث</p>@endif
        <div class="table-wrap" tabindex="0" role="region" aria-label="جدول العملاء المسجّلين">
            <table class="customer-table">
                <thead><tr><th>الاسم</th><th>رقم الواتساب</th><th>العنوان</th><th>اللوكيشن</th><th>تاريخ التسجيل</th><th>آخر تعديل</th></tr></thead>
                <tbody>
                @forelse($customers as $customer)
                    @php
                        $digits = preg_replace('/\D+/', '', $customer->phone);
                        if (str_starts_with($digits, '00')) $digits = substr($digits, 2);
                        if (str_starts_with($digits, '05')) $digits = (in_array(substr($digits, 0, 3), ['056', '059']) ? '970' : '972').substr($digits, 1);
                        $hasLocation = $customer->latitude !== null && $customer->longitude !== null;
                    @endphp
                    <tr>
                        <td class="customer-name">{{ $customer->name }}</td>
                        <td><span dir="ltr">{{ $customer->phone }}</span><small><a href="https://wa.me/{{ $digits }}" target="_blank" rel="noopener noreferrer"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i> فتح واتساب</a></small></td>
                        <td class="customer-address">{{ $customer->residence_address }}</td>
                        <td>@if($hasLocation)<a href="https://www.google.com/maps?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" rel="noopener noreferrer"><i class="ti ti-map-pin" aria-hidden="true"></i> فتح الخريطة</a><small dir="ltr">{{ $customer->latitude }}, {{ $customer->longitude }}</small>@else لم يُحدّد بعد @endif</td>
                        <td><time datetime="{{ $customer->created_at->toIso8601String() }}">{{ $customer->created_at->format('Y-m-d H:i') }}</time></td>
                        <td><time datetime="{{ $customer->updated_at->toIso8601String() }}">{{ $customer->updated_at->format('Y-m-d H:i') }}</time></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="customer-empty"><i class="ti ti-users" aria-hidden="true"></i>{{ $search !== '' ? 'لا توجد نتائج مطابقة لبحثك.' : 'لم يسجّل أي عميل بعد. سيظهر هنا من يحفظ بياناته من صفحة المطعم.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <nav class="customer-pagination" aria-label="صفحات العملاء">
                @if($customers->previousPageUrl())<a class="customer-btn" href="{{ $customers->previousPageUrl() }}" rel="prev">السابق</a>@endif
                <span>صفحة {{ $customers->currentPage() }} من {{ $customers->lastPage() }}</span>
                @if($customers->nextPageUrl())<a class="customer-btn" href="{{ $customers->nextPageUrl() }}" rel="next">التالي</a>@endif
            </nav>
        @endif
    </section>
</main>
</body>
</html>
