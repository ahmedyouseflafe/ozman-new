<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>حجوزات الصالون | {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.salon_appointments.index')
</head>

<body>
    @include('admin.includes.sidebar')
    <main class="salon-page">
        @if (session('status'))
            <div class="notice">{{ session('status') }}</div>
        @endif
        <section class="hero">
            <div><span class="eyebrow">الحجوزات</span>
                <h1>حجوزات {{ $shop->name }}</h1>
                <p>كل حجز محفوظ هنا، والموعد المحجوز لا يظهر متاحًا للعملاء الآخرين.</p>
            </div>
            <div class="hero-actions"><a class="btn" target="_blank"
                    href="{{ route('cosmetics.booking', $shop) }}"><i class="ti ti-external-link"></i> صفحة الحجز</a><a
                    class="btn btn-primary" target="_blank" href="{{ route('cosmetics.store', $shop) }}"><i
                        class="ti ti-eye"></i> معاينة المتجر</a></div>
        </section>
        <section class="stats">
            <article class="stat"><small>مواعيد اليوم</small><strong>{{ $stats['today'] }}</strong></article>
            <article class="stat"><small>حجوزات جديدة</small><strong>{{ $stats['new'] }}</strong></article>
            <article class="stat"><small>مواعيد مؤكدة</small><strong>{{ $stats['confirmed'] }}</strong></article>
        </section>
        <section class="panel">
            <header class="panel-head">
                <h2><i class="ti ti-calendar-heart" style="color:var(--cyan)"></i> إدارة الحجوزات</h2>
                <form class="filters" method="get"><label>التاريخ<input type="date" name="date"
                            value="{{ $selectedDate }}"></label><label>الحالة<select name="status">
                            <option value="">كل الحالات</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </label><button class="btn" type="submit">تصفية</button></form>
            </header>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>الخدمة</th>
                            <th>الموعد</th>
                            <th>الملاحظات</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appointments as $appointment)
                            <tr>
                                <td class="customer"><b>{{ $appointment->customer_name }}</b><a
                                        href="https://wa.me/{{ preg_replace('/\D+/', '', $appointment->customer_phone) }}"
                                        target="_blank" rel="noopener">{{ $appointment->customer_phone }}</a></td>
                                <td>{{ $appointment->service }}</td>
                                <td><b>{{ $appointment->appointment_at->format('Y-m-d') }}</b><br><span
                                        class="muted">{{ $appointment->appointment_at->format('H:i') }}</span></td>
                                <td>{{ $appointment->notes ?: '—' }}</td>
                                <td>
                                    <form class="status-form" method="post"
                                        action="{{ route('salon-appointments.status', [$shop, $appointment]) }}">@csrf
                                        @method('patch')<select name="status"
                                            class="status-{{ $appointment->status }}">
                                            @foreach ($statusLabels as $value => $label)
                                                <option value="{{ $value }}" @selected($appointment->status === $value)>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn" type="submit" title="حفظ"><i
                                                class="ti ti-device-floppy"></i></button>
                                    </form>
                                </td>
                        </tr>@empty<tr>
                                <td class="empty" colspan="5">لا توجد حجوزات حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $appointments->links() }}</div>
        </section>
    </main>
</body>

</html>
