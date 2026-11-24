<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('شو بدوّروا الزباين') }}{{ $shop ? ' | '.$shop->name : '' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.demand.index')
</head>
<body>
@include('admin.includes.sidebar')
<main class="demand-page">
    <section class="hero">
        <div>
            <span class="eyebrow">{{ __('الطلب الضايع') }}</span>
            <h1>{{ __('شو بدوّروا الزباين وما لقوه') }}</h1>
            <p>{{ __('كل بحث بالتطبيق أو الموقع أو مع فُلّة ما طلعله نتيجة. بدون أسماء ولا أرقام، بس الكلمات.') }}</p>
        </div>
        <form class="filters" method="get">
            @if($shops->count() > 1 || auth()->user()->isSuperAdmin())
            <select name="shop_id" onchange="this.form.submit()" aria-label="{{ __('المحل') }}">
                @if(auth()->user()->isSuperAdmin())<option value="">{{ __('كل أوزمان') }}</option>@endif
                @foreach($shops as $option)<option value="{{ $option->id }}" @selected($shop?->id === $option->id)>{{ $option->name }}</option>@endforeach
            </select>
            @elseif($shop)<input type="hidden" name="shop_id" value="{{ $shop->id }}">@endif
            <div class="toggle" role="group" aria-label="{{ __('الفترة') }}">
                <button name="days" value="7" class="{{ $days === 7 ? 'on' : '' }}">{{ __('آخر 7 أيام') }}</button>
                <button name="days" value="30" class="{{ $days === 30 ? 'on' : '' }}">{{ __('آخر 30 يوم') }}</button>
            </div>
        </form>
    </section>

    @if($report)
        @foreach(['in_shop' => __('انبحث عنه بمحلك وما لقوه'), 'in_type' => __('انبحث عنه بأوزمان بنوع محلك وما لقاه حدا')] as $key => $title)
        <section class="panel">
            <h2>{{ $title }}</h2>
            @if($report[$key]->isEmpty())
                <p class="empty">{{ $key === 'in_shop' ? __('ولا بحث ضايع بمحلك بهالفترة 👌') : __('ما في بحث ضايع بنوع محلك بهالفترة.') }}</p>
            @else
                <div class="rows">
                @foreach($report[$key] as $row)
                    <div class="row">
                        <div class="term"><b>{{ $row['shown'] }}</b><small>{{ trans_choice('{1} بحث واحد|[2,*] :count مرات', $row['searches'], ['count' => $row['searches']]) }}</small></div>
                        <div class="bar"><span style="width: {{ min(100, round($row['searches'] / max(1, $report[$key]->max('searches')) * 100)) }}%"></span></div>
                        @if($row['added'])
                            <span class="done"><i class="ti ti-check"></i> {{ __('صار عندك') }}</span>
                        @else
                            <a class="btn" href="{{ route('products.create', ['shop_id' => $shop->id, 'name' => $row['shown']]) }}"><i class="ti ti-plus"></i> {{ __('ضيف منتج') }}</a>
                        @endif
                    </div>
                @endforeach
                </div>
            @endif
        </section>
        @endforeach
    @elseif($overall !== null)
        <section class="panel">
            <h2>{{ __('حسب نوع المحل') }}</h2>
            @if($byType->isEmpty())
                <p class="empty">{{ __('لسا ما في بحث ضايع.') }}</p>
            @else
                <div class="chips">@foreach($byType as $row)<span class="chip">{{ $types[$row['type']] ?? __('غير محدد') }} <b>{{ $row['searches'] }}</b></span>@endforeach</div>
            @endif
        </section>
        <section class="panel">
            <h2>{{ __('أكثر إشي انبحث عنه وما انلقى') }}</h2>
            @if($overall->isEmpty())
                <p class="empty">{{ __('لسا ما في بحث ضايع.') }}</p>
            @else
                <div class="rows">
                @foreach($overall as $row)
                    <div class="row">
                        <div class="term"><b>{{ $row['shown'] }}</b><small>{{ $types[$row['type']] ?? __('غير محدد') }}</small></div>
                        <div class="bar"><span style="width: {{ min(100, round($row['searches'] / max(1, $overall->max('searches')) * 100)) }}%"></span></div>
                        <span class="count">{{ $row['searches'] }}</span>
                    </div>
                @endforeach
                </div>
            @endif
        </section>
    @else
        <section class="panel"><p class="empty">{{ __('ما في محل مربوط بحسابك.') }}</p></section>
    @endif
</main>
</body>
</html>
