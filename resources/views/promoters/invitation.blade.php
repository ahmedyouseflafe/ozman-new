@extends('promoters.layout')
@section('title', __('دعوتك إلى Ozman Creators'))
@section('content')
<section class="community-hero"><span class="eyebrow">{{ __('مساحتك جاهزة') }}</span><h1>{{ __('أهلًا :name ✨', ['name' => $user->name]) }}</h1><p>{{ __('اختار كلمة مرورك، وبعدها ادخل وانشر أول حكاية.') }}</p></section>
<section class="panel invitation-panel"><h2>{{ __('تفعيل دخول المروّج') }}</h2><p dir="ltr">{{ $user->email }}</p><form method="post" action="{{ route('promoter.invitation.accept',$token) }}" class="stack">@csrf
<label>{{ __('كلمة المرور الجديدة') }}<input name="password" type="password" minlength="10" maxlength="128" autocomplete="new-password" required></label>
<label>{{ __('تأكيد كلمة المرور') }}<input name="password_confirmation" type="password" minlength="10" maxlength="128" autocomplete="new-password" required></label>
<button class="button">{{ __('حفظ كلمة المرور والمتابعة') }}</button><small>{{ __('الرابط صالح 3 أيام ولمرة واحدة. لا تشاركه مع غيرك.') }}</small></form></section>
@endsection
