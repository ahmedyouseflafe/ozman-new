<section class="panel" id="changePassword"><h2>{{ __('كلمة المرور') }}</h2>
<details @if($errors->has('current_password') || $errors->has('password')) open @endif><summary>{{ __('تغيير كلمة المرور') }}</summary>
<form method="post" action="{{ route('community.password.change') }}" class="stack">@csrf @method('PUT')
<label>{{ __('كلمة المرور الحالية') }}<input name="current_password" type="password" maxlength="128" autocomplete="current-password" required></label>
<label>{{ __('كلمة المرور الجديدة') }}<input name="password" type="password" minlength="8" maxlength="128" autocomplete="new-password" required><small>{{ __('8 أحرف على الأقل.') }}</small></label>
<label>{{ __('تأكيد كلمة المرور الجديدة') }}<input name="password_confirmation" type="password" minlength="8" maxlength="128" autocomplete="new-password" required></label>
<button class="button outline" type="submit">{{ __('حفظ كلمة المرور') }}</button></form>
<small>{{ __('بعد التغيير، الأجهزة الثانية اللي محفوظ عليها الدخول بتطلب تسجيل دخول من جديد.') }}</small></details></section>
