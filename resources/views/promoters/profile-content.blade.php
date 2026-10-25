<div class="social-profile">
<section class="profile-hero">
    <div class="profile-identity">
        @include('promoters.story-avatar', ['inlineComposer' => true])
        <div class="profile-copy">
            <div class="profile-topline"><span class="handle" dir="ltr">{{ '@'.$profile->slug }}</span>@if($canManage)<button class="icon-create" type="button" data-compose="post" aria-label="إضافة منشور">+</button>@endif</div>
            <h1>{{ $profile->display_name }}</h1>
            <div class="profile-stats">
                <div><strong>{{ $posts->total() }}</strong><span>منشور</span></div>
                <div><strong id="promoterFollowerCount">{{ $profile->followers_count }}</strong><span>متابع</span></div>
                <div><strong>{{ $shopCount }}</strong><span>محل مذكور</span></div>
            </div>
            <p class="bio">{{ $profile->bio ?: 'أشارككم محلات وتجارب تستحق الزيارة. شوفوا آخر ستورياتي وفيديوهاتي هنا.' }}</p>
            <div class="profile-actions">
                @if($canManage)<a class="button outline" href="{{ route('promoter.manage', $profile) }}">تعديل الملف الشخصي</a><button class="button" type="button" data-compose="post">إضافة منشور</button>@else @include('promoters.follow-button') @endif
                <button class="button outline" type="button" id="shareProfile">مشاركة الملف</button>
            </div>
            <p id="profileShareStatus" role="status"></p>
        </div>
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
</div>
