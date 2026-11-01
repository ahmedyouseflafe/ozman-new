<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('نقاط العملاء') }} - Ozman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--bg:#071118;--panel:#0c1b24;--line:#1d3b49;--cyan:#16d3ee;--green:#55e9a0;--gold:#ffd166;--red:#ff9aae;--muted:#9eb4c2}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 85% 0,rgba(0,207,242,.12),transparent 28%),var(--bg);color:#f7fbff;font-family:Cairo,Arial,sans-serif}.points-page{width:min(1400px,calc(100% - 310px));margin:0 270px 0 auto;padding:28px 0 55px}.hero,.panel{border:1px solid var(--line);border-radius:24px;background:linear-gradient(145deg,rgba(15,37,48,.96),rgba(7,19,26,.98));box-shadow:0 16px 50px rgba(0,0,0,.2)}.hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:25px}.eyebrow{color:var(--cyan);font-size:11px;font-weight:900;letter-spacing:.08em}.hero h1{margin:5px 0;font-size:29px}.hero p{margin:0;color:var(--muted);font-size:12px}.state{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:900}.state.on{background:rgba(85,233,160,.12);color:var(--green)}.state.off{background:rgba(255,154,174,.12);color:var(--red)}.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:0 13px;border:1px solid #276377;border-radius:12px;background:#102b37;color:#e8fbff;font:inherit;font-size:11px;font-weight:900;text-decoration:none;cursor:pointer}.btn-primary{border:0;background:linear-gradient(135deg,#14d7ef,#358fff);color:#04202a}.layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,400px);gap:14px;margin-top:16px;align-items:start}.panel{padding:18px;min-width:0}.panel h2{margin:0 0 14px;font-size:18px}.search{display:flex;gap:8px;margin-bottom:14px}.search input,.form input,.form textarea{width:100%;min-height:40px;padding:8px 11px;border:1px solid var(--line);border-radius:10px;background:#07151d;color:#fff;font:inherit;font-size:12px}.form{display:grid;gap:11px}.form label{display:grid;gap:5px;color:var(--muted);font-size:11px;font-weight:700}.form small{color:var(--muted);font-size:10px;font-weight:400}.notice{margin-bottom:14px;padding:11px 13px;border:1px solid #4fcf9c;border-radius:12px;background:rgba(63,198,144,.1);color:#8affc5;font-size:12px}.errors{margin:0 0 12px;padding:11px 13px 11px 28px;border:1px solid #a0475a;border-radius:12px;background:rgba(160,71,90,.12);color:#ffb1c0;font-size:11px}table{width:100%;border-collapse:collapse;font-size:12px}th{color:var(--muted);font-size:11px;text-align:start;padding:8px;border-bottom:1px solid var(--line)}td{padding:10px 8px;border-bottom:1px solid rgba(29,59,73,.6);vertical-align:middle}tr.is-selected td{background:rgba(22,211,238,.07)}td small{display:block;color:var(--muted);overflow-wrap:anywhere}.num{font-weight:900;white-space:nowrap}.num.gold{color:var(--gold)}.num.dim{color:var(--muted)}.pos{color:var(--green)}.neg{color:var(--red)}.badge{display:inline-block;padding:1px 8px;border-radius:999px;background:#102b37;font-size:10px;font-weight:900;white-space:nowrap}.badge.confirmed{color:var(--green)}.badge.pending{color:var(--gold)}.badge.cancelled{color:var(--muted);text-decoration:line-through}.balance{display:flex;gap:10px;margin-bottom:14px}.balance div{flex:1;padding:12px;border:1px solid var(--line);border-radius:14px;background:#081720}.balance b{display:block;font-size:22px;color:var(--gold)}.balance span{color:var(--muted);font-size:11px}.history{display:grid;gap:7px;margin-top:16px}.history article{display:flex;justify-content:space-between;gap:10px;padding:10px;border:1px solid var(--line);border-radius:12px;background:#081720;font-size:11px}.history p{margin:2px 0 0;color:var(--muted);overflow-wrap:anywhere}.pagination{display:flex;justify-content:center;gap:12px;margin-top:14px;font-size:12px}.pagination a{color:var(--cyan)}.empty{padding:35px;text-align:center;color:var(--muted)}@media(max-width:1050px){.points-page{width:calc(100% - 28px);margin:auto;padding-top:78px}.layout{grid-template-columns:1fr}}@media(max-width:620px){.hero{align-items:flex-start;flex-direction:column;padding:18px}.hero h1{font-size:23px}.panel{padding:12px}.hide-sm{display:none}td,th{padding:8px 5px}}
    </style>
</head>
<body>
@include('admin.includes.sidebar')
<main class="points-page">
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    <section class="hero">
        <div>
            <span class="eyebrow">LOYALTY POINTS</span>
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
