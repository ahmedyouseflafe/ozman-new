<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('تقييمات :shop', ['shop' => $shop->name]) }}</title>
    <meta name="description" content="{{ $summary['count'] ? __(':average من 5 · :count تقييم', ['average' => number_format($summary['average'], 1), 'count' => $summary['count']]) : __('لسا ما في تقييمات') }}">
    @viewStyle('front.shop_reviews')
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
