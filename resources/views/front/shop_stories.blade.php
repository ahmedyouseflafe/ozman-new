<link rel="stylesheet" href="{{ route('front.assets', ['file' => 'shop-stories.css']) }}?v={{ hash_file('sha256', base_path('public/shop-stories.css')) }}">
<section id="shopStories" data-feed="{{ route('shop-stories.feed') }}" hidden><h2>ستوريات المحلات</h2><div id="shopStoriesList"></div></section>
<dialog id="shopStoryViewer" aria-label="ستوريات المحل">
    <div id="shopStoryProgress" aria-hidden="true"></div>
    <header><strong id="shopStoryTitle"></strong><button type="button" id="shopStoryPause">إيقاف مؤقت</button><button type="button" id="shopStoryClose" aria-label="إغلاق">✕</button></header>
    <div id="shopStoryMedia"></div>
    <nav><button type="button" id="shopStoryPrevious">السابق</button><button type="button" id="shopStoryNext">التالي</button></nav>
    <footer><p id="shopStoryCaption"></p><a id="shopStoryVisit">زيارة المحل</a></footer>
</dialog>
<script defer src="{{ route('front.assets', ['file' => 'shop-stories.js']) }}?v={{ hash_file('sha256', base_path('public/shop-stories.js')) }}"></script>
