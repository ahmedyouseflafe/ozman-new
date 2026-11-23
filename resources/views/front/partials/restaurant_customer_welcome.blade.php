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
            'openMap' => 'View on map', 'locationDeferred' => 'You can continue. Sharing your location when you order is optional.',
            'push' => 'Stay in the loop', 'pushHint' => 'Enable notifications for offers from stores on Ozman.',
            'pushEnable' => 'Enable notifications', 'pushBusy' => 'Enabling…', 'pushReady' => 'Notifications enabled',
            'pushDenied' => 'Notifications are blocked. You can enable them in browser settings and try again.',
            'pushDismissed' => 'Permission was not granted. You can enable notifications later.',
            'pushError' => 'Could not enable notifications. Try again; you can still enter the menu.',
            'pushUnavailable' => 'Notifications are unavailable in this browser. On iPhone, try adding the site to your Home Screen.',
            'submit' => 'Save & explore the menu', 'privacy' => 'Saving registers your details with Ozman for Bankai and remembers them on this device for your orders.',
            'saving' => 'Saving your details…', 'saveError' => 'Could not save your details. Check your connection and try again.',
            'confirmSaved' => 'Your details are filled in. Save them to complete your registration with Bankai.',
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
            'openMap' => 'הצגה במפה', 'locationDeferred' => 'אפשר להמשיך. שיתוף מיקום בזמן ההזמנה הוא אופציונלי.',
            'push' => 'נשארים מעודכנים', 'pushHint' => 'הפעילו התראות על מבצעים מחנויות ב-Ozman.',
            'pushEnable' => 'הפעלת התראות', 'pushBusy' => 'מפעיל…', 'pushReady' => 'ההתראות מופעלות',
            'pushDenied' => 'ההתראות חסומות. אפשר להפעיל אותן בהגדרות הדפדפן ולנסות שוב.',
            'pushDismissed' => 'לא ניתן אישור. אפשר להפעיל התראות בהמשך.',
            'pushError' => 'לא ניתן להפעיל התראות. נסו שוב; אפשר להמשיך לתפריט.',
            'pushUnavailable' => 'התראות אינן זמינות בדפדפן זה. באייפון נסו להוסיף את האתר למסך הבית.',
            'submit' => 'שמירה וכניסה לתפריט', 'privacy' => 'השמירה רושמת את הפרטים ב-Ozman עבור בנקאי ושומרת אותם במכשיר להזמנות שלך.',
            'saving' => 'שומר את הפרטים…', 'saveError' => 'לא ניתן לשמור את הפרטים. בדקו את החיבור ונסו שוב.',
            'confirmSaved' => 'הפרטים מולאו. שמרו אותם כדי להשלים את ההרשמה לבנקאי.',
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
            'openMap' => 'شوف الموقع على الخريطة', 'locationDeferred' => 'تمام، بتقدر تكمل. تحديد اللوكيشن وقت الطلب اختياري.',
            'push' => 'خليك أول من يعرف', 'pushHint' => 'فعّل الإشعارات لتصلك عروض المحلات على Ozman.',
            'pushEnable' => 'تفعيل الإشعارات', 'pushBusy' => 'جارٍ التفعيل…', 'pushReady' => 'الإشعارات مفعّلة',
            'pushDenied' => 'الإشعارات محظورة. فعّلها من إعدادات المتصفح وجرّب مرة ثانية.',
            'pushDismissed' => 'ما تم السماح بالإشعارات. بتقدر تفعّلها لاحقًا.',
            'pushError' => 'تعذّر تفعيل الإشعارات. جرّب مرة ثانية؛ بتقدر تكمل للمنيو.',
            'pushUnavailable' => 'الإشعارات غير متاحة بهالمتصفح. على آيفون جرّب إضافة الموقع للشاشة الرئيسية.',
            'submit' => 'احفظ بياناتي وافتح المنيو', 'privacy' => 'بالحفظ، بتسجّل بياناتك لدى إدارة Ozman لبانكاي، وبنحفظها على هالجهاز لتسهيل طلباتك.',
            'saving' => 'جارٍ حفظ بياناتك…', 'saveError' => 'ما قدرنا نحفظ بياناتك. تأكد من اتصالك وجرّب مرة ثانية.',
            'confirmSaved' => 'بياناتك معبّاية. احفظها حتى نكمّل تسجيلك لدى بانكاي.',
            'phoneError' => 'اكتب رقم جوال صحيح، مثل 0591234567.', 'required' => 'اكتب اسمك وعنوانك حتى نكمل.',
            'storageError' => 'حفظنا بياناتك لهالزيارة. المتصفح مش سامح نحفظها للمرة الجاي على الجهاز.',
            'continue' => 'كمّل للمنيو', 'cancel' => 'رجوع للمنيو', 'locationChoice' => 'حدّد موقعك أو اختار تحديده وقت الطلب.',
        ],
    };
    if ($shop->slug !== 'bankai-sushi') {
        foreach (['title', 'privacy', 'confirmSaved'] as $label) {
            $welcome[$label] = str_replace(['Bankai', 'בנקאי', 'بانكاي'], $shop->name, $welcome[$label]);
        }
    }
    if ($shop->catalog_type === 'cosmetics') {
        $welcome['eyebrow'] = $shop->name;
        $welcome['intro'] = match($locale) {'en'=>'Save your details once for easier shopping and your welcome gift.', 'he'=>'שמרו פרטים לקנייה נוחה ומתנת הצטרפות.', default=>'سجّل بياناتك مرة واحدة لتسهيل طلباتك والحصول على هدية التسجيل.'};
        $welcome['submit'] = $welcome['continue'] = match($locale) {'en'=>'Continue to store', 'he'=>'המשך לחנות', default=>'كمّل للمحل'};
        $welcome['cancel'] = match($locale) {'en'=>'Back to store', 'he'=>'חזרה לחנות', default=>'رجوع للمحل'};
    }
