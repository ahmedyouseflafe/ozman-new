@extends('promoters.layout')
@section('title', $profile->display_name.' | مروّجات أوزمان')
@section('description', $profile->bio ?: 'ستوريات وفيديوهات '.$profile->display_name.' لاكتشاف محلات أوزمان.')
@section('content')
<section class="profile-hero">
    <div class="hero-decoration" aria-hidden="true">✳</div>
    <div class="profile-identity"><div class="avatar-ring large">@include('promoters.avatar')</div><div class="profile-copy"><span class="eyebrow">اكتشافات · تجارب · محلات</span><h1>{{ $profile->display_name }}</h1><div class="handle" dir="ltr">{{ '@'.$profile->slug }}</div><p class="bio">{{ $profile->bio ?: 'أشارككم محلات وتجارب تستحق الزيارة. شوفوا آخر ستورياتي وفيديوهاتي هنا.' }}</p>
    @include('promoters.follow-button')
    <div class="profile-actions"><a class="button" href="#promoterScreen">شاهد الفيديوهات <span aria-hidden="true">▷</span></a><button class="button outline" type="button" id="shareProfile">مشاركة الصفحة ↗</button>@if(auth()->user()?->isSuperAdmin() || (auth()->user()?->isPromoter() && auth()->id() === $profile->user_id))<a class="text-link" href="{{ route('promoter.manage', $profile) }}">إدارة صفحتي</a>@endif</div><p id="profileShareStatus" role="status"></p>
    </div></div>
    <div class="profile-stats"><div><strong id="promoterFollowerCount">{{ $profile->followers_count }}</strong><span>متابع</span></div><div><strong>{{ $posts->total() }}</strong><span>منشور</span></div><div><strong>{{ $stories->count() }}</strong><span>ستوري الآن</span></div><div><strong>{{ $shopCount }}</strong><span>محل مذكور</span></div></div>
