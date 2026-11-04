@extends('promoters.layout')
@section('title', ($registerFirst ?? false) ? 'إنشاء حساب • مجتمع أوزمان' : 'تسجيل الدخول • مجتمع أوزمان')
@section('content')
@auth
<section class="panel narrow-panel"><h2>أنت مسجّل باسم {{ auth()->user()->name }}</h2><a class="button" href="{{ route(auth()->user()->isSuperAdmin() || (auth()->user()->isPromoter() && ! auth()->user()->promoterProfile?->isPending()) ? 'promoter.dashboard' : 'community.account') }}">افتح حسابي</a><form method="post" action="{{ route('logout') }}">@csrf<button class="text-link">تسجيل الخروج لتبديل الحساب</button></form></section>
@else
{{-- One centred card like Instagram: log in by default, sign up with ?tab=register. --}}
<div class="auth-stack">
@if($registerFirst ?? false)
<section class="panel auth-card" id="register">
<div class="auth-brand" aria-hidden="true"><span class="brand-mark">O<span>·</span></span></div>
<h1 class="auth-title">{{ __('أنشئ حسابك') }}</h1>
<p class="auth-lead">{{ __('تابع المروّجين، اكتشف المحلات، واجمع نقاط على طلباتك.') }}</p>
<form method="post" action="{{ route('community.register') }}" class="stack" enctype="multipart/form-data" data-signup>@csrf
@php($type = old('account_type', request('as') === 'promoter' ? 'promoter' : 'customer'))
<fieldset class="account-type"><legend>{{ __('نوع الحساب') }}</legend>
<label><input type="radio" name="account_type" value="customer" @checked($type === 'customer')><span><b>{{ __('عميل') }}</b><small>{{ __('تابع وجمّع نقاط') }}</small></span></label>
<label><input type="radio" name="account_type" value="promoter" @checked($type === 'promoter')><span><b>{{ __('مروّج') }}</b><small>{{ __('انشر عن المحلات') }}</small></span></label>
</fieldset>
<p class="promoter-note" data-promoter-note @if($type !== 'promoter') hidden @endif>{{ __('حساب المروّج بيتفعّل بعد موافقة الإدارة. لحد ما ينقبل، حسابك بيشتغل كعميل.') }}</p>
@include('promoters.avatar-picker')
<label class="sr-label"><span class="visually-hidden">{{ __('البريد الإلكتروني') }}</span><input name="email" type="email" dir="ltr" autocomplete="email" value="{{ old('email') }}" maxlength="255" placeholder="{{ __('البريد الإلكتروني') }}" required autofocus></label>
<label class="sr-label"><span class="visually-hidden">{{ __('الاسم الكامل') }}</span><input name="name" autocomplete="name" maxlength="100" value="{{ old('name') }}" placeholder="{{ __('الاسم الكامل') }}" required></label>
<label class="sr-label"><span class="visually-hidden">{{ __('اسم المستخدم') }}</span><span class="username-field" dir="ltr"><span aria-hidden="true">@</span><input name="username" value="{{ old('username') }}" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" pattern="@?[a-zA-Z0-9._]{3,30}" placeholder="{{ __('اسم المستخدم') }}" required></span><small>{{ __('أحرف إنجليزية وأرقام و . و _ — للمروّج بيصير رابط صفحته.') }}</small></label>
<label class="sr-label"><span class="visually-hidden">{{ __('كلمة المرور') }}</span><input name="password" type="password" minlength="8" maxlength="128" autocomplete="new-password" placeholder="{{ __('كلمة المرور (8 أحرف على الأقل)') }}" required></label>
<button class="button">{{ __('التسجيل') }}</button>
<small class="auth-fine">{{ __('بياناتك خاصة. المروّجين بيشوفوا اسمك الأول بس.') }}</small>
</form></section>
<section class="panel auth-switch"><p>{{ __('عندك حساب؟') }} <a class="text-link" href="{{ route('community.login') }}">{{ __('سجّل دخول') }}</a></p></section>
@else
<section class="panel auth-card">
<div class="auth-brand" aria-hidden="true"><span class="brand-mark">O<span>·</span></span></div>
<h1 class="auth-title">Ozman <b>Community</b></h1>
<p class="auth-lead">{{ __('للعملاء والمروّجين. كل حساب بيفتح مساحته الخاصة.') }}</p>
<form method="post" action="{{ route('community.login.store') }}" class="stack">@csrf
<label class="sr-label"><span class="visually-hidden">{{ __('الإيميل أو اسم المستخدم') }}</span><input name="login" dir="ltr" autocomplete="username" autocapitalize="none" spellcheck="false" value="{{ old('login', old('email')) }}" maxlength="255" placeholder="{{ __('الإيميل أو اسم المستخدم') }}" required autofocus></label>
<label class="sr-label"><span class="visually-hidden">{{ __('كلمة المرور') }}</span><input name="password" type="password" autocomplete="current-password" placeholder="{{ __('كلمة المرور') }}" required></label>
<button class="button">{{ __('تسجيل الدخول') }}</button>
</form>
<a class="text-link auth-forgot" href="{{ route('community.password.request') }}">{{ __('نسيت كلمة المرور؟') }}</a>
</section>
<section class="panel auth-switch"><p>{{ __('ما عندك حساب؟') }} <a class="text-link" href="{{ route('community.login', ['tab' => 'register']) }}">{{ __('سجّل') }}</a></p>
<p><a class="text-link" href="{{ route('community.login', ['tab' => 'register', 'as' => 'promoter']) }}">{{ __('بدك تصير مروّج؟ قدّم طلبك') }}</a></p></section>
@endif
</div>
@endauth
@endsection
@section('scripts')
<script>document.querySelectorAll('[data-signup]').forEach(form=>{const note=form.querySelector('[data-promoter-note]');form.querySelectorAll('input[name=account_type]').forEach(r=>r.addEventListener('change',()=>{note.hidden=form.querySelector('input[name=account_type]:checked')?.value!=='promoter';}));});</script>
@endsection
