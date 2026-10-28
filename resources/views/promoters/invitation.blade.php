@extends('promoters.layout')
@section('title','دعوتك إلى Ozman Creators')
@section('content')
<section class="community-hero"><span class="eyebrow">مساحتك جاهزة</span><h1>أهلًا {{ $user->name }} ✨</h1><p>اختار كلمة مرورك، وبعدها ادخل وانشر أول حكاية.</p></section>
<section class="panel invitation-panel"><h2>تفعيل دخول المروّج</h2><p dir="ltr">{{ $user->email }}</p><form method="post" action="{{ route('promoter.invitation.accept',$token) }}" class="stack">@csrf
<label>كلمة المرور الجديدة<input name="password" type="password" minlength="10" maxlength="128" autocomplete="new-password" required></label>
<label>تأكيد كلمة المرور<input name="password_confirmation" type="password" minlength="10" maxlength="128" autocomplete="new-password" required></label>
<button class="button">حفظ كلمة المرور والمتابعة</button><small>الرابط صالح 3 أيام ولمرة واحدة. لا تشاركه مع غيرك.</small></form></section>
@endsection
