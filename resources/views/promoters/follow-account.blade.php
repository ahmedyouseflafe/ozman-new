@extends('promoters.layout')
@section('title', __('متابعة :name', ['name' => $profile->display_name]))
@section('content')
<div class="section-heading page-heading"><div><h1>{{ __('تابع :name', ['name' => $profile->display_name]) }}</h1><p>{{ __('ادخل بحسابك أو أنشئ حسابًا لحفظ متابعتك على أجهزتك.') }}</p></div><a class="text-link" href="{{ $profile->publicUrl() }}">{{ __('رجوع للصفحة') }}</a></div>
<div class="studio-columns">
<section class="panel"><h2>{{ __('عندي حساب') }}</h2><form class="stack" method="post" action="{{ route('promoters.follow.login', $profile) }}">@csrf
<label>{{ __('البريد الإلكتروني') }}<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" dir="ltr" required></label>
<label>{{ __('كلمة المرور') }}<input type="password" name="password" autocomplete="current-password" required></label>
<button class="button" type="submit">{{ __('دخول ومتابعة') }}</button></form></section>
<section class="panel"><h2>{{ __('حساب جديد') }}</h2><form class="stack" method="post" action="{{ route('promoters.follow.register', $profile) }}" enctype="multipart/form-data">@csrf
@include('promoters.avatar-picker')
<label>{{ __('اسمك') }}<input name="name" value="{{ old('name') }}" maxlength="100" autocomplete="name" required></label>
<label>{{ __('البريد الإلكتروني') }}<input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" dir="ltr" required></label>
<label>{{ __('كلمة المرور') }}<input type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" required><small>{{ __('8 أحرف على الأقل.') }}</small></label>
<label>{{ __('تأكيد كلمة المرور') }}<input type="password" name="password_confirmation" minlength="8" maxlength="128" autocomplete="new-password" required></label>
<button class="button" type="submit">{{ __('إنشاء حساب ومتابعة') }}</button></form></section>
</div>
@endsection
