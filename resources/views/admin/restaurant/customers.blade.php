<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>العملاء المسجّلون | {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.restaurant.customers')
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
