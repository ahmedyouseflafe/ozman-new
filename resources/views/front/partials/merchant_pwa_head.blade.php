@php
    $merchantPwaShop = $pwaShop ?? ($shop ?? null);
    $merchantPwaUser = auth()->user();
    $forceCustomerPwa = $forceCustomerPwa ?? false;
    $merchantPwaEnabled = ! $forceCustomerPwa
        && $merchantPwaShop instanceof \App\Models\Shop
        && $merchantPwaUser?->isShopOwner()
        && $merchantPwaUser->shops()->whereKey($merchantPwaShop->id)->exists();
    $offerNotificationsEnabled = $merchantPwaShop instanceof \App\Models\Shop
        && $merchantPwaShop->is_active;
    $customerPwaEnabled = $offerNotificationsEnabled && ! $merchantPwaEnabled;
    $webPushPublicKey = ($merchantPwaEnabled || $offerNotificationsEnabled)
        ? ($vapidPublicKey ?? app(\App\Services\WebPushService::class)->publicKey())
        : null;
@endphp

@if($merchantPwaShop instanceof \App\Models\Shop && $merchantPwaShop->is_active)
    <link rel="icon" type="image/png" href="{{ route('merchant-app.icon', ['shop' => $merchantPwaShop, 'size' => 192, 'v' => $merchantPwaShop->updated_at?->timestamp ?? 1]) }}">
@endif

@if($merchantPwaEnabled || $offerNotificationsEnabled)
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('front.partials.web_push_client')
@endif

@if($customerPwaEnabled)
    @php
        $customerPwaVersion = $merchantPwaShop->updated_at?->timestamp ?? 1;
        $customerPwaLabels = [
            'ready' => 'التطبيق جاهز للتثبيت على جهازك.',
            'installed' => 'تم تثبيت تطبيق '.$merchantPwaShop->name.' بنجاح.',
            'browserInstructions' => 'افتح قائمة Chrome واختر «تثبيت التطبيق» أو «إضافة إلى الشاشة الرئيسية».',
            'iosInstructions' => 'على iPhone أو iPad: من Safari افتح «مشاركة» (أو … ثم مشاركة)، ثم «إضافة إلى الشاشة الرئيسية» واضغط «إضافة».',
            'openChrome' => 'فتح في Chrome للتثبيت',
            'iosInstall' => 'طريقة التثبيت على iPhone',
            'openChromeHelp' => 'اضغط الزر لفتح الرابط في Chrome وإكمال تثبيت التطبيق.',
            'unsupported' => 'هذا المتصفح لا يدعم تثبيت التطبيق. افتح الرابط في Chrome أو Safari.',
            'error' => 'تعذر تجهيز التطبيق للتثبيت. تحقق من الاتصال وحاول مجددًا.',
            'shareText' => 'حمّل تطبيق '.$merchantPwaShop->name.' وافتح المحل مباشرة من جوالك.',
            'copied' => 'تم نسخ رابط التطبيق.',
            'copyFailed' => 'تعذر نسخ الرابط. انسخه من شريط العنوان.',
            'install' => 'تثبيت تطبيق '.$merchantPwaShop->name,
            'copyLink' => 'نسخ رابط التثبيت',
            'cancelled' => 'لم يكتمل التثبيت. يمكنك المحاولة مجددًا من زر التثبيت أو قائمة المتصفح.',
        ];
        $customerPwaConfig = [
            'shopName' => $merchantPwaShop->name,
            'shareUrl' => route('shop-app.install', $merchantPwaShop),
            'installUrl' => route('shop-app.install', $merchantPwaShop),
            'serviceWorkerUrl' => asset('merchant-pwa-sw.js'),
            'labels' => $customerPwaLabels,
        ];
    @endphp
    <meta name="theme-color" content="#071820">
    <meta name="application-name" content="{{ $merchantPwaShop->name }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $merchantPwaShop->name }}">
    <link rel="manifest" href="{{ route('shop-app.manifest', $merchantPwaShop) }}">
    <link rel="apple-touch-icon" href="{{ route('merchant-app.icon', ['shop' => $merchantPwaShop, 'size' => 192, 'v' => $customerPwaVersion]) }}">
    <script>
        window.OZMAN_SHOP_PWA = @json($customerPwaConfig);
    </script>
    <script defer src="{{ asset('shop-pwa.js') }}?v={{ filemtime(public_path('shop-pwa.js')) }}"></script>
@endif

@if($merchantPwaEnabled)
    @php
        $merchantPwaVersion = $merchantPwaShop->updated_at?->timestamp ?? 1;
        $merchantPwaConfig = [
            'shopId' => $merchantPwaShop->id,
            'shopName' => $merchantPwaShop->name,
            'installUrl' => route('merchant-app.install', $merchantPwaShop),
            'serviceWorkerUrl' => asset('merchant-pwa-sw.js'),
            'subscribeUrl' => route('merchant-app.push.store'),
            'unsubscribeUrl' => route('merchant-app.push.destroy'),
            'vapidPublicKey' => $webPushPublicKey,
        ];
    @endphp
    <meta name="theme-color" content="#071820">
    <meta name="application-name" content="{{ $merchantPwaShop->name }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $merchantPwaShop->name }}">
    <link rel="manifest" href="{{ route('merchant-app.manifest', $merchantPwaShop) }}">
    <link rel="apple-touch-icon" href="{{ route('merchant-app.icon', ['shop' => $merchantPwaShop, 'size' => 192, 'v' => $merchantPwaVersion]) }}">
    <script>
        window.OZMAN_MERCHANT_PWA = @json($merchantPwaConfig);
    </script>
    <script defer src="{{ asset('merchant-pwa.js') }}?v={{ filemtime(public_path('merchant-pwa.js')) }}"></script>
