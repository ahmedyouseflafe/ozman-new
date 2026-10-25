@extends('promoters.layout')
@section('title', 'متابعة '.$profile->display_name)
@section('content')
<div class="section-heading page-heading"><div><h1>تابع {{ $profile->display_name }}</h1><p>ادخل بحسابك أو أنشئ حسابًا لحفظ متابعتك على أجهزتك.</p></div><a class="text-link" href="{{ $profile->publicUrl() }}">رجوع للصفحة</a></div>
<div class="studio-columns">
<section class="panel"><h2>عندي حساب</h2><form class="stack" method="post" action="{{ route('promoters.follow.login', $profile) }}">@csrf
<label>البريد الإلكتروني<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" dir="ltr" required></label>
<label>كلمة المرور<input type="password" name="password" autocomplete="current-password" required></label>
<button class="button" type="submit">دخول ومتابعة</button></form></section>
<section class="panel"><h2>حساب جديد</h2><form class="stack" method="post" action="{{ route('promoters.follow.register', $profile) }}">@csrf
<label>اسمك<input name="name" value="{{ old('name') }}" maxlength="100" autocomplete="name" required></label>
<label>البريد الإلكتروني<input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" dir="ltr" required></label>
<label>كلمة المرور<input type="password" name="password" minlength="8" autocomplete="new-password" required><small>8 أحرف على الأقل.</small></label>
<label>تأكيد كلمة المرور<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
<button class="button" type="submit">إنشاء حساب ومتابعة</button></form></section>
</div>
@endsection
