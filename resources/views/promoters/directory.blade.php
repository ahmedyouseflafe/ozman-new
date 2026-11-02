@extends('promoters.layout')
@section('title', 'مروّجين أوزمان | قصص ومحلات تستحق الاكتشاف')
@section('content')
<section class="directory-hero"><span class="eyebrow">OZMAN CREATORS</span><h1>محلات جديدة.<br>حكايات بعيون مختلفة<span>.</span></h1><p>شاهد الستوري، اكتشف التفاصيل، وادخل المحل اللي لفت نظرك.</p>
@if($recent->isNotEmpty())
<div class="hero-stories" aria-label="{{ __('آخر الحكايات') }}">
    @foreach($recent as $post)
        <a class="hero-story" href="{{ $post->profile->publicUrl() }}" aria-label="{{ __('حكاية من :name', ['name' => $post->profile->display_name]) }}">
            <img src="{{ route('promoters.media', ['post' => $post, 'part' => $post->type === 'image' ? 'media' : 'poster']) }}" alt="" loading="lazy">
            @if($post->type === 'video')<span class="hero-story-play" aria-hidden="true">▷</span>@endif
            <span class="hero-story-name">{{ $post->profile->display_name }}</span>
        </a>
    @endforeach
</div>
@endif
</section>
<div class="section-heading"><h2>تعرّف على المروّجين</h2><span>{{ $profiles->total() }} صفحة</span></div>
<nav class="directory-sort" aria-label="{{ __('ترتيب المروّجين') }}"><a href="{{ route('promoters.index') }}" @class(['is-active' => $sort === 'reach']) @if($sort === 'reach') aria-current="page" @endif>{{ __('🔥 الأكثر وصول') }}</a><a href="{{ route('promoters.index', ['sort' => 'latest']) }}" @class(['is-active' => $sort === 'latest']) @if($sort === 'latest') aria-current="page" @endif>{{ __('الأحدث') }}</a></nav>
<section class="creator-grid">
    @forelse($profiles as $profile)
    <a href="{{ $profile->publicUrl() }}" class="creator-card">
        <div class="avatar-ring">@include('promoters.avatar', ['profile' => $profile])</div>
        @php($level = $profile->level())
        <h2>{{ $profile->display_name }}</h2><span class="level-badge level-{{ $level['key'] }}">{{ $level['icon'] }} {{ __($level['name']) }}</span><span class="handle" dir="ltr">{{ '@'.$profile->slug }}</span><p>{{ \Illuminate\Support\Str::limit($profile->bio, 120) ?: 'اكتشف المحلات من خلال ستورياتي وفيديوهاتي.' }}</p><div class="creator-stats"><span title="{{ __('منشور') }}">▦ <b>{{ $profile->posts_count }}</b> {{ __('منشور') }}</span><span title="{{ __('متابع') }}">👥 <b>{{ $profile->followerTotal() }}</b> {{ __('متابع') }}</span><span title="{{ __('زيارة') }}">👁 <b>{{ number_format($profile->page_views) }}</b> {{ __('زيارة') }}</span></div><span class="creator-arrow">←</span>
    </a>
    @empty
    <div class="empty-state"><span class="empty-icon">✳</span><h2>الحكايات بتبدأ قريبًا</h2><p>صفحات المروّجين ستظهر هنا بعد إضافتها من الإدارة.</p>@if(auth()->user()?->isSuperAdmin())<a class="button" href="{{ route('promoter.accounts') }}">إضافة حساب مروّج</a>@endif</div>
    @endforelse
</section>
@include('promoters.pagination', ['items' => $profiles])
@endsection
