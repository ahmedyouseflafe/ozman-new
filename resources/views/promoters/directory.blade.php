@extends('promoters.layout')
@section('title', 'مروّجات أوزمان | قصص ومحلات تستحق الاكتشاف')
@section('content')
<section class="directory-hero"><span class="eyebrow">OZMAN CREATORS</span><h1>محلات جديدة.<br>حكايات بعيون مختلفة<span>.</span></h1><p>شاهد الستوري، اكتشف التفاصيل، وادخل المحل اللي لفت نظرك.</p></section>
<div class="section-heading"><h2>تعرّف على المروّجات</h2><span>{{ $profiles->total() }} صفحة</span></div>
<section class="creator-grid">
    @forelse($profiles as $profile)
    <a href="{{ $profile->publicUrl() }}" class="creator-card">
        <div class="avatar-ring">@include('promoters.avatar', ['profile' => $profile])</div>
        <h2>{{ $profile->display_name }}</h2><span class="handle" dir="ltr">{{ '@'.$profile->slug }}</span><p>{{ \Illuminate\Support\Str::limit($profile->bio, 120) ?: 'اكتشف المحلات من خلال ستورياتي وفيديوهاتي.' }}</p><span class="pill">{{ $profile->posts_count }} منشور</span><span class="pill">{{ $profile->followers_count }} متابع</span><span class="creator-arrow">←</span>
    </a>
    @empty
    <div class="empty-state"><span class="empty-icon">✳</span><h2>الحكايات بتبدأ قريبًا</h2><p>صفحات المروّجات ستظهر هنا بعد إضافتها من الإدارة.</p>@if(auth()->user()?->isSuperAdmin())<a class="button" href="{{ route('promoter.accounts') }}">إضافة حساب مروّجة</a>@endif</div>
    @endforelse
</section>
@include('promoters.pagination', ['items' => $profiles])
@endsection
