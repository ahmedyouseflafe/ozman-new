@php
    $welcome = match ($locale) {
        'en' => [
            'eyebrow' => 'YOUR NEXT FAVORITE ROLL', 'title' => 'Welcome to Bankai',
            'intro' => 'Great sushi starts here. Add your details once and make your next order easier.',
            'name' => 'Your name', 'nameHint' => 'How should we call you?', 'phone' => 'WhatsApp number',
            'address' => 'Address', 'addressHint' => 'City, street and a nearby landmark',
            'location' => 'Your location', 'locate' => 'Locate me', 'locationHint' => 'For a smoother delivery to your door.',
            'locationLater' => 'I’ll set it when ordering', 'locating' => 'Finding your location…',
            'locationReady' => 'Location saved', 'locationError' => 'Could not locate you. Allow location access and try again, or set it when ordering.',
            'openMap' => 'View on map', 'locationDeferred' => 'You can continue. Set your location before a delivery order.',
            'push' => 'Stay in the loop', 'pushHint' => 'Enable notifications for offers from stores on Ozman.',
            'pushEnable' => 'Enable notifications', 'pushBusy' => 'Enabling…', 'pushReady' => 'Notifications enabled',
            'pushDenied' => 'Notifications are blocked. You can enable them in browser settings and try again.',
            'pushDismissed' => 'Permission was not granted. You can enable notifications later.',
            'pushError' => 'Could not enable notifications. Try again; you can still enter the menu.',
            'pushUnavailable' => 'Notifications are unavailable in this browser. On iPhone, try adding the site to your Home Screen.',
            'submit' => 'Save & explore the menu', 'privacy' => 'Your details stay on this device and are included when you place an order.',
            'phoneError' => 'Enter a valid mobile number, such as 0591234567.', 'required' => 'Please enter your name and address.',
            'storageError' => 'Saved for this visit. Your browser is not allowing details to be remembered on this device.',
            'continue' => 'Continue to menu', 'cancel' => 'Back to menu', 'locationChoice' => 'Locate yourself or choose to set the location when ordering.',
        ],
        'he' => [
            'eyebrow' => 'YOUR NEXT FAVORITE ROLL', 'title' => 'ברוכים הבאים לבנקאי',
            'intro' => 'סושי מעולה מתחיל כאן. מלאו פרטים פעם אחת להזמנה קלה יותר.',
            'name' => 'השם שלך', 'nameHint' => 'איך קוראים לך?', 'phone' => 'מספר WhatsApp',
            'address' => 'כתובת', 'addressHint' => 'עיר, רחוב ונקודת ציון קרובה',
            'location' => 'המיקום שלך', 'locate' => 'איתור המיקום שלי', 'locationHint' => 'כדי שהמשלוח יגיע עד אליך.',
            'locationLater' => 'אגדיר מיקום בזמן ההזמנה', 'locating' => 'מאתר את המיקום…',
            'locationReady' => 'המיקום נשמר', 'locationError' => 'לא הצלחנו לאתר אותך. אפשרו גישה למיקום ונסו שוב, או הגדירו אותו בזמן ההזמנה.',
            'openMap' => 'הצגה במפה', 'locationDeferred' => 'אפשר להמשיך. יש להגדיר מיקום לפני הזמנת משלוח.',
            'push' => 'נשארים מעודכנים', 'pushHint' => 'הפעילו התראות על מבצעים מחנויות ב-Ozman.',
            'pushEnable' => 'הפעלת התראות', 'pushBusy' => 'מפעיל…', 'pushReady' => 'ההתראות מופעלות',
            'pushDenied' => 'ההתראות חסומות. אפשר להפעיל אותן בהגדרות הדפדפן ולנסות שוב.',
            'pushDismissed' => 'לא ניתן אישור. אפשר להפעיל התראות בהמשך.',
            'pushError' => 'לא ניתן להפעיל התראות. נסו שוב; אפשר להמשיך לתפריט.',
            'pushUnavailable' => 'התראות אינן זמינות בדפדפן זה. באייפון נסו להוסיף את האתר למסך הבית.',
            'submit' => 'שמירה וכניסה לתפריט', 'privacy' => 'הפרטים נשמרים במכשיר הזה ונשלחים עם ביצוע ההזמנה.',
            'phoneError' => 'יש להזין מספר נייד תקין, למשל 0501234567.', 'required' => 'יש להזין שם וכתובת.',
            'storageError' => 'הפרטים נשמרו לביקור הזה. הדפדפן אינו מאפשר לשמור אותם במכשיר.',
            'continue' => 'המשך לתפריט', 'cancel' => 'חזרה לתפריט', 'locationChoice' => 'יש לאתר את המיקום או לבחור להגדיר אותו בזמן ההזמנה.',
        ],
        default => [
            'eyebrow' => 'YOUR NEXT FAVORITE ROLL', 'title' => 'أهلًا فيك في بانكاي',
            'intro' => 'رولك المفضّل بستناك. سجّل بياناتك مرة، وخلّي طلبك الجاي أسهل.',
            'name' => 'الاسم', 'nameHint' => 'شو بنحب نناديك؟', 'phone' => 'رقم الواتساب',
            'address' => 'العنوان', 'addressHint' => 'المدينة، الشارع وأقرب علامة مميزة',
            'location' => 'اللوكيشن', 'locate' => 'حدّد موقعي', 'locationHint' => 'عشان نوصل طلبك لعندك بسهولة.',
            'locationLater' => 'بحدّده وقت الطلب', 'locating' => 'جارٍ تحديد موقعك…',
            'locationReady' => 'تم تحديد موقعك', 'locationError' => 'ما قدرنا نحدّد موقعك. اسمح بالوصول للموقع وجرّب مرة ثانية، أو حدّده وقت الطلب.',
            'openMap' => 'شوف الموقع على الخريطة', 'locationDeferred' => 'بتقدر تكمل، وتحدّد اللوكيشن قبل إرسال طلب توصيل.',
            'push' => 'خليك أول من يعرف', 'pushHint' => 'فعّل الإشعارات لتصلك عروض المحلات على Ozman.',
            'pushEnable' => 'تفعيل الإشعارات', 'pushBusy' => 'جارٍ التفعيل…', 'pushReady' => 'الإشعارات مفعّلة',
            'pushDenied' => 'الإشعارات محظورة. فعّلها من إعدادات المتصفح وجرّب مرة ثانية.',
            'pushDismissed' => 'ما تم السماح بالإشعارات. بتقدر تفعّلها لاحقًا.',
            'pushError' => 'تعذّر تفعيل الإشعارات. جرّب مرة ثانية؛ بتقدر تكمل للمنيو.',
            'pushUnavailable' => 'الإشعارات غير متاحة بهالمتصفح. على آيفون جرّب إضافة الموقع للشاشة الرئيسية.',
            'submit' => 'احفظ بياناتي وافتح المنيو', 'privacy' => 'بياناتك بتنحفظ على هالجهاز، وبتنرسل للمطعم لما تعمل طلب.',
            'phoneError' => 'اكتب رقم جوال صحيح، مثل 0591234567.', 'required' => 'اكتب اسمك وعنوانك حتى نكمل.',
            'storageError' => 'حفظنا بياناتك لهالزيارة. المتصفح مش سامح نحفظها للمرة الجاي على الجهاز.',
            'continue' => 'كمّل للمنيو', 'cancel' => 'رجوع للمنيو', 'locationChoice' => 'حدّد موقعك أو اختار تحديده وقت الطلب.',
        ],
    };
