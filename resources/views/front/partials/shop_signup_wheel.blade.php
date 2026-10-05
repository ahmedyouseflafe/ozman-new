@php
    $signupWheelCopy = match ($locale) {
        'en' => ['intro' => 'A welcome gift, just for you', 'hint' => 'Spin once. Your discount is applied automatically to your first order.', 'spin' => 'Spin the wheel', 'busy' => 'Spinning…', 'close' => 'Continue to menu', 'result' => 'Your gift:', 'saved' => 'Saved for your first order at this restaurant.', 'error' => 'Could not load your gift. Check your connection and try again.', 'retry' => 'Try again', 'open' => 'My welcome gift', 'discount' => 'Welcome discount', 'pending' => 'Spin your welcome wheel before sending this order.'],
        'he' => ['intro' => 'מתנת הצטרפות במיוחד בשבילך', 'hint' => 'סיבוב אחד. ההנחה תחול אוטומטית על ההזמנה הראשונה שלך.', 'spin' => 'סיבוב הגלגל', 'busy' => 'מסובב…', 'close' => 'המשך לתפריט', 'result' => 'המתנה שלך:', 'saved' => 'נשמר להזמנה הראשונה שלך במסעדה הזאת.', 'error' => 'לא ניתן לטעון את המתנה. בדקו את החיבור ונסו שוב.', 'retry' => 'ניסיון נוסף', 'open' => 'מתנת ההצטרפות שלי', 'discount' => 'הנחת הצטרפות', 'pending' => 'סובבו את גלגל ההצטרפות לפני שליחת ההזמנה.'],
        default => ['intro' => 'هدية ترحيب، مخصوص إلك', 'hint' => 'لفّة واحدة، وخصمك بننقص تلقائيًا من أول طلب إلك.', 'spin' => 'لف العجلة', 'busy' => 'العجلة بتلف…', 'close' => 'كمّل للمنيو', 'result' => 'هديتك:', 'saved' => 'حفظناها لأول طلب إلك من هالمطعم.', 'error' => 'ما قدرنا نحمّل هديتك. تأكد من النت وجرّب مرة ثانية.', 'retry' => 'جرّب مرة ثانية', 'open' => 'هدية تسجيلي', 'discount' => 'خصم التسجيل', 'pending' => 'لف عجلة التسجيل قبل إرسال الطلب لتحصل على خصمك.'],
    };
@endphp
@php
    $signupWheelCopy['hint'] = match ($locale) {
        'en' => 'Spin once. Your discount or gift is added automatically to your first order.',
        'he' => 'סיבוב אחד. ההנחה או המתנה תתווסף אוטומטית להזמנה הראשונה שלך.',
        default => 'لفّة واحدة، وخصمك أو هديتك بتنضاف تلقائيًا لأول طلب إلك.',
    };
@endphp
<style>
    #shopSignupWheel{width:min(470px,calc(100% - 24px));max-height:calc(100dvh - 24px);padding:26px;border:1px solid #168797;border-radius:27px;background:radial-gradient(circle at 50% 20%,#094455,#08121a 65%);color:#effcff;text-align:center;font-family:Cairo,Arial,sans-serif;overflow:auto}
    #shopSignupWheel::backdrop{background:#000b;backdrop-filter:blur(9px)}#shopSignupWheel [hidden]{display:none!important}
    .signup-wheel-kicker{color:#0cd9ed;font-size:11px;font-weight:900;margin:0}.signup-wheel-title{font-size:24px;line-height:1.6;margin:8px 0}.signup-wheel-hint{color:#abc3ce;font-size:12px;line-height:1.8}
    .signup-wheel-wrap{position:relative;width:min(300px,100%);aspect-ratio:1;margin:22px auto}.signup-wheel-wrap:before{content:'';position:absolute;z-index:2;top:-9px;left:calc(50% - 12px);border-left:12px solid transparent;border-right:12px solid transparent;border-top:25px solid #f5fbff;filter:drop-shadow(0 3px 3px #000)}
    #signupWheelDisc{width:100%;height:100%;border-radius:50%;border:7px solid #d4f7fc;box-shadow:0 0 40px #04d6f020;transition:transform 3.6s cubic-bezier(.15,.75,.15,1);position:relative;direction:ltr}
    #signupWheelDisc:after{content:'✦';display:grid;place-items:center;position:absolute;inset:calc(50% - 28px);background:#09232e;color:#1ae0f5;border:4px solid #c7f5ff;border-radius:50%;font-size:22px}
    .signup-wheel-label{position:absolute;left:50%;top:50%;width:45%;font-size:10px;line-height:1.2;font-weight:900;color:#fff;text-shadow:0 1px 3px #000;transform-origin:left center;text-align:center;padding:0 6px 0 25px;overflow-wrap:anywhere}
    .signup-wheel-button{display:block;width:100%;min-height:46px;padding:11px;border:0;border-radius:12px;background:#08d6ed;color:#00232b;font:900 14px Cairo,Arial,sans-serif;cursor:pointer}.signup-wheel-button:disabled{opacity:.65;cursor:wait}.signup-wheel-secondary{margin-top:9px;background:#102833;color:#bbd9e4;border:1px solid #2a5869}
    #signupWheelResult{padding:14px;border:1px solid #29d6b37a;background:#0c342e;color:#81f4cf;border-radius:14px;margin:12px 0;font-size:17px;font-weight:900;line-height:1.8}#signupWheelError{color:#ffb0b0;font-size:12px;line-height:1.8}
    #signupWheelReopen{display:block;margin:12px auto;padding:9px 15px;border:1px solid #19b6c5;border-radius:18px;background:#0b2931;color:#60ebef;font:800 12px Cairo,Arial,sans-serif;cursor:pointer}#signupWheelReopen[hidden]{display:none}
    @media(prefers-reduced-motion:reduce){#signupWheelDisc{transition:none}}
</style>
<button type="button" id="signupWheelReopen" hidden>{{ $signupWheelCopy['open'] }}</button>
<dialog id="shopSignupWheel" aria-labelledby="signupWheelTitle" data-url="{{ route('restaurant.signup-reward', $shop) }}">
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
<script>window.OZMAN_SIGNUP_WHEEL_COPY = @json($signupWheelCopy);</script>
<script>{!! file_get_contents(base_path('public/shop-signup-wheel.js')) !!}</script>
