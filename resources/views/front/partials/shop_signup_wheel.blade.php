@php
    $signupWheelCopy = match ($locale) {
        'en' => ['intro' => 'A welcome gift, just for you', 'hint' => 'Spin once. Your discount is applied automatically to your first order.', 'spin' => 'Spin the wheel', 'busy' => 'Spinning…', 'close' => 'Continue to menu', 'result' => 'Your gift:', 'saved' => 'Saved for your first order at this restaurant.', 'error' => 'Could not load your gift. Check your connection and try again.', 'retry' => 'Try again', 'open' => 'My welcome gift', 'discount' => 'Welcome discount', 'pending' => 'Spin your welcome wheel before sending this order.'],
        'he' => ['intro' => 'מתנת הצטרפות במיוחד בשבילך', 'hint' => 'סיבוב אחד. ההנחה תחול אוטומטית על ההזמנה הראשונה שלך.', 'spin' => 'סיבוב הגלגל', 'busy' => 'מסובב…', 'close' => 'המשך לתפריט', 'result' => 'המתנה שלך:', 'saved' => 'נשמר להזמנה הראשונה שלך במסעדה הזאת.', 'error' => 'לא ניתן לטעון את המתנה. בדקו את החיבור ונסו שוב.', 'retry' => 'ניסיון נוסף', 'open' => 'מתנת ההצטרפות שלי', 'discount' => 'הנחת הצטרפות', 'pending' => 'סובבו את גלגל ההצטרפות לפני שליחת ההזמנה.'],
        default => ['intro' => 'هدية ترحيب، مخصوص إلك', 'hint' => 'لفّة واحدة، وخصمك بننقص تلقائيًا من أول طلب إلك.', 'spin' => 'لف العجلة', 'busy' => 'العجلة بتلف…', 'close' => 'كمّل للمنيو', 'result' => 'هديتك:', 'saved' => 'حفظناها لأول طلب إلك من هالمطعم.', 'error' => 'ما قدرنا نحمّل هديتك. تأكد من النت وجرّب مرة ثانية.', 'retry' => 'جرّب مرة ثانية', 'open' => 'هدية تسجيلي', 'discount' => 'خصم التسجيل', 'pending' => 'لف عجلة التسجيل قبل إرسال الطلب لتحصل على خصمك.'],
    };
@endphp
@php
    if ($shop->catalog_type === 'cosmetics') {
        $signupWheelCopy['close'] = match($locale) {'en'=>'Continue shopping', 'he'=>'המשך לקניות', default=>'كمّل تسوّق'};
        $signupWheelCopy['saved'] = match($locale) {'en'=>'Saved for your first order at this store.', 'he'=>'נשמר להזמנה הראשונה בחנות הזאת.', default=>'حفظناها لأول طلب إلك من هالمحل.'};
    }
    $signupWheelCopy['hint'] = match ($locale) {
        'en' => 'Spin once. Your discount or gift is added automatically to your first order.',
        'he' => 'סיבוב אחד. ההנחה או המתנה תתווסף אוטומטית להזמנה הראשונה שלך.',
        default => 'لفّة واحدة، وخصمك أو هديتك بتنضاف تلقائيًا لأول طلب إلك.',
    };
    $signupWheelCopy['noPrize'] = match ($locale) {
        'en' => 'Better luck next time — no prize this time.',
        'he' => 'בהצלחה בפעם הבאה — הפעם אין פרס.',
        default => 'حظ أوفر — ما طلعت جائزة هالمرة.',
    };
    $signupWheelCopy = array_replace($signupWheelCopy, match ($locale) {
        'en' => ['error' => 'Could not load your gift. Please try again.', 'server' => 'The store server could not load the wheel. Please try again shortly.', 'session' => 'Your session expired. Reload the page and try again.', 'timeout' => 'Loading the wheel took too long. Please try again.', 'rateLimit' => 'Too many attempts. Wait a little and try again.'],
        'he' => ['error' => 'לא ניתן לטעון את המתנה. נסו שוב.', 'server' => 'שרת החנות לא הצליח לטעון את הגלגל. נסו שוב בקרוב.', 'session' => 'פג תוקף החיבור. רעננו את הדף ונסו שוב.', 'timeout' => 'טעינת הגלגל ארכה זמן רב מדי. נסו שוב.', 'rateLimit' => 'יותר מדי ניסיונות. המתינו מעט ונסו שוב.'],
        default => ['error' => 'تعذّر تحميل هديتك. جرّب مرة ثانية.', 'server' => 'صار خطأ بسيرفر المحل أثناء تحميل العجلة. جرّب بعد شوي.', 'session' => 'انتهت صلاحية الجلسة. حدّث الصفحة وجرّب مرة ثانية.', 'timeout' => 'تحميل العجلة أخذ وقت طويل. جرّب مرة ثانية.', 'rateLimit' => 'المحاولات كثيرة. استنى شوي وجرّب مرة ثانية.'],
    });
@endphp
@viewStyle('front.partials.shop_signup_wheel')
<button type="button" id="signupWheelReopen" hidden>{{ $signupWheelCopy['open'] }}</button>
<dialog id="shopSignupWheel" aria-labelledby="signupWheelTitle" data-preview="{{ !empty($signupPreview) ? '1' : '0' }}" data-url="{{ empty($signupPreview) ? route('restaurant.signup-reward', $shop) : '' }}" data-csrf-url="{{ route('csrf.refresh') }}">
    @if(!empty($signupPreview))<p style="color:#ffe29a;font-size:12px;margin-bottom:10px">معاينة تجريبية — لا تُحفظ جائزة ولا تُستهلك مرات ظهور.</p>@endif
    <p class="signup-wheel-kicker">{{ $shop->name }} · {{ $signupWheelCopy['intro'] }}</p>
    <h2 class="signup-wheel-title" id="signupWheelTitle"></h2>
    <p class="signup-wheel-hint">{{ $signupWheelCopy['hint'] }}</p>
    <div class="signup-wheel-wrap" id="signupWheelWrap"><div id="signupWheelDisc"></div></div>
    <p id="signupWheelResult" role="status" hidden></p>
    <img id="signupWheelGiftImage" alt="" hidden style="width:160px;height:160px;max-width:100%;object-fit:contain;border-radius:18px;background:#fff;padding:8px;margin:0 auto 14px">
    <p id="signupWheelError" role="alert" hidden></p>
    <button class="signup-wheel-button" type="button" id="signupWheelSpin">{{ $signupWheelCopy['spin'] }}</button>
    <button class="signup-wheel-button signup-wheel-secondary" type="button" id="signupWheelClose">{{ $signupWheelCopy['close'] }}</button>
</dialog>
@if(!empty($signupPreview))
    @php($signupWheelCopy['saved'] = 'نتيجة تجريبية فقط، لم تُضف إلى حسابك.')
    @php($signupWheelCopy['close'] = 'إغلاق المعاينة')
@endif
<script>window.OZMAN_SIGNUP_WHEEL_COPY = @json($signupWheelCopy);</script>
<script>{!! file_get_contents(base_path('public/shop-signup-wheel.js')) !!}</script>
