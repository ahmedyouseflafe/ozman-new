<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>تسجيلات الزوار - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.visitor_registrations.index')
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            <div class="content">
                <div class="hero-strip">
                    <div class="ticker">
                        <span>تسجيلات الزوار والعملاء والتجار</span>
                        <span>بيانات الدخول الأولى للموقع</span>
                        <span>متابعة طلبات الانضمام واللوكيشن</span>
                        <span>تسجيلات الزوار والعملاء والتجار</span>
                    </div>
                </div>

                <div class="page-head">
                    <div>
                        <div class="page-kicker">إدارة الزوار</div>
                        <h1>تسجيلات الزوار</h1>
                        <p>كل عميل أو صاحب متجر سجل بياناته من واجهة الموقع يظهر هنا.</p>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card" style="--card-color: var(--primary);">
                        <div class="stat-label">كل التسجيلات</div>
                        <div class="stat-val">{{ $totalCount }}</div>
                        <i class="ti ti-address-book stat-icon"></i>
                    </div>
                    <div class="stat-card" style="--card-color: var(--green);">
                        <div class="stat-label">عملاء</div>
                        <div class="stat-val">{{ $customersCount }}</div>
                        <i class="ti ti-user stat-icon"></i>
                    </div>
                    <div class="stat-card" style="--card-color: var(--yellow);">
                        <div class="stat-label">أصحاب متاجر</div>
                        <div class="stat-val">{{ $merchantsCount }}</div>
                        <i class="ti ti-building-store stat-icon"></i>
                    </div>
                </div>

                <section class="panel">
                    <div class="panel-head">
                        <div class="panel-title">
                            <i class="ti ti-list-details"></i>
                            القائمة
                        </div>

                        <form class="filter-row" method="GET" action="{{ route('visitor-registrations.index') }}">
                            <input
                                class="search-inp"
                                type="search"
                                name="search"
                                value="{{ $search }}"
                                placeholder="بحث بالاسم، الهاتف، المتجر">

                            <select class="filter-select" name="type" aria-label="فلترة النوع">
                                <option value="">كل الأنواع</option>
                                <option value="customer" @selected($selectedType === 'customer')>عملاء</option>
                                <option value="merchant" @selected($selectedType === 'merchant')>أصحاب متاجر</option>
                            </select>

                            <select class="filter-select" name="shop_id" aria-label="المحل الذي تم التسجيل منه">
                                <option value="">كل المحلات</option>
                                @foreach($registrationShops as $registrationShop)
                                    <option value="{{ $registrationShop->id }}" @selected($selectedShopId === $registrationShop->id)>{{ $registrationShop->name }}</option>
                                @endforeach
                            </select>
                            <button class="filter-btn" type="submit">
                                <i class="ti ti-search"></i>
                                بحث
                            </button>
                        </form>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>النوع</th>
                                    <th>الاسم والهاتف</th>
                                    <th>المحل / بيانات المتجر</th>
                                    <th>العنوان</th>
                                    <th>اللوكيشن</th>
                                    <th>الحالة</th>
                                    <th>الإجراء</th>
                                    <th>تاريخ التسجيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registrations as $registration)
                                    <tr>
                                        <td>
                                            @if($registration->type === 'merchant')
                                                <span class="tag tag-y">صاحب متجر</span>
                                            @else
                                                <span class="tag tag-c">عميل</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="main-cell">
                                                {{ $registration->name }}
                                                <span class="sub-line">{{ $registration->phone }}</span>
                                                @if($registration->type === 'customer')
                                                    @php
                                                        $whatsappDigits = preg_replace('/\D+/', '', $registration->phone);
                                                        if (str_starts_with($whatsappDigits, '00')) $whatsappDigits = substr($whatsappDigits, 2);
                                                        if (str_starts_with($whatsappDigits, '05')) $whatsappDigits = (in_array(substr($whatsappDigits, 0, 3), ['056', '059']) ? '970' : '972').substr($whatsappDigits, 1);
                                                    @endphp
                                                    <a class="map-link" href="https://wa.me/{{ $whatsappDigits }}" target="_blank" rel="noopener noreferrer" aria-label="واتساب {{ $registration->name }}"><i class="ti ti-brand-whatsapp"></i> واتساب</a>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($registration->type === 'merchant')
                                                <div class="main-cell">
                                                    {{ $registration->shop_name ?: '-' }}
                                                    <span class="sub-line">الملف الضريبي: {{ $registration->tax_file ?: '-' }}</span>
                                                </div>
                                            @else
                                                {{ $registration->shop?->name ?: 'الموقع العام' }}
                                            @endif
                                        </td>
                                        <td>{{ $registration->residence_address }}</td>
                                        <td>
                                            @if($registration->map_link)
                                                <a class="map-link" href="{{ $registration->map_link }}" target="_blank" rel="noopener noreferrer">
                                                    <i class="ti ti-map-pin"></i>
                                                    فتح الخريطة
                                                </a>
                                                <span class="sub-line">{{ $registration->latitude }}, {{ $registration->longitude }}</span>
                                            @else
                                                لم يُحدّد بعد
                                            @endif
                                        </td>
                                        <td>
                                            @if($registration->type !== 'merchant' || $registration->status === 'approved')
                                                <span class="tag tag-g">مقبول</span>
                                            @elseif($registration->status === 'rejected')
                                                <span class="tag tag-r">مرفوض</span>
                                            @else
                                                <span class="tag tag-y">قيد المراجعة</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($registration->type === 'merchant')
                                                <div class="approval-actions">
                                                    <form method="POST" action="{{ route('visitor-registrations.status.update', $registration) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="approved">
                                                        <button class="approval-btn" type="submit">قبول</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('visitor-registrations.status.update', $registration) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="rejected">
                                                        <button class="approval-btn reject" type="submit">رفض</button>
                                                    </form>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            {{ $registration->created_at?->format('Y-m-d H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="ti ti-address-book-off"></i>
                                                لا توجد تسجيلات حتى الآن
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($registrations->hasPages())
                        <div class="pagination">
                            @if($registrations->onFirstPage())
                                <span class="disabled">السابق</span>
                            @else
                                <a href="{{ $registrations->previousPageUrl() }}">السابق</a>
                            @endif

                            @foreach($registrations->getUrlRange(1, $registrations->lastPage()) as $page => $url)
                                @if($page === $registrations->currentPage())
                                    <span class="active">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}">{{ $page }}</a>
                                @endif
                            @endforeach

                            @if($registrations->hasMorePages())
                                <a href="{{ $registrations->nextPageUrl() }}">التالي</a>
                            @else
                                <span class="disabled">التالي</span>
                            @endif
                        </div>
                    @endif
                </section>
            </div>
        </main>
    </div>
</body>

</html>
