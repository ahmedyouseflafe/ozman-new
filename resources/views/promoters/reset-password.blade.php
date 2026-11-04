@extends('promoters.layout')
@section('title', __('كلمة مرور جديدة • مجتمع أوزمان'))
@section('content')
<section class="panel narrow-panel"><span class="eyebrow">{{ __('استعادة الدخول') }}</span><h1>{{ __('اختار كلمة مرور جديدة') }}</h1>
<form method="post" action="{{ route('community.password.store') }}" class="stack">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<label>{{ __('البريد الإلكتروني') }}<input name="email" type="email" dir="ltr" autocomplete="email" value="{{ old('email', $email) }}" maxlength="255" required></label>
<label>{{ __('كلمة المرور الجديدة') }}<input name="password" type="password" minlength="8" maxlength="128" autocomplete="new-password" required autofocus><small>{{ __('8 أحرف على الأقل.') }}</small></label>
<label>{{ __('تأكيد كلمة المرور') }}<input name="password_confirmation" type="password" minlength="8" maxlength="128" autocomplete="new-password" required></label>
<button class="button">{{ __('حفظ كلمة المرور') }}</button></form></section>
@endsection
