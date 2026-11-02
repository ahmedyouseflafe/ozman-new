@extends('promoters.layout')
@section('title', 'مروّجات أوزمان | قصص ومحلات تستحق الاكتشاف')
@section('content')
<section class="directory-hero"><span class="eyebrow">OZMAN CREATORS</span><h1>محلات جديدة.<br>حكايات بعيون مختلفة<span>.</span></h1><p>شاهد الستوري، اكتشف التفاصيل، وادخل المحل اللي لفت نظرك.</p></section>
<div class="section-heading"><h2>تعرّف على المروّجات</h2><span>{{ $profiles->total() }} صفحة</span></div>
<nav class="directory-sort" aria-label="{{ __('ترتيب المروّجين') }}"><a href="{{ route('promoters.index') }}" @class(['is-active' => $sort === 'reach']) @if($sort === 'reach') aria-current="page" @endif>{{ __('🔥 الأكثر وصول') }}</a><a href="{{ route('promoters.index', ['sort' => 'latest']) }}" @class(['is-active' => $sort === 'latest']) @if($sort === 'latest') aria-current="page" @endif>{{ __('الأحدث') }}</a></nav>
<section class="creator-grid">
    @forelse($profiles as $profile)
    <a href="{{ $profile->publicUrl() }}" class="creator-card">
        <div class="avatar-ring">@include('promoters.avatar', ['profile' => $profile])</div>
        @php($level = $profile->level())
        <h2>{{ $profile->display_name }}</h2><span class="level-badge level-{{ $level['key'] }}">{{ $level['icon'] }} {{ __($level['name']) }}</span><span class="handle" dir="ltr">{{ '@'.$profile->slug }}</span><p>{{ \Illuminate\Support\Str::limit($profile->bio, 120) ?: 'اكتشف المحلات من خلال ستورياتي وفيديوهاتي.' }}</p><span class="pill">{{ $profile->posts_count }} منشور</span><span class="pill">{{ $profile->followerTotal() }} متابع</span><span class="pill">{{ number_format($profile->page_views) }} {{ __('زيارة') }}</span><span class="creator-arrow">←</span>
    </a>
    @empty
    <div class="empty-state"><span class="empty-icon">✳</span><h2>الحكايات بتبدأ قريبًا</h2><p>صفحات المروّجات ستظهر هنا بعد إضافتها من الإدارة.</p>@if(auth()->user()?->isSuperAdmin())<a class="button" href="{{ route('promoter.accounts') }}">إضافة حساب مروّجة</a>@endif</div>
    @endforelse
</section>
@include('promoters.pagination', ['items' => $profiles])
@endsection
