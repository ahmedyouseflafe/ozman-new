@extends('promoters.layout')
@section('title', __('نسيت كلمة المرور • مجتمع أوزمان'))
@section('content')
<section class="panel narrow-panel"><span class="eyebrow">{{ __('استعادة الدخول') }}</span><h1>{{ __('نسيت كلمة المرور؟') }}</h1>
<p>{{ __('اكتب بريد حسابك، وبنبعتلك رابط لتختار كلمة مرور جديدة. للعملاء والمروّجين.') }}</p>
<form method="post" action="{{ route('community.password.email') }}" class="stack">@csrf
<label>{{ __('البريد الإلكتروني') }}<input name="email" type="email" dir="ltr" autocomplete="email" value="{{ old('email') }}" maxlength="255" required autofocus></label>
<button class="button">{{ __('ابعتلي الرابط') }}</button></form>
<a class="text-link" href="{{ route('community.login') }}">{{ __('← رجوع لتسجيل الدخول') }}</a></section>
@endsection
