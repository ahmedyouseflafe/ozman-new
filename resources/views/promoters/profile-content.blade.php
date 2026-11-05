<div class="social-profile">
<section class="profile-hero profile-card">
    @php($level = $profile->level())
    <div class="profile-top">
        @include('promoters.story-avatar', ['inlineComposer' => true])
        <div class="profile-stats profile-main-stats">
            <div><strong>{{ $posts->total() }}</strong><span>{{ __('منشور') }}</span></div>
            <div><strong id="promoterFollowerCount">{{ $profile->followerTotal() }}</strong><span>{{ __('متابع') }}</span></div>
            <div><strong>{{ number_format($profile->page_views) }}</strong><span>{{ __('زيارة') }}</span></div>
        </div>
    </div>
    <div class="profile-copy">
        <h1>{{ $profile->display_name }} <span class="level-badge level-{{ $level['key'] }}" id="promoterLevelBadge" title="{{ __('مستوى الصفحة حسب الهدايا والمتابعين والزيارات') }}">{{ $level['icon'] }} {{ __($level['name']) }}</span></h1>
        <span class="handle" dir="ltr">{{ '@'.$profile->slug }}</span>
        <p class="bio">{{ $profile->bio ?: 'أشارككم محلات وتجارب تستحق الزيارة. شوفوا آخر ستورياتي وفيديوهاتي هنا.' }}</p>
        <div class="profile-chips">
            <span title="{{ __('نقاط الهدايا اللي وصلت الصفحة') }}">🎁 <strong id="promoterGiftPoints">{{ number_format($profile->gift_points) }}</strong> {{ __('نقاط هدايا') }}</span>
            <span title="{{ __('كم مرة الزوار فتحوا محل من منشورات هالصفحة') }}">🛍 <strong>{{ number_format($shopVisits) }}</strong> {{ __('زيارة للمحلات') }}</span>
            <span>🏪 <strong>{{ $shopCount }}</strong> {{ __('محل مذكور') }}</span>
        </div>
        <div class="profile-actions profile-action-row">
            @if($canManage)
                <button class="button" type="button" data-compose="post">{{ __('＋ منشور جديد') }}</button>
                <a class="button outline" href="{{ route('promoter.manage', $profile) }}">{{ __('لوحتي') }}</a>
                <button class="button outline icon-only" type="button" data-live-prepare title="{{ __('بث مباشر') }}" aria-label="{{ __('بث مباشر') }}">◉</button>
            @else
                @include('promoters.follow-button')
                @if($gifting['gifts']->isNotEmpty())<button class="button gift-open" type="button" data-gift-open>{{ __('🎁 ادعم بهدية') }}</button>@endif
            @endif
            <button class="button outline icon-only" type="button" id="shareProfile" title="{{ __('مشاركة الملف') }}" aria-label="{{ __('مشاركة الملف') }}">↗</button>
        </div>
        <p id="profileShareStatus" role="status"></p>
    </div>
</section>
<section class="stories-section" aria-label="الستوريات">
    <div class="story-strip">
        @if($canManage)<button class="story-bubble story-create" type="button" data-compose="story"><span class="story-ring"><span class="story-plus">+</span></span><span>إضافة ستوري</span></button>@endif
        @forelse($stories as $story)
            <button type="button" class="story-bubble" data-story="{{ $loop->index }}"><span class="story-ring">@if($story->type === 'image' || $story->poster)<img src="{{ route('promoters.media', ['post'=>$story, 'part'=>$story->type === 'image' ? 'media' : 'poster']) }}" alt="" loading="lazy">@else<span class="story-video">▷</span>@endif</span><span>{{ $story->shop?->is_active ? $story->shop->name : 'من يومي' }}</span></button>
        @empty
            <p class="quiet-empty">ما في ستوريات حاليًا.<small>الستوري بتظهر لمدة 24 ساعة.</small></p>
        @endforelse
    </div>
</section>
@if($featuredShops->isNotEmpty())
<section class="featured-shops" aria-labelledby="featuredShopsTitle">
    <h2 id="featuredShopsTitle">{{ __('المحلات اللي بحكي عنها') }}</h2>
    <div class="featured-shops-row">
        @foreach($featuredShops as $row)
            @php($shop = $row['post']->shop)
            <a class="featured-shop" href="{{ $row['post']->shopVisitUrl() }}">
                <span class="featured-shop-logo">@if($shop->logo)<img src="{{ str_starts_with($shop->logo, 'http') ? $shop->logo : asset($shop->logo) }}" alt="" loading="lazy">@else{{ mb_substr(preg_replace('/^ال(?=\S)/u', '', trim($shop->name)) ?: $shop->name, 0, 1) }}@endif</span>
                <span class="featured-shop-name">{{ $shop->name }}</span>
                <span class="featured-shop-meta">{{ trans_choice('{1} منشور واحد|[2,*] :count منشورات', $row['posts_count'], ['count' => $row['posts_count']]) }}</span>
                <span class="featured-shop-cta">{{ __('زور المحل ←') }}</span>
            </a>
        @endforeach
    </div>
