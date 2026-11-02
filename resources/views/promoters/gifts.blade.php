@extends('promoters.layout')
@section('title', __('الهدايا والمستويات | أوزمان'))
@section('content')
<style>{!! file_get_contents(public_path('promoter-profile.css')) !!}</style>
<div class="section-heading page-heading"><div><span class="eyebrow">{{ __('إدارة أوزمان') }}</span><h1>{{ __('الهدايا والمستويات') }}</h1><p>{{ __('الزباين بيهدوا المروّجين من نقاط مشترياتهم، والهدايا بترفع مستوى الصفحة.') }}</p></div><a class="button outline" href="{{ route('promoter.accounts') }}">{{ __('حسابات المروّجات') }}</a></div>
<div class="studio-columns">
<section class="panel"><h2>{{ __('قائمة الهدايا') }}</h2>
<div class="stack">
@foreach($gifts as $gift)
<form method="post" action="{{ route('promoter.gifts.update', $gift) }}" class="gift-admin-row">@csrf @method('PATCH')
<label>{{ __('الرمز') }}<input name="icon" value="{{ $gift->icon }}" maxlength="16" required class="gift-admin-icon"></label>
<label>{{ __('الاسم') }}<input name="name" value="{{ $gift->name }}" maxlength="60" required></label>
<label>{{ __('النقاط') }}<input name="points" type="number" min="1" max="100000" value="{{ $gift->points }}" required></label>
<label>{{ __('الترتيب') }}<input name="sort_order" type="number" min="0" max="1000" value="{{ $gift->sort_order }}"></label>
<input type="hidden" name="is_active" value="0"><label class="check"><input type="checkbox" name="is_active" value="1" @checked($gift->is_active)> {{ __('ظاهرة') }}</label>
<button class="button small" type="submit">{{ __('حفظ') }}</button></form>
@endforeach
</div></section>
<aside class="stack">
<section class="panel"><h2>{{ __('هدية جديدة') }}</h2><form method="post" action="{{ route('promoter.gifts.store') }}" class="stack">@csrf
<label>{{ __('الرمز (إيموجي)') }}<input name="icon" maxlength="16" required placeholder="💐"></label>
<label>{{ __('الاسم') }}<input name="name" maxlength="60" required></label>
<label>{{ __('النقاط') }}<input name="points" type="number" min="1" max="100000" required></label>
<label>{{ __('الترتيب') }}<input name="sort_order" type="number" min="0" max="1000" value="10"></label>
<input type="hidden" name="is_active" value="1">
<button class="button" type="submit">{{ __('إضافة') }}</button></form></section>
<section class="panel"><h2>{{ __('المستويات') }}</h2><ul class="level-list">@foreach(\App\Services\PromoterLevel::LEVELS as $level)<li><span class="level-badge level-{{ $level['key'] }}">{{ $level['icon'] }} {{ __($level['name']) }}</span><span>{{ __('من :points نقطة وصول', ['points' => number_format($level['from'])]) }}</span></li>@endforeach</ul><small>{{ __('الوصول = نقاط الهدايا + 10 لكل متابع + زيارة لكل زائر باليوم.') }}</small></section>
<section class="panel"><h2>{{ __('الأكثر هدايا') }}</h2><ol class="level-list">@forelse($topProfiles as $top)<li><a href="{{ $top->publicUrl() }}">{{ $top->display_name }}</a><span>🎁 {{ number_format($top->gift_points) }} · {{ number_format($top->page_views) }} {{ __('زيارة') }}</span></li>@empty<li>{{ __('لسه ما في هدايا.') }}</li>@endforelse</ol></section>
</aside>
</div>
@endsection
