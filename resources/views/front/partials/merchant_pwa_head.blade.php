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

@if($merchantPwaEnabled || $offerNotificationsEnabled)
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endif

@if($customerPwaEnabled)
    @php
        $customerPwaVersion = $merchantPwaShop->updated_at?->timestamp ?? 1;
        $customerPwaLabels = [
            'ready' => 'التطبيق جاهز للتثبيت على جهازك.',
            'installed' => 'تم تثبيت تطبيق '.$merchantPwaShop->name.' بنجاح.',
            'browserInstructions' => 'افتح قائمة Chrome واختر «تثبيت التطبيق» أو «إضافة إلى الشاشة الرئيسية».',
            'iosInstructions' => 'على iPhone: افتح الصفحة في Safari، اضغط مشاركة ثم «إضافة إلى الشاشة الرئيسية».',
            'openChrome' => 'فتح في Chrome للتثبيت',
            'iosInstall' => 'طريقة التثبيت على iPhone',
            'openChromeHelp' => 'اضغط الزر لفتح الرابط في Chrome وإكمال تثبيت التطبيق.',
            'unsupported' => 'هذا المتصفح لا يدعم تثبيت التطبيق. افتح الرابط في Chrome أو Safari.',
            'error' => 'تعذر تجهيز التطبيق للتثبيت. تحقق من الاتصال وحاول مجددًا.',
            'shareText' => 'حمّل تطبيق '.$merchantPwaShop->name.' وافتح المحل مباشرة من جوالك.',
            'copied' => 'تم نسخ رابط التطبيق.',
            'copyFailed' => 'تعذر نسخ الرابط. انسخه من شريط العنوان.',
        ];
        $customerPwaConfig = [
            'shopName' => $merchantPwaShop->name,
            'shareUrl' => route('shop-app.index', $merchantPwaShop),
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
                'denied' => 'יש לאשר התראות בהגדרות הדפדפן.',
                'success' => 'מעולה! מעכשיו תקבלו מבצעים מכל החנויות.',
                'error' => 'לא ניתן להפעיל התראות. נסו שוב.',
            ],
            'en' => [
                'title' => 'Enable offer notifications',
                'body' => 'Allow notifications to receive every new offer from all stores on Ozman.',
                'allow' => 'Enable notifications',
                'later' => 'Later',
                'requesting' => 'Enabling notifications…',
                'denied' => 'Please allow notifications in your browser settings.',
                'success' => 'Done! You will now receive offers from all stores.',
                'error' => 'Notifications could not be enabled. Please try again.',
            ],
            default => [
                'title' => 'فعّل إشعارات العروض',
                'body' => 'اسمح بالإشعارات ليصلك أي عرض جديد من جميع المحلات على Ozman.',
                'allow' => 'تفعيل الإشعارات',
                'later' => 'لاحقًا',
                'requesting' => 'جارٍ تفعيل الإشعارات…',
                'denied' => 'يجب السماح بالإشعارات من إعدادات المتصفح.',
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
            'showPrompt' => ($offerNotificationsPrompt ?? true) && ! $merchantPwaEnabled,
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
