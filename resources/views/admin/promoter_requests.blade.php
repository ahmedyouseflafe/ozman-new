<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('طلبات المروّجين') }} - Ozman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--bg:#071118;--panel:#0c1b24;--line:#1d3b49;--cyan:#16d3ee;--green:#55e9a0;--gold:#ffd166;--red:#ff9aae;--muted:#9eb4c2}*{box-sizing:border-box}
        body{margin:0;background:radial-gradient(circle at 85% 0,rgba(0,207,242,.12),transparent 28%),var(--bg);color:#f7fbff;font-family:Cairo,Arial,sans-serif}
        .req-page{width:min(1200px,calc(100% - 310px));margin:0 270px 0 auto;padding:28px 0 55px}
        .hero,.panel{border:1px solid var(--line);border-radius:24px;background:linear-gradient(145deg,rgba(15,37,48,.96),rgba(7,19,26,.98));box-shadow:0 16px 50px rgba(0,0,0,.2)}
        .hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:25px;flex-wrap:wrap}.eyebrow{color:var(--cyan);font-size:11px;font-weight:900;letter-spacing:.08em}.hero h1{margin:5px 0;font-size:29px;display:flex;align-items:center;gap:12px}.hero p{margin:0;color:var(--muted);font-size:13px}
        .count{display:inline-grid;place-items:center;min-width:36px;height:36px;padding:0 10px;border-radius:18px;background:rgba(255,209,102,.15);color:var(--gold);font-size:18px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 16px;border:1px solid #276377;border-radius:12px;background:#102b37;color:#e8fbff;font:inherit;font-size:13px;font-weight:900;text-decoration:none;cursor:pointer}
        .btn-approve{border:0;background:linear-gradient(135deg,#3fe0a0,#16b5d6);color:#04202a}.btn-reject{border-color:#7a3446;background:rgba(160,71,90,.15);color:#ffb1c0}
        .notice{margin-bottom:14px;padding:12px 14px;border:1px solid #4fcf9c;border-radius:12px;background:rgba(63,198,144,.1);color:#8affc5;font-size:13px}.errors{margin-bottom:14px;padding:12px 14px;border:1px solid #a0475a;border-radius:12px;background:rgba(160,71,90,.12);color:#ffb1c0;font-size:13px}
        h2{margin:26px 0 12px;font-size:19px}
        .requests{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:14px}
        .req{display:grid;gap:14px;padding:18px;border:1px solid #6a5420;border-radius:20px;background:linear-gradient(145deg,rgba(56,44,14,.35),rgba(12,27,36,.95))}
        .req-head{display:flex;align-items:center;gap:14px}.avatar{width:58px;height:58px;border-radius:50%;display:grid;place-items:center;font-size:24px;font-weight:900;color:#fff;flex-shrink:0;overflow:hidden;border:2px solid #e0b85a}.avatar img{width:100%;height:100%;object-fit:cover}
        .req-head h3{margin:0;font-size:18px}.handle{color:var(--cyan);font-size:13px;direction:ltr;display:inline-block}
        .facts{display:grid;gap:6px;margin:0;font-size:13px}.facts div{display:flex;gap:8px}.facts dt{color:var(--muted);flex:0 0 80px}.facts dd{margin:0;overflow-wrap:anywhere}.facts a{color:var(--cyan)}
        .bio{margin:0;padding:10px 12px;border-radius:12px;background:#081720;color:#cfe3ee;font-size:13px;white-space:pre-line}
        .actions{display:flex;gap:10px}.actions form{flex:1}.actions .btn{width:100%}
        .empty{padding:40px 20px;text-align:center;color:var(--muted)}.empty i{font-size:44px;color:var(--green);display:block;margin-bottom:8px}
        .recent{display:grid;gap:8px}.recent a{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid var(--line);border-radius:14px;background:#081720;color:inherit;text-decoration:none}.recent a:hover{border-color:var(--cyan)}.recent .avatar{width:40px;height:40px;font-size:16px;border-color:var(--line)}.recent b{display:block}.recent small{color:var(--muted)}.recent .meta{margin-inline-start:auto;color:var(--muted);font-size:12px;white-space:nowrap}
        @media(max-width:1050px){.req-page{width:calc(100% - 28px);margin:auto;padding-top:78px}}
        @media(max-width:620px){.hero{padding:18px}.hero h1{font-size:23px}.requests{grid-template-columns:1fr}.recent .meta{display:none}}
    </style>
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
