<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('نقاط العملاء') }} - Ozman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.customer_points.index')
</head>
<body>
@include('admin.includes.sidebar')
<main class="points-page">
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    <section class="hero">
        <div>
            <span class="eyebrow">نقاط الولاء</span>
            <h1>{{ __('نقاط العملاء') }}</h1>
            <p>
                <span class="state {{ $settings['enabled'] ? 'on' : 'off' }}">{{ $settings['enabled'] ? __('البرنامج مفعّل') : __('البرنامج موقوف') }}</span>
                {{ __('كل :amount ₪ = :points نقطة', ['amount' => $settings['amount'], 'points' => $settings['points']]) }}
            </p>
        </div>
        <a class="btn" href="{{ route('settings') }}#loyalty"><i class="ti ti-settings" aria-hidden="true"></i> {{ __('إعدادات البرنامج') }}</a>
    </section>

    <div class="layout">
        <section class="panel">
            <form class="search" method="get" action="{{ route('customer-points.index') }}" role="search">
                <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('ابحث بالاسم أو الإيميل أو الهاتف') }}" aria-label="{{ __('ابحث بالاسم أو الإيميل أو الهاتف') }}">
                <button class="btn btn-primary" type="submit"><i class="ti ti-search" aria-hidden="true"></i></button>
            </form>
            @if($customers->isEmpty())
                <div class="empty">{{ __('ما في عملاء مطابقين.') }}</div>
            @else
            <table>
                <thead><tr><th>{{ __('العميل') }}</th><th>{{ __('الرصيد المؤكد') }}</th><th class="hide-sm">{{ __('معلّقة') }}</th><th></th></tr></thead>
                <tbody>
                @foreach($customers as $customer)
                    <tr class="{{ $selected?->id === $customer->id ? 'is-selected' : '' }}">
                        <td><b>{{ $customer->name }}</b><small dir="ltr" style="text-align:right">{{ $customer->email }}</small></td>
                        <td class="num gold">{{ number_format((int) $customer->points_balance) }}</td>
                        <td class="num dim hide-sm">{{ number_format((int) $customer->points_pending) }}</td>
                        <td><a class="btn" href="{{ route('customer-points.index', ['customer' => $customer->id, 'search' => $search ?: null, 'page' => $customers->currentPage()]) }}#customer">{{ __('التفاصيل') }}</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if($customers->hasPages())
                <nav class="pagination" aria-label="{{ __('الصفحات') }}">
                    @if(! $customers->onFirstPage())<a href="{{ $customers->previousPageUrl() }}">{{ __('السابق') }}</a>@endif
                    <span>{{ $customers->currentPage() }} / {{ $customers->lastPage() }}</span>
                    @if($customers->hasMorePages())<a href="{{ $customers->nextPageUrl() }}">{{ __('التالي') }}</a>@endif
                </nav>
            @endif
            @endif
        </section>

        <section class="panel" id="customer">
            @if($selected)
                <h2>{{ $selected->name }}</h2>
                <div class="balance">
                    <div><b>{{ number_format($selectedBalance) }}</b><span>{{ __('الرصيد المؤكد') }}</span></div>
                    <div><b>{{ number_format($selectedPending) }}</b><span>{{ __('معلّقة') }}</span></div>
                </div>
                @if($canAdjust)
                    @if($errors->any())<ul class="errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
                    <form class="form" method="post" action="{{ route('customer-points.adjust', ['customer' => $selected->id, 'search' => $search ?: null]) }}">
                        @csrf
                        <label>{{ __('عدد النقاط') }}
                            <input type="number" name="points" required step="1" min="-100000" max="100000" inputmode="numeric" value="{{ old('points') }}" placeholder="50 / -20">
                            <small>{{ __('رقم موجب للإضافة، وسالب للخصم.') }}</small>
                        </label>
                        <label>{{ __('سبب التعديل') }} *
                            <textarea name="note" required minlength="3" maxlength="500" rows="2">{{ old('note') }}</textarea>
                        </label>
                        <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy" aria-hidden="true"></i> {{ __('حفظ التعديل') }}</button>
                    </form>
                @endif
                <div class="history">
                    @forelse($history as $entry)
                        <article>
                            <div>
                                <b>{{ $entry->typeLabel() }}</b>
                                <p>{{ $entry->created_at->format('Y-m-d H:i') }}@if($entry->shop) · {{ $entry->shop->name }}@endif @if($entry->order) · <span dir="ltr">{{ $entry->order->order_number }}</span>@endif</p>
                                @if($entry->note)<p>{{ $entry->note }}</p>@endif
                            </div>
                            <div style="text-align:end">
                                <span class="num {{ $entry->points >= 0 ? 'pos' : 'neg' }}" dir="ltr">{{ $entry->points > 0 ? '+' : '' }}{{ number_format($entry->points) }}</span><br>
                                <span class="badge {{ $entry->status }}">{{ $entry->statusLabel() }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="empty">{{ __('لا توجد حركات نقاط بعد.') }}</div>
                    @endforelse
                </div>
            @else
                <div class="empty">{{ __('اختر عميل من القائمة لعرض حركاته وتعديل نقاطه.') }}</div>
            @endif
        </section>
    </div>
</main>
</body>
</html>
