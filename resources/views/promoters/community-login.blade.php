@extends('promoters.layout')
@section('title','أهلًا في مجتمع أوزمان')
@section('content')
<section class="community-hero"><span class="eyebrow">OZMAN COMMUNITY</span><h1>حكايات قريبة منك.<br>وحساب يجمع اللي بتحبّه.</h1><p>تابع المروّجين، اكتشف جديدهم، وخلّي إشعاراتك بمكان واحد.</p></section>
@auth
<section class="panel"><h2>أنت مسجّل باسم {{ auth()->user()->name }}</h2><a class="button" href="{{ route(auth()->user()->isPromoter() || auth()->user()->isSuperAdmin() ? 'promoter.dashboard' : 'community.account') }}">افتح حسابي</a><form method="post" action="{{ route('logout') }}">@csrf<button class="text-link">تسجيل الخروج لتبديل الحساب</button></form></section>
@else
<div class="studio-columns community-auth">
<section class="panel"><span class="eyebrow">أهلًا برجعتك</span><h2>تسجيل الدخول</h2><p>للعملاء والمروّجين. كل حساب بيفتح مساحته الخاصة.</p><form method="post" action="{{ route('community.login.store') }}" class="stack">@csrf
<label>البريد الإلكتروني<input name="email" type="email" dir="ltr" autocomplete="email" value="{{ old('email') }}" required></label>
<label>كلمة المرور<input name="password" type="password" autocomplete="current-password" required></label><button class="button">دخول لحسابي ←</button>
</form><small>مروّج جديد؟ افتح رابط الدعوة اللي أرسلته إلك الإدارة لتعيين كلمة مرورك.</small></section>
<section class="panel"><span class="eyebrow">ابدأ اكتشافك</span><h2>إنشاء حساب عميل</h2><p>ملفك للمتابعة والإشعارات، بدون صلاحيات نشر.</p><form method="post" action="{{ route('community.register') }}" class="stack">@csrf
<label>اسمك<input name="name" autocomplete="name" maxlength="100" value="{{ old('name') }}" required></label>
<label>البريد الإلكتروني<input name="email" type="email" dir="ltr" autocomplete="email" value="{{ old('email') }}" required></label>
<label>رقم الجوال (اختياري)<input name="phone" type="tel" dir="ltr" autocomplete="tel" maxlength="30" value="{{ old('phone') }}"></label>
<label>كلمة المرور<input name="password" type="password" minlength="10" maxlength="128" autocomplete="new-password" required><small>10 أحرف على الأقل.</small></label>
<label>تأكيد كلمة المرور<input name="password_confirmation" type="password" minlength="10" maxlength="128" autocomplete="new-password" required></label>
<button class="button">إنشاء حسابي</button></form></section></div>
@endauth
@endsection
