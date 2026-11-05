@extends('promoters.layout')
@section('title', 'مروّجين أوزمان | قصص ومحلات تستحق الاكتشاف')
@section('content')
<section class="page-intro">
    <div><h1>{{ __('اكتشف المروّجين') }}</h1><p>{{ __('ستوريات وفيديوهات من ناس بتحكي عن محلات أوزمان. تابع اللي بيعجبك وادخل المحل اللي لفت نظرك.') }}</p></div>
    @guest<a class="button outline small" href="{{ route('community.login', ['tab' => 'register', 'as' => 'promoter']) }}">{{ __('✨ صير مروّج') }}</a>@endguest
</section>
@if($recent->isNotEmpty())
<section class="hero-stories" aria-label="{{ __('آخر الحكايات') }}">
    @foreach($recent as $post)
        <a class="story-chip" href="{{ $post->profile->publicUrl() }}" aria-label="{{ __('حكاية من :name', ['name' => $post->profile->display_name]) }}">
            <span class="story-chip-ring"><img src="{{ route('promoters.media', ['post' => $post, 'part' => $post->type === 'image' ? 'media' : 'poster']) }}" alt="" loading="lazy">@if($post->type === 'video')<i aria-hidden="true">▷</i>@endif</span>
            <span class="story-chip-name">{{ $post->profile->display_name }}</span>
        </a>
    @endforeach
</section>
@endif
<div class="list-toolbar">
    <h2>{{ __('تعرّف على المروّجين') }} <span class="count">{{ $profiles->total() }}</span></h2>
    <div class="segmented" role="navigation" aria-label="{{ __('ترتيب المروّجين') }}"><a href="{{ route('promoters.index') }}" @class(['is-active' => $sort === 'reach']) @if($sort === 'reach') aria-current="page" @endif>{{ __('🔥 الأكثر وصول') }}</a><a href="{{ route('promoters.index', ['sort' => 'latest']) }}" @class(['is-active' => $sort === 'latest']) @if($sort === 'latest') aria-current="page" @endif>{{ __('الأحدث') }}</a></div>
</div>
<section class="creator-list">
    @forelse($profiles as $profile)
    @php($level = $profile->level())
    <a href="{{ $profile->publicUrl() }}" class="creator-row">
        <div class="avatar-ring">@include('promoters.avatar', ['profile' => $profile])</div>
        <div class="creator-row-body">
            <h3>{{ $profile->display_name }} <span class="level-badge level-{{ $level['key'] }}">{{ $level['icon'] }} {{ __($level['name']) }}</span></h3>
            <span class="handle" dir="ltr">{{ '@'.$profile->slug }}</span>
            <p>{{ \Illuminate\Support\Str::limit($profile->bio, 90) ?: __('اكتشف المحلات من خلال ستورياتي وفيديوهاتي.') }}</p>
            <div class="creator-stats"><span><b>{{ $profile->posts_count }}</b> {{ __('منشور') }}</span><span><b>{{ $profile->followerTotal() }}</b> {{ __('متابع') }}</span><span><b>{{ number_format($profile->page_views) }}</b> {{ __('زيارة') }}</span></div>
        </div>
        <span class="creator-arrow" aria-hidden="true">←</span>
    </a>
    @empty
    <div class="empty-state"><span class="empty-icon">✳</span><h2>الحكايات بتبدأ قريبًا</h2><p>صفحات المروّجين بتظهر هون أول ما تنضاف.</p>@if(auth()->user()?->isSuperAdmin())<a class="button" href="{{ route('promoter.accounts') }}">إضافة حساب مروّج</a>@endif</div>
    @endforelse
</section>
@include('promoters.pagination', ['items' => $profiles])
@endsection
