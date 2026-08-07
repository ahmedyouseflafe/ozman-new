<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فحص بطاقة رابحة - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        *{box-sizing:border-box} :root{--cyan:#00e5ff;--purple:#7000ff;--green:#25d366;--red:#ff5572;--line:rgba(255,255,255,.11);--muted:rgba(255,255,255,.62)}
        body{margin:0;min-height:100vh;background:radial-gradient(circle at 20% 10%,rgba(112,0,255,.18),transparent 34%),radial-gradient(circle at 75% 8%,rgba(0,229,255,.16),transparent 32%),#05070a;color:#fff;font-family:Cairo,sans-serif}
        .main{min-height:100vh;margin-right:245px}.content{padding:34px;max-width:1120px;margin:auto}.head{margin-bottom:22px}.kicker{color:var(--cyan);font-weight:900;font-size:13px}.head h1{font-size:36px;margin:6px 0;color:var(--cyan);text-shadow:0 0 22px rgba(0,229,255,.35)}.head p{color:var(--muted);font-weight:700}
        .panel{border:1px solid var(--line);border-radius:28px;padding:26px;background:linear-gradient(145deg,rgba(255,255,255,.075),rgba(255,255,255,.025));box-shadow:0 22px 60px rgba(0,0,0,.4);backdrop-filter:blur(18px)}
        .search{display:grid;grid-template-columns:1fr auto;gap:12px}.search input{min-height:58px;border:1px solid var(--line);border-radius:18px;background:rgba(0,0,0,.35);color:#fff;padding:0 20px;font:900 19px Cairo;direction:ltr;outline:none}.search input:focus{border-color:var(--cyan);box-shadow:0 0 20px rgba(0,229,255,.18)}
        .btn{min-height:58px;border:0;border-radius:18px;padding:0 28px;background:linear-gradient(135deg,var(--cyan),var(--purple));font:900 16px Cairo;cursor:pointer;color:#001014;box-shadow:0 0 24px rgba(0,229,255,.25)}.errors{margin-top:14px;color:#ff9aae;font-weight:800}
        .result{margin-top:22px;padding:24px;border:1px solid var(--line);border-radius:24px;background:rgba(0,0,0,.28)}.result.win{border-color:rgba(37,211,102,.42);box-shadow:inset 0 0 35px rgba(37,211,102,.06)}.result.lose{border-color:rgba(255,85,114,.35)}.result-title{display:flex;align-items:center;gap:10px;font-size:23px;font-weight:900}.win .result-title{color:#55ff99}.lose .result-title{color:#ff8298}
        .details{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:20px}.detail{padding:16px;border-radius:18px;background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.07)}.detail span{display:block;color:var(--muted);font-size:12px;font-weight:800}.detail strong{display:block;margin-top:8px;font-size:17px}.prize{display:flex;align-items:center;gap:16px;margin-top:20px}.prize img{width:82px;height:82px;object-fit:cover;border-radius:18px;border:1px solid rgba(0,229,255,.35)}
        @media(max-width:900px){.main{margin-right:0}.content{padding:22px 14px 100px}.head h1{font-size:28px}.search{grid-template-columns:1fr}.details{grid-template-columns:1fr}.btn{width:100%}}
    </style>
</head>
<body>
@include('admin.includes.sidebar')
<main class="main">
    @include('admin.includes.header', ['title' => 'فحص بطاقة رابحة'])
    <div class="content">
        <header class="head">
            <div class="kicker">OZMAN CARD INSPECTOR</div>
            <h1>فحص بطاقة رابحة</h1>
            <p>أدخل رقم البطاقة كاملاً لمعرفة النتيجة فقط. هذه الصفحة لا تسمح بعرض قائمة البطاقات أو تعديلها.</p>
        </header>

        <section class="panel">
            <form class="search" method="GET" action="{{ route('raffle-cards.inspector') }}">
                <input name="card_number" value="{{ old('card_number', $cardNumber) }}" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" aria-label="رقم البطاقة" required autofocus>
                <button class="btn" type="submit"><i class="ti ti-search"></i> فحص البطاقة</button>
            </form>
            @error('card_number')<div class="errors">{{ $message }}</div>@enderror

            @if($searched && $card)
                <div class="result win">
                    <div class="result-title"><i class="ti ti-circle-check"></i> البطاقة رابحة</div>
                    <div class="prize">
                        @if($card->prize_image)<img src="{{ asset($card->prize_image) }}" alt="{{ $card->prize_title }}">@endif
                        <strong>{{ $card->prize_title ?: 'جائزة رابحة' }}</strong>
                    </div>
                    <div class="details">
                        <div class="detail"><span>رقم البطاقة</span><strong dir="ltr">{{ $card->card_number }}</strong></div>
                        <div class="detail"><span>حالة البطاقة</span><strong>{{ $card->is_active ? 'نشطة' : 'غير نشطة' }}</strong></div>
                        <div class="detail"><span>حالة الاستخدام</span><strong>{{ $card->used_at ? 'مستخدمة سابقاً' : 'متاحة ولم تُستخدم' }}</strong></div>
                    </div>
                </div>
            @elseif($searched)
                <div class="result lose">
                    <div class="result-title"><i class="ti ti-circle-x"></i> الرقم ليس بطاقة رابحة مسجلة</div>
                    <div class="details"><div class="detail"><span>الرقم الذي تم فحصه</span><strong dir="ltr">{{ $cardNumber }}</strong></div></div>
                </div>
            @endif
        </section>
    </div>
</main>
</body>
</html>
