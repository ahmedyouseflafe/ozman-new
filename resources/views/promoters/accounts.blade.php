@extends('promoters.layout')
@section('title', 'حسابات المروّجات | أوزمان')
@section('content')
<div class="section-heading page-heading"><div><span class="eyebrow">إدارة أوزمان</span><h1>حسابات المروّجات</h1><p>حساب مستقل وصفحة خاصة لكل مروّجة.</p></div><a class="button outline" href="{{ route('dashboard') }}">العودة للوحة التحكم</a></div>
<div class="studio-columns">
<section class="panel"><h2>مروّجة جديدة</h2><form method="post" action="{{ route('promoter.accounts.store') }}" class="stack">@csrf
<label>الاسم الظاهر<input name="display_name" maxlength="100" value="{{ old('display_name') }}" required autocomplete="name"></label>
<label>اسم الصفحة بالرابط<input name="slug" dir="ltr" pattern="[a-z0-9]+(-[a-z0-9]+)*" minlength="3" maxlength="80" value="{{ old('slug') }}" placeholder="sara-discoveries" required><small>أحرف إنجليزية صغيرة وأرقام وشرطة. مثال: /promoters/sara</small></label>
<label>بريد تسجيل الدخول<input name="email" type="email" dir="ltr" value="{{ old('email') }}" required autocomplete="off"></label>
<label>كلمة المرور<input name="password" type="password" minlength="10" maxlength="128" required autocomplete="new-password"><small>10 أحرف على الأقل. شارك بيانات الدخول مع صاحبة الحساب.</small></label>
<button class="button" type="submit">إنشاء حساب وصفحة ↗</button></form></section>
<section class="panel"><h2>الحسابات <span class="count">{{ $profiles->total() }}</span></h2><div class="stack">
@forelse($profiles as $profile)<article class="account-card"><div class="account-heading"><div class="avatar-ring">@include('promoters.avatar')</div><div><h3>{{ $profile->display_name }}</h3><span dir="ltr" class="handle">{{ $profile->user->email }}</span><p>{{ $profile->posts_count }} منشور · {{ $profile->is_active && $profile->user->is_active ? 'حساب مفعّل' : 'حساب معطّل' }}</p></div></div>
<div class="profile-actions"><a class="button small" href="{{ route('promoter.manage', $profile) }}">إدارة المحتوى</a>@if($profile->is_active && $profile->user->is_active)<a class="button outline small" href="{{ $profile->publicUrl() }}">فتح الصفحة ↗</a>@endif</div>
<details><summary>إعدادات الحساب</summary><form method="post" action="{{ route('promoter.accounts.update', $profile) }}" class="stack">@csrf @method('PATCH')<input type="hidden" name="is_active" value="0"><label class="check"><input type="checkbox" name="is_active" value="1" @checked($profile->is_active && $profile->user->is_active)> الحساب والصفحة مفعّلان</label><label>تغيير كلمة المرور (اختياري)<input type="password" name="password" minlength="10" maxlength="128" autocomplete="new-password"></label><button type="submit" class="button outline small">حفظ الإعدادات</button></form></details>
</article>@empty<p class="quiet-empty">ما في حسابات بعد. أنشئ أول حساب من النموذج.</p>@endforelse
</div>@include('promoters.pagination', ['items'=>$profiles])</section></div>
@endsection
