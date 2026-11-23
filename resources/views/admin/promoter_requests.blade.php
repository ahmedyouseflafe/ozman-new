<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('طلبات المروّجين') }} - Ozman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.promoter_requests')
</head>
<body>
@include('admin.includes.sidebar')
<main class="req-page">
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="errors" role="alert">{{ $errors->first() }}</div>@endif
    <section class="hero">
        <div>
            <span class="eyebrow">CREATOR APPLICATIONS</span>
            <h1>{{ __('طلبات المروّجين') }} <span class="count">{{ $pending->count() }}</span></h1>
            <p>{{ __('ناس سجّلوا كمروّجين. صفحتهم مخفية لحد ما تقبلهم، وبيوصلهم إيميل بقرارك. الرفض بيخلّي حسابهم كعميل.') }}</p>
        </div>
        <a class="btn" href="{{ route('promoter.accounts') }}"><i class="ti ti-users" aria-hidden="true"></i> {{ __('كل حسابات المروّجين') }}</a>
    </section>

    <h2>{{ __('بانتظار قرارك') }}</h2>
    @if($pending->isEmpty())
        <section class="panel empty"><i class="ti ti-circle-check" aria-hidden="true"></i>{{ __('ما في طلبات جديدة. أول ما حدا يسجّل كمروّج بيطلع هون، وبيطلع رقم أحمر بالقائمة.') }}</section>
    @else
        <div class="requests">
            @foreach($pending as $application)
                @php($applicant = $application->user)
                <article class="req">
                    <div class="req-head">
                        <span class="avatar" style="background: {{ $applicant->avatarColor() }}">@if($applicant->avatarUrl())<img src="{{ $applicant->avatarUrl() }}" alt="">@else{{ $applicant->avatarInitial() }}@endif</span>
                        <div><h3>{{ $application->display_name }}</h3>@if($applicant->username)<span class="handle">{{ '@'.$applicant->username }}</span>@endif</div>
                    </div>
                    <dl class="facts">
                        <div><dt>{{ __('الإيميل') }}</dt><dd dir="ltr">{{ $applicant->email }}</dd></div>
                        @if($applicant->phone)<div><dt>{{ __('الجوال') }}</dt><dd><a dir="ltr" href="tel:{{ preg_replace('/[^0-9+]/', '', $applicant->phone) }}">{{ $applicant->phone }}</a></dd></div>@endif
                        <div><dt>{{ __('رابط صفحته') }}</dt><dd dir="ltr">/promoters/{{ $application->slug }}</dd></div>
                        <div><dt>{{ __('قدّم') }}</dt><dd>{{ $application->created_at->diffForHumans() }}</dd></div>
                    </dl>
                    @if($application->bio)<p class="bio">{{ $application->bio }}</p>@endif
                    <div class="actions">
                        <form method="post" action="{{ route('promoter.accounts.approve', $application) }}">@csrf<button class="btn btn-approve" type="submit"><i class="ti ti-check" aria-hidden="true"></i> {{ __('قبول وتفعيل') }}</button></form>
                        <form method="post" action="{{ route('promoter.accounts.reject', $application) }}" onsubmit="return confirm('{{ __('رفض الطلب؟ الحساب بيضل كعميل.') }}')">@csrf<button class="btn btn-reject" type="submit"><i class="ti ti-x" aria-hidden="true"></i> {{ __('رفض') }}</button></form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if($recent->isNotEmpty())
        <h2>{{ __('آخر المروّجين المفعّلين') }}</h2>
        <div class="recent">
            @foreach($recent as $profile)
                <a href="{{ route('promoter.manage', $profile) }}">
                    <span class="avatar" style="background: {{ $profile->user->avatarColor() }}">{{ $profile->user->avatarInitial() }}</span>
                    <span><b>{{ $profile->display_name }}</b><small dir="ltr">/promoters/{{ $profile->slug }}</small></span>
                    <span class="meta">{{ $profile->followerTotal() }} {{ __('متابع') }} · {{ $profile->posts_count }} {{ __('منشور') }} · {{ $profile->is_active ? __('مفعّل') : __('معطّل') }}</span>
                </a>
            @endforeach
        </div>
    @endif
</main>
</body>
</html>
