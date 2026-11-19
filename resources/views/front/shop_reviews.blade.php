<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('تقييمات :shop', ['shop' => $shop->name]) }}</title>
    <meta name="description" content="{{ $summary['count'] ? __(':average من 5 · :count تقييم', ['average' => number_format($summary['average'], 1), 'count' => $summary['count']]) : __('لسا ما في تقييمات') }}">
    <style>
        :root { --bg: #0b0f1a; --card: #141a2b; --line: #263049; --text: #eef2ff; --muted: #9aa6c4; --accent: #08def4; --star: #ffc542; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: Cairo, system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 18px 16px 48px; }
        .top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .top h1 { margin: 0; font-size: 20px; }
        .back { color: var(--accent); text-decoration: none; font-weight: 800; white-space: nowrap; }
        .panel { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 18px; }
        .summary { display: flex; gap: 22px; align-items: center; }
        .big { text-align: center; min-width: 110px; }
        .big strong { display: block; font-size: 44px; line-height: 1; color: var(--star); }
        .stars { color: var(--star); letter-spacing: 2px; font-size: 16px; }
        .stars .off { color: #3a4560; }
        .muted { color: var(--muted); font-size: 13px; }
        .bars { flex: 1; display: grid; gap: 6px; }
        .bar { display: grid; grid-template-columns: 22px 1fr 34px; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); }
        .bar span:first-child { color: var(--star); }
        .track { height: 8px; border-radius: 6px; background: #1f2740; overflow: hidden; }
        .fill { height: 100%; background: var(--star); border-radius: 6px; }
        .note { margin: 12px 0 18px; color: var(--muted); font-size: 12.5px; text-align: center; }
        .review { margin-top: 10px; }
        .review header { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .review b { font-size: 15px; }
        .review p { margin: 8px 0 0; line-height: 1.75; white-space: pre-line; }
        .reply { margin-top: 10px; padding: 10px 12px; border-radius: 12px; background: #1b2236; color: var(--muted); font-size: 14px; }
        .empty { text-align: center; padding: 40px 10px; color: var(--muted); }
        .more { display: block; margin: 18px auto 0; text-align: center; color: var(--accent); font-weight: 800; text-decoration: none; }
        @media (max-width: 480px) { .summary { flex-direction: column; align-items: stretch; } }
    </style>
</head>
<body>
@php($starLine = fn (float $value) => str_repeat('★', $full = min(5, (int) floor($value + 0.25))).'<span class="off">'.str_repeat('★', 5 - $full).'</span>')
<main class="wrap">
    <div class="top">
        <h1>{{ __('تقييمات :shop', ['shop' => $shop->name]) }}</h1>
        <a class="back" href="{{ $shop->publicUrl() }}">{{ __('ارجع للمحل') }}</a>
    </div>

    <section class="panel summary" aria-label="{{ __('ملخص التقييمات') }}">
        <div class="big">
            <strong>{{ $summary['count'] ? number_format($summary['average'], 1) : '–' }}</strong>
            <div class="stars" aria-hidden="true">{!! $starLine((float) ($summary['average'] ?? 0)) !!}</div>
            <div class="muted">{{ __(':count تقييم', ['count' => $summary['count']]) }}</div>
        </div>
        <div class="bars">
            @foreach ($stars as $star => $count)
                <div class="bar">
                    <span>{{ $star }}★</span>
                    <div class="track"><div class="fill" style="width: {{ $summary['count'] ? round($count / $summary['count'] * 100) : 0 }}%"></div></div>
                    <span>{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </section>
    <p class="note">{{ __('كل التقييمات من زباين طلبوا فعلاً من المحل عن طريق تطبيق أوزمان.') }}</p>

    @forelse ($reviews as $review)
        <article class="panel review">
            <header>
                <b>{{ $review->publicName() }}</b>
                <span class="stars" aria-label="{{ __(':rating من 5', ['rating' => $review->rating]) }}">{!! $starLine((float) $review->rating) !!}</span>
            </header>
            <div class="muted">{{ $review->created_at?->timezone('Asia/Hebron')->format('d/m/Y') }}</div>
            @if (filled($review->comment))
                <p>{{ $review->comment }}</p>
            @endif
            @if (filled($review->reply))
                <div class="reply">{{ __('رد المحل: :reply', ['reply' => $review->reply]) }}</div>
            @endif
        </article>
    @empty
        <div class="empty">{{ __('لسا ما في تقييمات') }}</div>
    @endforelse

    @if ($reviews->hasMorePages())
        <a class="more" href="{{ $reviews->nextPageUrl() }}">{{ __('عرض المزيد') }}</a>
    @endif
</main>
</body>
</html>