@endif

@if($offerNotificationsEnabled)
    @php
        $offerLabels = match (app()->getLocale()) {
            'he' => [
                'title' => 'הפעלת התראות למבצעים',
                'body' => 'אשרו התראות כדי לקבל כל מבצע חדש מכל החנויות ב-Ozman.',
                'allow' => 'הפעלת התראות',
                'later' => 'אחר כך',
                'requesting' => 'מפעיל התראות…',
                'denied' => 'הדפדפן חסם התראות. פתחו את הגדרות האתר ליד הכתובת ואשרו התראות, ואז לחצו בדיקה מחדש. אפשר גם לדלג ולהמשיך לגלוש.',
                'success' => 'מעולה! מעכשיו תקבלו מבצעים מכל החנויות.',
                'error' => 'לא ניתן להפעיל התראות. נסו שוב.',
            ],
            'en' => [
                'title' => 'Enable offer notifications',
                'body' => 'Enable notifications to continue browsing and receive offers from all stores on Ozman.',
                'allow' => 'Enable notifications',
                'later' => 'Later',
                'requesting' => 'Enabling notifications…',
                'denied' => 'Your browser blocked notifications. Open site settings beside the address, allow notifications, then recheck. You can also skip and continue browsing.',
                'success' => 'Done! You will now receive offers from all stores.',
                'error' => 'Notifications could not be enabled. Please try again.',
            ],
            default => [
                'title' => 'فعّل إشعارات العروض',
                'body' => 'للمتابعة وتصفح المحل، فعّل الإشعارات لتصلك عروض جميع المحلات على Ozman.',
                'allow' => 'تفعيل الإشعارات',
                'later' => 'لاحقًا',
                'requesting' => 'جارٍ تفعيل الإشعارات…',
                'denied' => 'المتصفح حاظر الإشعارات. افتح إعدادات الموقع بجانب العنوان، اسمح بالإشعارات ثم اضغط تحقق مجددًا. أو اضغط تخطّي وكمل تسوّق.',
                'success' => 'تم! ستصلك الآن عروض جميع المحلات.',
                'error' => 'تعذر تفعيل الإشعارات. حاول مرة أخرى.',
            ],
        };
        $offerNotificationsConfig = [
            'sourceShopId' => $merchantPwaShop->id,
            'serviceWorkerUrl' => asset('merchant-pwa-sw.js'),
            'subscribeUrl' => route('offers.push.store'),
            'unsubscribeUrl' => route('offers.push.destroy'),
            'vapidPublicKey' => $webPushPublicKey,
            'showPrompt' => ! request()->routeIs('shop-app.install', 'merchant-app.install'),
            'iosLabel' => match (app()->getLocale()) {
                'en' => 'Notifications are unavailable here. You can skip and browse without installing anything.',
                'he' => 'התראות אינן זמינות כאן. אפשר לדלג ולגלוש ללא התקנה.',
                default => 'الإشعارات مش متاحة من هون. بتقدر تتخطّى وتكمل تصفح بدون تثبيت.',
            },
            'iosBodyLabel' => match (app()->getLocale()) {
                'en' => 'Receive offers from all stores, or skip and continue browsing.',
                'he' => 'קבלו מבצעים מכל החנויות, או דלגו והמשיכו לגלוש.',
                default => 'فعّل الإشعارات لتصلك عروض كل المحلات، أو تخطّى وكمل تصفح.',
            },
            'skipLabel' => match (app()->getLocale()) {
                'en' => 'Skip and browse',
                'he' => 'דלג והמשך לגלוש',
                default => 'تخطّي ومتابعة التصفح',
            },
            'timeoutLabel' => match (app()->getLocale()) {
                'en' => 'Activation took too long. Check the browser permission prompt, retry, or skip and continue shopping.',
                'he' => 'ההפעלה מתעכבת. בדקו את בקשת האישור בדפדפן, נסו שוב או דלגו והמשיכו לקנות.',
                default => 'التفعيل تأخر. تأكد من طلب السماح بالمتصفح، جرّب مجددًا أو اضغط تخطّي وكمل تسوّق.',
            },
            'dismissedLabel' => match (app()->getLocale()) {
                'en' => 'Permission was not granted. Try again and choose Allow, or skip and continue shopping.',
                'he' => 'לא התקבל אישור. נסו שוב ובחרו אישור, או דלגו והמשיכו לקנות.',
                default => 'ما تم السماح بالإشعارات. جرّب مجددًا واختار سماح، أو اضغط تخطّي وكمل تسوّق.',
            },
            'unsupportedLabel' => 'هذا المتصفح لا يدعم إشعارات الموقع. افتح الرابط في متصفح يدعم الإشعارات مثل Chrome على أندرويد. إذا كنت داخل واتساب أو إنستغرام، افتح الرابط في المتصفح الخارجي.',
            'recheckLabel' => 'تحقّق من التفعيل مجددًا',
            'title' => $offerLabels['title'],
            'body' => $offerLabels['body'],
            'allowLabel' => $offerLabels['allow'],
            'laterLabel' => $offerLabels['later'],
            'requestingLabel' => $offerLabels['requesting'],
            'deniedLabel' => $offerLabels['denied'],
            'successLabel' => $offerLabels['success'],
            'errorLabel' => $offerLabels['error'],
        ];
    @endphp
    <script>
        window.OZMAN_OFFER_NOTIFICATIONS = @json($offerNotificationsConfig);
    </script>
    <script defer src="{{ asset('offer-notifications.js') }}?v={{ filemtime(public_path('offer-notifications.js')) }}"></script>
@endif