</section>
<section class="stories-section" aria-label="الستوريات"><div class="section-heading"><div><span class="eyebrow">من يومي</span><h2>آخر الستوري</h2></div><span>لحظات تبقى 24 ساعة</span></div>
<div class="story-strip">
@forelse($stories as $story)<button type="button" class="story-bubble" data-story="{{ $loop->index }}"><span class="story-ring">@if($story->type === 'image' || $story->poster)<img src="{{ route('promoters.media', ['post'=>$story, 'part'=>$story->type === 'image' ? 'media' : 'poster']) }}" alt="" loading="lazy">@else<span class="story-video">▷</span>@endif</span><span>{{ $story->shop?->is_active ? $story->shop->name : 'من يومي' }}</span></button>
@empty<p class="quiet-empty">ما في ستوريات جديدة حاليًا. اكتشف المنشورات والفيديوهات بالأسفل.</p>@endforelse
</div></section>
<section class="screen-section" id="promoterScreen"><div class="section-heading"><div><span class="eyebrow">خُذ جولة معي</span><h2>على الشاشة</h2></div><span>{{ $videos->count() ? 'آخر الفيديوهات' : 'فيديوهات المروّجة' }}</span></div>
<div class="screen-grid"><div class="screen-stage">
@if($videos->isNotEmpty())<video id="promoterVideo" controls playsinline muted preload="metadata" @if($videos->first()->poster) poster="{{ route('promoters.media', ['post'=>$videos->first(), 'part'=>'poster']) }}" @endif src="{{ route('promoters.media', $videos->first()) }}"></video><span class="screen-label">OZMAN / ON SCREEN</span>
@else<div class="screen-empty"><span>▷</span><h3>مساحة للحكاية الجاية</h3><p>أول فيديو تنشره المروّجة رح يظهر هون.</p></div>@endif
</div><aside class="screen-info"><span class="eyebrow">الفيديو الحالي</span><h3 id="screenShop">{{ $videos->first()?->shop?->is_active ? $videos->first()->shop->name : 'من اختياراتي' }}</h3><p id="screenCaption">{{ $videos->first()?->caption ?: 'تفاصيل المحلات وتجاربها، من زاوية أقرب.' }}</p><a class="button" id="screenShopLink" @if(! $videos->first()?->shop?->is_active) hidden @else href="{{ $videos->first()->shop->publicUrl() }}" @endif>زور المحل ←</a><div class="playlist" aria-label="اختيار الفيديو">@foreach($videos as $video)<button type="button" data-video="{{ $loop->index }}" class="playlist-item {{ $loop->first ? 'active' : '' }}" aria-pressed="{{ $loop->first ? 'true':'false' }}"><span class="playlist-thumb">@if($video->poster)<img src="{{ route('promoters.media', ['post'=>$video,'part'=>'poster']) }}" alt="" loading="lazy">@else▷@endif</span><span><strong>{{ $video->shop?->is_active ? $video->shop->name : 'فيديو '.$loop->iteration }}</strong><small>{{ \Illuminate\Support\Str::limit($video->caption ?: 'شاهد الفيديو', 65) }}</small></span><b aria-hidden="true">↗</b></button>@endforeach</div><p id="screenStatus" role="status" class="media-status"></p></aside></div>
</section>
<section class="posts-section" id="posts"><div class="section-heading"><div><span class="eyebrow">مساحة الاكتشاف</span><h2>المنشورات</h2></div><span>صور وفيديوهات من المحلات</span></div><div class="post-grid">
@forelse($posts as $post)<article class="post-card"><button class="post-visual" type="button" data-post="{{ $loop->index }}" aria-label="فتح {{ $post->type === 'video' ? 'فيديو' : 'صورة' }}: {{ \Illuminate\Support\Str::limit($post->caption,80) }}">@if($post->type === 'image' || $post->poster)<img src="{{ route('promoters.media', ['post'=>$post,'part'=>$post->type === 'image' ? 'media':'poster']) }}" alt="{{ \Illuminate\Support\Str::limit($post->caption,120) }}" loading="lazy">@else<div class="video-placeholder"><span>▷</span><small>شاهد الحكاية</small></div>@endif<span class="media-badge">{{ $post->type === 'video' ? '▷ فيديو':'▧ صورة' }}</span></button><div class="post-body"><div class="post-meta">@if($post->shop?->is_active)<a href="{{ $post->shop->publicUrl() }}">{{ $post->shop->name }} ↗</a>@else<span>من يومي</span>@endif<time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->format('d / m') }}</time></div><p>{{ $post->caption ?: 'اكتشاف جديد من محلات أوزمان.' }}</p></div></article>
@empty<div class="empty-state"><span class="empty-icon">✳</span><h3>كل حكاية إلها بداية</h3><p>المنشورات الأولى ستظهر هنا قريبًا.</p></div>@endforelse
</div>@include('promoters.pagination', ['items'=>$posts])</section>
<dialog class="social-viewer" id="socialViewer" aria-label="عارض المنشورات والستوريات"><header><span>{{ $profile->display_name }}</span><button type="button" id="viewerClose" aria-label="إغلاق">×</button></header><div class="story-progress" id="storyProgress" hidden><span></span></div><div class="viewer-media" id="viewerMedia"></div><div class="viewer-nav"><button type="button" id="viewerPrevious" aria-label="السابق">→</button><span id="viewerCounter"></span><button type="button" id="viewerPause" hidden>إيقاف مؤقت</button><button type="button" id="viewerNext" aria-label="التالي">←</button></div><footer><p id="viewerCaption"></p><a id="viewerShop" class="button" hidden>زور المحل ←</a><p id="viewerStatus" role="status"></p></footer></dialog>
@endsection
@section('scripts')
@php
    $serialize = fn ($post) => ['src'=>route('promoters.media',$post),'type'=>$post->type,'poster'=>$post->poster ? route('promoters.media',['post'=>$post,'part'=>'poster']) : null,'caption'=>$post->caption,'shop'=>$post->shop?->is_active ? $post->shop->name : null,'url'=>$post->shop?->is_active ? $post->shop->publicUrl() : null];
    $pageData = ['title'=>$profile->display_name, 'url'=>$profile->publicUrl(), 'videos'=>$videos->map($serialize)->values(), 'stories'=>$stories->map($serialize)->values(), 'posts'=>collect($posts->items())->map($serialize)->values()];
@endphp
<script>window.PromoterPage=@json($pageData);</script>
<script>{!! file_get_contents(public_path('promoter-social.js')) !!}</script>
<script>{!! file_get_contents(public_path('promoter-follow.js')) !!}</script>
@endsection