@endphp
<style>
    .bankai-profile-edit{align-self:center;display:inline-flex;align-items:center;justify-content:center;gap:6px;margin-top:10px;padding:7px 14px;border:1px solid #25454c;border-radius:20px;background:#092027;color:#aeeef4;font:700 12px Cairo,Arial,sans-serif;cursor:pointer}
    #bankaiWelcome{box-sizing:border-box;width:min(890px,calc(100% - 28px));max-width:890px;max-height:calc(100dvh - 28px);margin:auto;padding:0;border:1px solid #1c606c;border-radius:28px;background:#081116;color:#edfaff;overflow:auto;box-shadow:0 30px 110px #000a;font-family:Cairo,Arial,sans-serif}
    #bankaiWelcome::backdrop{background:#01070cd9;backdrop-filter:blur(10px)}
    #bankaiWelcome *{box-sizing:border-box}
    #bankaiWelcome [hidden]{display:none!important}
    .bankai-welcome-layout{display:grid;grid-template-columns:.78fr 1.22fr;min-height:600px}
    .bankai-welcome-brand{position:relative;isolation:isolate;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:40px 26px;text-align:center;overflow:hidden;background:radial-gradient(ellipse at 50% 38%,#0a667658,transparent 65%),linear-gradient(155deg,#09242c,#071015)}
    .bankai-welcome-brand:before,.bankai-welcome-brand:after{content:'';position:absolute;z-index:-1;border:1px solid #13d4e51c;border-radius:50%;width:310px;height:310px;top:25px;left:calc(50% - 155px)}
    .bankai-welcome-brand:after{width:400px;height:400px;top:-20px;left:calc(50% - 200px)}
    .bankai-welcome-logo{width:148px;height:148px;object-fit:contain;border:1px solid #13c6d987;border-radius:30px;background:#050d12;box-shadow:0 0 45px #00cce226;margin:0 0 30px}
    .bankai-welcome-kicker{color:#12d9ef;font-size:9px;font-weight:900;letter-spacing:2px;margin:0 0 10px;direction:ltr}
    #bankaiWelcome h2{margin:0 0 12px;font-size:29px;line-height:1.5;font-weight:900;color:#f1fcff}
    .bankai-welcome-intro{margin:0;color:#9db5bf;font-size:13px;line-height:2;max-width:260px}
    .bankai-welcome-form{display:grid;gap:14px;margin:0;padding:28px;background:linear-gradient(135deg,#0d1920,#091217)}
    .bankai-welcome-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .bankai-welcome-field{display:grid;gap:6px;font-size:12px;font-weight:800;color:#d3e5eb}
    .bankai-welcome-field input{width:100%;min-width:0;height:46px;padding:10px 12px;border:1px solid #29414c;border-radius:12px;background:#071016;color:#f3fcff;font:500 12px Cairo,Arial,sans-serif;outline:none}
    .bankai-welcome-field input::placeholder{color:#7d98a4}
    #bankaiWelcome :focus-visible,.bankai-profile-edit:focus-visible{outline:2px solid #13d9ed;outline-offset:3px}
    .bankai-welcome-field input:focus{border-color:#13d9ed}
    .bankai-welcome-box{padding:13px;border:1px solid #253d46;border-radius:15px;background:#071219}
    .bankai-welcome-box-head{display:flex;align-items:center;gap:9px;margin-bottom:10px}
    .bankai-welcome-icon{display:grid;place-items:center;flex:none;width:34px;height:34px;background:#0b303a;border-radius:10px;color:#11d7eb;font-size:19px}
    .bankai-welcome-box strong{font-size:12px;display:block}
    .bankai-welcome-box p{margin:3px 0 0;color:#94aeb9;font-size:10px;line-height:1.8}
    .bankai-welcome-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    #bankaiWelcome button{font-family:inherit;cursor:pointer}
    .bankai-welcome-action{padding:9px 12px;border:1px solid #167785;border-radius:10px;background:#0a3039;color:#83f1fc;font-size:11px;font-weight:800}
    .bankai-welcome-link{padding:5px 0;border:0;background:none;color:#a1b8c3;font-size:10px;text-decoration:underline;text-underline-offset:4px}
    #bankaiWelcome .bankai-welcome-status{font-size:11px;line-height:1.7;color:#acc1ca;margin:7px 0 0}
    #bankaiWelcome .bankai-welcome-status[data-state=success]{color:#59dfb3}
    #bankaiWelcome .bankai-welcome-status[data-state=error]{color:#ffabba}
    .bankai-welcome-map{display:inline-block;margin-top:6px;color:#5ce6f6;font-size:11px;text-decoration:underline}
    .bankai-welcome-submit{display:flex;justify-content:center;align-items:center;gap:10px;min-height:50px;padding:12px;border:0;border-radius:13px;background:linear-gradient(105deg,#08d1e4,#28baf3);color:#002029;font-size:14px;font-weight:900;box-shadow:0 8px 25px #00cddd16}
    #bankaiWelcome button:disabled{cursor:wait;opacity:.6}
    .bankai-welcome-privacy{margin:0;color:#829ca8;font-size:10px;line-height:1.8;text-align:center}
    #bankaiWelcome .bankai-welcome-cancel{justify-self:center}
    @media(max-width:650px){#bankaiWelcome{width:calc(100% - 20px);max-height:calc(100dvh - 20px);border-radius:22px}.bankai-welcome-layout{grid-template-columns:1fr;min-height:0}.bankai-welcome-brand{padding:22px 20px 18px}.bankai-welcome-logo{width:70px;height:70px;border-radius:18px;margin-bottom:12px}.bankai-welcome-kicker{font-size:8px;margin-bottom:5px}#bankaiWelcome h2{font-size:23px;margin-bottom:5px}.bankai-welcome-intro{font-size:11px;max-width:310px}.bankai-welcome-form{padding:20px;gap:13px}.bankai-welcome-fields{grid-template-columns:1fr}.bankai-welcome-brand:before{top:-160px}.bankai-welcome-brand:after{top:-205px}}
</style>
<dialog id="bankaiWelcome" aria-labelledby="bankaiWelcomeTitle" aria-describedby="bankaiWelcomeIntro" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" data-storage-key="ozman.restaurant.{{ $shop->id }}.customer.v1">
    <div class="bankai-welcome-layout">
        <section class="bankai-welcome-brand">
            @if($shop->logo)<img class="bankai-welcome-logo" src="{{ asset($shop->logo) }}" alt="{{ $shop->name }}">@endif
            <p class="bankai-welcome-kicker">{{ $welcome['eyebrow'] }}</p>
            <h2 id="bankaiWelcomeTitle">{{ $welcome['title'] }}</h2>
            <p class="bankai-welcome-intro" id="bankaiWelcomeIntro">{{ $welcome['intro'] }}</p>
        </section>
        <form class="bankai-welcome-form" id="bankaiWelcomeForm">
            <div class="bankai-welcome-fields">
                <label class="bankai-welcome-field" for="bankaiName">{{ $welcome['name'] }}<input id="bankaiName" name="name" autocomplete="name" maxlength="120" placeholder="{{ $welcome['nameHint'] }}" required></label>
                <label class="bankai-welcome-field" for="bankaiWhatsapp">{{ $welcome['phone'] }}<input id="bankaiWhatsapp" name="whatsapp" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" maxlength="30" placeholder="05xxxxxxxx" required aria-describedby="bankaiWelcomeMessage"></label>
            </div>
            <label class="bankai-welcome-field" for="bankaiAddress">{{ $welcome['address'] }}<input id="bankaiAddress" name="address" autocomplete="street-address" maxlength="500" placeholder="{{ $welcome['addressHint'] }}" required></label>
            <section class="bankai-welcome-box">
                <div class="bankai-welcome-box-head"><span class="bankai-welcome-icon"><i class="ti ti-map-pin" aria-hidden="true"></i></span><div><strong>{{ $welcome['location'] }}</strong><p>{{ $welcome['locationHint'] }}</p></div></div>
                <div class="bankai-welcome-actions"><button class="bankai-welcome-action" id="bankaiLocate" type="button"><i class="ti ti-current-location" aria-hidden="true"></i> {{ $welcome['locate'] }}</button><button class="bankai-welcome-link" id="bankaiLocateLater" type="button">{{ $welcome['locationLater'] }}</button></div>
                <p class="bankai-welcome-status" id="bankaiLocationStatus" role="status" hidden></p>
                <a class="bankai-welcome-map" id="bankaiMap" target="_blank" rel="noopener noreferrer" hidden>{{ $welcome['openMap'] }}</a>
            </section>
            <section class="bankai-welcome-box">
                <div class="bankai-welcome-box-head"><span class="bankai-welcome-icon"><i class="ti ti-bell-ringing" aria-hidden="true"></i></span><div><strong>{{ $welcome['push'] }}</strong><p>{{ $welcome['pushHint'] }}</p></div></div>
                <button type="button" class="bankai-welcome-action" id="bankaiEnablePush">{{ $welcome['pushEnable'] }}</button>
                <p class="bankai-welcome-status" id="bankaiPushStatus" role="status" hidden></p>
            </section>
            <p class="bankai-welcome-status" id="bankaiWelcomeMessage" role="alert" hidden></p>
            <button class="bankai-welcome-submit" type="submit" id="bankaiWelcomeSave">{{ $welcome['submit'] }} <i class="ti ti-arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i></button>
            <p class="bankai-welcome-privacy">{{ $welcome['privacy'] }}</p>
            <button type="button" class="bankai-welcome-link bankai-welcome-cancel" id="bankaiWelcomeCancel" hidden>{{ $welcome['cancel'] }}</button>
        </form>
    </div>
</dialog>
<script>window.BANKAI_WELCOME_COPY = @json($welcome);</script>
<script>{!! file_get_contents(base_path('public/restaurant-customer-welcome.js')) !!}</script>