@endphp
@php
    $welcome['sessionError'] = match(app()->getLocale()) {
        'en' => 'Your session expired. Reload this page and try again.',
        'he' => 'פג תוקף ההפעלה. רעננו את העמוד ונסו שוב.',
        default => 'انتهت جلسة الصفحة. حدّث الصفحة وجرّب مرة ثانية.',
    };
    $welcome['rateError'] = match(app()->getLocale()) {
        'en' => 'Too many attempts. Wait a minute and try again.',
        'he' => 'יותר מדי ניסיונות. המתינו דקה ונסו שוב.',
        default => 'محاولات كثيرة. انتظر دقيقة وجرّب مرة ثانية.',
    };
@endphp
@viewStyle('front.partials.restaurant_customer_welcome')
<dialog id="bankaiWelcome" aria-labelledby="bankaiWelcomeTitle" aria-describedby="bankaiWelcomeIntro" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" data-storage-key="ozman.restaurant.{{ $shop->id }}.customer.v{{ $shop->customer_registration_version ?? 1 }}" data-csrf-url="{{ route('csrf.refresh') }}" data-registration-url="{{ route('restaurant.customer-registration.store', $shop) }}">
    <div class="bankai-welcome-layout">
        <section class="bankai-welcome-brand">
            @if($shop->logo)<img class="bankai-welcome-logo" src="{{ asset($shop->logo) }}" alt="{{ $shop->name }}">@endif
            <p class="bankai-welcome-kicker">{{ $welcome['eyebrow'] }}</p>
            <h2 id="bankaiWelcomeTitle">{{ $welcome['title'] }}</h2>
            <p class="bankai-welcome-intro" id="bankaiWelcomeIntro">{{ $welcome['intro'] }}</p>
            <a class="bankai-profile-edit" data-shop-app-installer href="{{ route('shop-app.install', $shop) }}">{{ __('تثبيت التطبيق') }}</a>
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
