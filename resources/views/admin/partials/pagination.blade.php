@if ($paginator->hasPages())
    @php
        $pagerCopy = match (app()->getLocale()) {
            'en' => ['pages' => 'Pagination', 'previous' => 'Previous page', 'next' => 'Next page', 'results' => 'results'],
            'he' => ['pages' => 'ניווט בין עמודים', 'previous' => 'העמוד הקודם', 'next' => 'העמוד הבא', 'results' => 'תוצאות'],
            default => ['pages' => 'التنقل بين الصفحات', 'previous' => 'الصفحة السابقة', 'next' => 'الصفحة التالية', 'results' => 'نتيجة'],
        };
    @endphp
    @viewStyle('admin.partials.pagination')
    <nav class="oz-pagination" aria-label="{{ $pagerCopy['pages'] }}">
        <p class="oz-pagination-summary"><bdi>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }}</bdi> {{ $pagerCopy['results'] }}</p>
        <ul class="oz-pagination-pages">
            <li>@if ($paginator->onFirstPage())<span class="oz-page oz-page-arrow" aria-disabled="true" aria-label="{{ $pagerCopy['previous'] }}">‹</span>@else<a class="oz-page oz-page-arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ $pagerCopy['previous'] }}">‹</a>@endif</li>
            @foreach ($elements as $element)
                @if (is_string($element))<li><span class="oz-page-gap">{{ $element }}</span></li>@endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>@if ($page == $paginator->currentPage())<span class="oz-page" aria-current="page">{{ $page }}</span>@else<a class="oz-page" href="{{ $url }}">{{ $page }}</a>@endif</li>
                    @endforeach
                @endif
            @endforeach
            <li>@if ($paginator->hasMorePages())<a class="oz-page oz-page-arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ $pagerCopy['next'] }}">›</a>@else<span class="oz-page oz-page-arrow" aria-disabled="true" aria-label="{{ $pagerCopy['next'] }}">›</span>@endif</li>
        </ul>
    </nav>
@endif
