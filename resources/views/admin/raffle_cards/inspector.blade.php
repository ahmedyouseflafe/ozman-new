<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فحص بطاقة رابحة - Ozman</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.raffle_cards.inspector')
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
                        <div class="detail"><span>اسم الفائز</span><strong>{{ $winnerName ?: 'لم تُستخدم البطاقة بعد' }}</strong></div>
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