</section>
@endif
@if($supporters->isNotEmpty())
<section class="top-supporters" aria-labelledby="topSupportersTitle">
    <h2 id="topSupportersTitle">{{ __('🏆 أكثر الداعمين') }}</h2>
    <ol>
        @foreach($supporters as $supporter)
            @continue(! $supporter->sender)
            <li><span class="supporter-rank">{{ $loop->iteration }}</span>@include('promoters.customer-avatar', ['user' => $supporter->sender, 'class' => 'is-small'])<span class="supporter-name">{{ \Illuminate\Support\Str::before(trim($supporter->sender->name).' ', ' ') }}</span><b>⭐ {{ number_format($supporter->total_points) }}</b></li>
        @endforeach
    </ol>
</section>
@endif
<nav class="profile-tabs" aria-label="محتوى الحساب">
    <button type="button" id="postsTab" data-profile-tab="posts" aria-controls="posts" aria-pressed="true">▦ <span>المنشورات</span></button>
    <button type="button" id="reelsTab" data-profile-tab="promoterScreen" aria-controls="promoterScreen" aria-pressed="false">▷ <span>الفيديوهات</span></button>
</nav>
<section id="posts" data-profile-panel aria-labelledby="postsTab">
    <div class="profile-media-grid">
    @forelse($posts as $post)
        <button class="profile-tile" type="button" data-post="{{ $loop->index }}" aria-label="فتح {{ $post->type === 'video' ? 'فيديو' : 'صورة' }}: {{ \Illuminate\Support\Str::limit($post->caption,80) }}">
            @if($post->type === 'image' || $post->poster)<img src="{{ route('promoters.media', ['post'=>$post,'part'=>$post->type === 'image' ? 'media':'poster']) }}" alt="{{ \Illuminate\Support\Str::limit($post->caption,120) }}" loading="lazy">@else<video src="{{ route('promoters.media', $post) }}#t=0.1" muted playsinline preload="metadata" aria-hidden="true"></video>@endif
            @if($post->type === 'video')<span class="tile-type" aria-hidden="true">▷</span>@endif
            <span class="tile-caption">{{ $post->caption ?: 'شاهد المنشور' }}</span>
        </button>
    @empty
        <div class="profile-empty"><span aria-hidden="true">▧</span><h2>لسّه ما في منشورات</h2><p>{{ $canManage ? 'شارك أول صورة أو فيديو مع متابعيك.' : 'الصور والفيديوهات الجديدة رح تظهر هون.' }}</p>@if($canManage)<button type="button" class="button" data-compose="post">شارك أول منشور</button>@endif</div>
    @endforelse
    </div>
    @include('promoters.pagination', ['items'=>$posts])
</section>
<section id="promoterScreen" data-profile-panel aria-labelledby="reelsTab" hidden>
    <div class="profile-media-grid reels-grid">
    @forelse($videos as $video)
        <button class="profile-tile" type="button" data-reel="{{ $loop->index }}" aria-label="فتح الفيديو: {{ \Illuminate\Support\Str::limit($video->caption,80) }}">
            @if($video->poster)<img src="{{ route('promoters.media', ['post'=>$video,'part'=>'poster']) }}" alt="" loading="lazy">@else<video src="{{ route('promoters.media', $video) }}#t=0.1" muted playsinline preload="none" aria-hidden="true"></video>@endif
            <span class="tile-type" aria-hidden="true">▷</span><span class="tile-caption">{{ $video->caption ?: 'شاهد الفيديو' }}</span>
        </button>
    @empty
        <div class="profile-empty"><span aria-hidden="true">▷</span><h2>لسّه ما في فيديوهات</h2><p>الفيديوهات المنشورة بتظهر هون.</p>@if($canManage)<button class="button" type="button" data-compose="video">إضافة فيديو</button>@endif</div>
    @endforelse
    </div>
</section>
@if($canManage) @include('promoters.composer') @endif
@if(! $canManage && $gifting['gifts']->isNotEmpty()) @include('promoters.gift-dialog') @endif
</div>
