@if ($paginator->hasPages())
    @php
        $pagerCopy = match (app()->getLocale()) {
            'en' => ['pages' => 'Pagination', 'previous' => 'Previous page', 'next' => 'Next page', 'results' => 'results'],
            'he' => ['pages' => 'ניווט בין עמודים', 'previous' => 'העמוד הקודם', 'next' => 'העמוד הבא', 'results' => 'תוצאות'],
            default => ['pages' => 'التنقل بين الصفحات', 'previous' => 'الصفحة السابقة', 'next' => 'الصفحة التالية', 'results' => 'نتيجة'],
        };
    @endphp
    <style>
        .oz-pagination{display:flex;flex-direction:column;align-items:center;gap:12px;padding:18px 8px;color:#c9c5d8;font-size:13px}
        .oz-pagination .oz-pagination-summary{margin:0;text-align:center}
        .oz-pagination .oz-pagination-pages{display:flex;justify-content:center;flex-wrap:wrap;gap:6px;direction:ltr;list-style:none;padding:0;margin:0;max-width:100%}
        .oz-pagination .oz-page{display:flex;align-items:center;justify-content:center;min-width:40px;min-height:44px;padding:6px 10px;border:1px solid #645379;border-radius:9px;background:#20172e;color:#eee8f7;text-decoration:none;font:inherit;line-height:1}
        .oz-pagination .oz-page[aria-current]{background:#00cce8;border-color:#00cce8;color:#06171c;font-weight:800}
        .oz-pagination .oz-page[aria-disabled]{opacity:.45}
        .oz-pagination a.oz-page:hover{border-color:#00d6ec;background:#30233f}
        .oz-pagination a.oz-page:focus-visible{outline:2px solid #00d6ec;outline-offset:3px}
        .oz-pagination .oz-page-arrow{font-size:25px}
        .oz-pagination .oz-page-gap{display:flex;align-items:center;min-height:44px;padding:0 4px}
        @media(max-width:480px){.oz-pagination .oz-pagination-pages{gap:4px}.oz-pagination .oz-page{min-width:36px;padding-inline:8px}}
    </style>
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
