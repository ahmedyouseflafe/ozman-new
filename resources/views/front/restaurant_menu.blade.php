@php
    $locale = app()->getLocale();
    $hasCustomerWelcome = $shop->slug === 'bankai-sushi' || \App\Models\RewardWheel::where('key', 'shop_signup_'.$shop->id)->exists();
    $isRtl = in_array($locale, ['ar', 'he'], true);
    $restaurantDictionary = [
        'ar' => [
            'menu_kicker' => 'منيو المطعم', 'table_order' => 'أنت تطلب الآن من', 'menu_intro' => 'اختر وجبتك، خصصها وأرسل طلبك للمطعم',
            'restaurant_open' => 'المطعم مفتوح ويستقبل الطلبات', 'restaurant_closed' => 'المطعم مغلق حالياً',
            'closed_detail' => 'يمكنك تصفح المنيو الآن، وسيعود استقبال الطلبات فور فتح المطعم.',
            'inside_restaurant' => 'طلب من داخل المطعم', 'delivery_pickup' => 'توصيل أو استلام', 'food_menu' => 'قائمة الطعام',
            'food_hint' => 'اضغط على الوجبة لاختيار الحجم والإضافات.', 'menu_sections' => 'أقسام المنيو', 'meals' => 'الوجبات',
            'show_options' => 'عرض خيارات', 'fresh_meal' => 'وجبة طازجة محضرة حسب طلبك.', 'prep_around' => 'تجهيز خلال نحو',
            'minute' => 'دقيقة', 'from' => 'من', 'add' => 'إضافة', 'empty_section' => 'لا توجد وجبات في هذا القسم حالياً.',
            'empty_menu' => 'لا توجد وجبات متاحة حالياً.', 'close_order' => 'إغلاق الطلب', 'order_details' => 'تفاصيل الطلب',
            'your_order' => 'طلبك', 'total' => 'المجموع', 'registered_on' => 'الطلب مسجل على', 'delivery_to_address' => 'توصيل إلى العنوان',
            'pickup' => 'استلام من المطعم', 'customer_name' => 'اسم صاحب الطلب', 'name' => 'الاسم', 'phone' => 'رقم الهاتف',
            'address' => 'عنوان التوصيل بالتفصيل', 'locate_me' => 'حدد موقعي للتوصيل', 'location_required' => 'اختياري: حدد موقعك عشان يوصلك الطلب أسرع.', 'address_required' => 'اكتب عنوان التوصيل بالتفصيل.',
            'notes' => 'ملاحظات عامة للمطعم', 'submit' => 'تأكيد وإرسال الطلب', 'track_order' => 'تتبّع طلبك', 'live_update' => 'تحديث مباشر',
            'estimated_prep' => 'مدة التجهيز المتوقعة:', 'ready_in' => 'طلبك بيجهز خلال', 'almost_ready' => 'طلبك قرّب يجهز 🙏', 'received' => 'تم الاستلام', 'preparing' => 'قيد التحضير', 'ready' => 'جاهز',
            'completed' => 'مكتمل', 'full_tracking' => 'فتح صفحة التتبّع الكاملة', 'view_order' => 'عرض الطلب', 'choose_size' => 'اختر حجم الوجبة',
            'addons' => 'إضافات على الوجبة', 'remove_ingredients' => 'حذف مكونات', 'quantity' => 'الكمية', 'meal_notes' => 'ملاحظات خاصة بهذه الوجبة',
            'add_to_order' => 'إضافة إلى الطلب', 'cancel' => 'إلغاء', 'empty_cart' => 'لم تضف أي وجبة بعد', 'without' => 'بدون', 'about' => 'نحو',
            'geo_unsupported' => 'المتصفح لا يدعم تحديد الموقع.', 'locating' => 'جاري تحديد موقعك بدقة...', 'location_success' => 'تم تحديد موقعك بنجاح',
            'open_location' => 'فتح الموقع', 'location_failed' => 'تعذر تحديد الموقع. اسمح للموقع بالوصول إلى اللوكيشن ثم حاول مجدداً.',
            'order_number' => 'رقم الطلب:', 'add_meal_error' => 'أضف وجبة واحدة على الأقل.', 'set_location_error' => 'حدد موقعك للتوصيل قبل إرسال الطلب.',
            'sent_success' => 'تم إرسال طلبك بنجاح. رقم الطلب:', 'send_error' => 'تعذر إرسال الطلب، حاول مرة أخرى.',
        ],
        'he' => [
            'menu_kicker' => 'תפריט המסעדה', 'table_order' => 'ההזמנה שלך משולחן', 'menu_intro' => 'בחרו מנה, התאימו אותה ושלחו את ההזמנה למסעדה',
            'restaurant_open' => 'המסעדה פתוחה ומקבלת הזמנות', 'restaurant_closed' => 'המסעדה סגורה כעת',
            'closed_detail' => 'אפשר לעיין בתפריט כעת. קבלת ההזמנות תחזור כשהמסעדה תיפתח.',
            'inside_restaurant' => 'הזמנה בתוך המסעדה', 'delivery_pickup' => 'משלוח או איסוף', 'food_menu' => 'תפריט אוכל',
            'food_hint' => 'לחצו על מנה לבחירת גודל ותוספות.', 'menu_sections' => 'קטגוריות התפריט', 'meals' => 'מנות',
            'show_options' => 'אפשרויות עבור', 'fresh_meal' => 'מנה טרייה שמוכנה לפי הזמנתכם.', 'prep_around' => 'מוכן בתוך כ־',
            'minute' => 'דקות', 'from' => 'החל מ־', 'add' => 'הוספה', 'empty_section' => 'אין כרגע מנות בקטגוריה זו.',
            'empty_menu' => 'אין כרגע מנות זמינות.', 'close_order' => 'סגירת ההזמנה', 'order_details' => 'פרטי ההזמנה',
            'your_order' => 'ההזמנה שלך', 'total' => 'סה״כ', 'registered_on' => 'ההזמנה נרשמה על', 'delivery_to_address' => 'משלוח לכתובת',
            'pickup' => 'איסוף מהמסעדה', 'customer_name' => 'שם המזמין', 'name' => 'שם', 'phone' => 'מספר טלפון',
            'address' => 'כתובת מלאה למשלוח', 'locate_me' => 'איתור המיקום למשלוח', 'location_required' => 'אופציונלי: סמנו מיקום כדי שההזמנה תגיע מהר יותר.', 'address_required' => 'כתבו כתובת מלאה למשלוח.',
            'notes' => 'הערות כלליות למסעדה', 'submit' => 'אישור ושליחת ההזמנה', 'track_order' => 'מעקב הזמנה', 'live_update' => 'עדכון חי',
            'estimated_prep' => 'זמן הכנה משוער:', 'ready_in' => 'ההזמנה תהיה מוכנה בעוד', 'almost_ready' => 'ההזמנה כמעט מוכנה 🙏', 'received' => 'התקבלה', 'preparing' => 'בהכנה', 'ready' => 'מוכנה',
            'completed' => 'הושלמה', 'full_tracking' => 'פתיחת עמוד המעקב המלא', 'view_order' => 'צפייה בהזמנה', 'choose_size' => 'בחירת גודל המנה',
            'addons' => 'תוספות למנה', 'remove_ingredients' => 'הסרת מרכיבים', 'quantity' => 'כמות', 'meal_notes' => 'הערות למנה זו',
            'add_to_order' => 'הוספה להזמנה', 'cancel' => 'ביטול', 'empty_cart' => 'עדיין לא הוספתם מנה', 'without' => 'ללא', 'about' => 'כ־',
            'geo_unsupported' => 'הדפדפן אינו תומך באיתור מיקום.', 'locating' => 'מאתרים את המיקום המדויק...', 'location_success' => 'המיקום אותר בהצלחה',
            'open_location' => 'פתיחת המיקום', 'location_failed' => 'לא ניתן לאתר את המיקום. אשרו גישה למיקום ונסו שוב.',
            'order_number' => 'מספר הזמנה:', 'add_meal_error' => 'הוסיפו לפחות מנה אחת.', 'set_location_error' => 'סמנו מיקום למשלוח לפני שליחת ההזמנה.',
            'sent_success' => 'ההזמנה נשלחה בהצלחה. מספר הזמנה:', 'send_error' => 'לא ניתן לשלוח את ההזמנה. נסו שוב.',
        ],
        'en' => [
            'menu_kicker' => 'Restaurant menu', 'table_order' => 'You are ordering from', 'menu_intro' => 'Choose your meal, customize it and send your order to the restaurant',
            'restaurant_open' => 'The restaurant is open and accepting orders', 'restaurant_closed' => 'The restaurant is currently closed',
            'closed_detail' => 'You can browse the menu now. Ordering will resume when the restaurant opens.',
            'inside_restaurant' => 'Dine-in order', 'delivery_pickup' => 'Delivery or pickup', 'food_menu' => 'Food menu',
            'food_hint' => 'Tap a meal to choose its size and extras.', 'menu_sections' => 'Menu sections', 'meals' => 'Meals',
            'show_options' => 'View options for', 'fresh_meal' => 'A fresh meal prepared to your order.', 'prep_around' => 'Ready in about',
            'minute' => 'minutes', 'from' => 'From', 'add' => 'Add', 'empty_section' => 'There are no meals in this section yet.',
            'empty_menu' => 'There are no meals available yet.', 'close_order' => 'Close order', 'order_details' => 'Order details',
            'your_order' => 'Your order', 'total' => 'Total', 'registered_on' => 'Order assigned to', 'delivery_to_address' => 'Deliver to address',
            'pickup' => 'Pickup from restaurant', 'customer_name' => 'Customer name', 'name' => 'Name', 'phone' => 'Phone number',
            'address' => 'Full delivery address', 'locate_me' => 'Set my delivery location', 'location_required' => 'Optional: share your location so the order arrives faster.', 'address_required' => 'Write the full delivery address.',
            'notes' => 'Notes for the restaurant', 'submit' => 'Confirm and send order', 'track_order' => 'Track your order', 'live_update' => 'Live update',
            'estimated_prep' => 'Estimated preparation time:', 'ready_in' => 'Your order will be ready in', 'almost_ready' => 'Your order is almost ready 🙏', 'received' => 'Received', 'preparing' => 'Preparing', 'ready' => 'Ready',
            'completed' => 'Completed', 'full_tracking' => 'Open full tracking page', 'view_order' => 'View order', 'choose_size' => 'Choose meal size',
            'addons' => 'Meal extras', 'remove_ingredients' => 'Remove ingredients', 'quantity' => 'Quantity', 'meal_notes' => 'Notes for this meal',
            'add_to_order' => 'Add to order', 'cancel' => 'Cancel', 'empty_cart' => 'You have not added a meal yet', 'without' => 'Without', 'about' => 'about',
            'geo_unsupported' => 'Your browser does not support location services.', 'locating' => 'Finding your precise location...', 'location_success' => 'Location set successfully',
            'open_location' => 'Open location', 'location_failed' => 'Could not find your location. Allow location access and try again.',
            'order_number' => 'Order number:', 'add_meal_error' => 'Add at least one meal.', 'set_location_error' => 'Set your delivery location before sending the order.',
            'sent_success' => 'Your order was sent successfully. Order number:', 'send_error' => 'Could not send your order. Please try again.',
        ],
    ];
    $copy = $restaurantDictionary[$locale] ?? $restaurantDictionary['ar'];
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('front.partials.merchant_pwa_head', ['pwaShop' => $shop, 'offerNotificationsPrompt' => ! $hasCustomerWelcome])
    @php
        $restaurantCanonical = route('restaurant.menu', $shop);
        $restaurantDescription = $shop->description ?: "تصفح منيو {$shop->name} والأسعار واطلب مباشرة عبر Ozman.";
        $restaurantImage = $shop->banner
            ? asset(\Illuminate\Support\Str::startsWith($shop->banner, 'storage/') ? $shop->banner : 'storage/'.$shop->banner)
            : ($shop->logo ? asset($shop->logo) : asset('images/logo.svg'));
    @endphp
    @include('front.partials.seo', [
        'title' => 'منيو '.$shop->name.' | Ozman',
        'description' => $restaurantDescription,
        'canonical' => $restaurantCanonical,
        'image' => $restaurantImage,
        'robots' => $table ? 'noindex, follow' : 'index, follow, max-image-preview:large',
        'schema' => [
            '@context' => 'https://schema.org', '@type' => 'Restaurant', 'name' => $shop->name,
            'url' => $restaurantCanonical, 'description' => $restaurantDescription,
            'image' => $restaurantImage, 'telephone' => $shop->phone, 'address' => $shop->address,
        ],
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('front.restaurant_menu')
    @viewStyle('front.restaurant_menu-2')
</head>

<body>
    @php
        $restaurantProducts = $products
            ->map(function ($product) use ($products) {
                $attributes = $product->catalog_attributes ?? [];
                return [
                    'id' => $product->id,
                    'name' => $product->localized('name'),
                    'price' => (float) ($product->discount_price ?: $product->price),
                    'sizes' => $attributes['meal_size_prices'] ?? [],
                    'addons' => $attributes['addon_prices'] ?? [],
                    'choice_groups' => app(\App\Services\MealChoiceService::class)->forProduct($product, $products),
                    'ingredients' => $attributes['removable_ingredients'] ?? [],
                    'preparation_time' => max(0, (int) ($attributes['preparation_time'] ?? 0)),
                ];
            })
            ->values();
        $restaurantIdentity = strtolower($shop->name.' '.$shop->slug);
        $isSushiRestaurant = str_contains($restaurantIdentity, 'sushi')
            || str_contains($restaurantIdentity, 'سوشي')
            || str_contains($restaurantIdentity, 'סושי');
        $bundledSushiBackground = 'media/restaurant/sushi-background-vertical.mp4';
        $remoteSushiBackground = 'https://videos.pexels.com/video-files/19742746/19742746-hd_1080_1920_30fps.mp4';
        $defaultCategoryBackground = $isSushiRestaurant
            ? (is_file(public_path($bundledSushiBackground))
                ? asset($bundledSushiBackground)
                : $remoteSushiBackground)
            : null;
        $restaurantCategories = $categories
            ->map(function ($category) use ($products, $defaultCategoryBackground) {
                $categoryIdentity = strtolower(collect([
                    $category->name,
                    $category->slug,
                    ...array_values($category->name_translations ?? []),
                ])->filter(fn ($value) => is_string($value) && filled($value))->implode(' '));
                $categoryPlacement = match (true) {
                    str_contains($categoryIdentity, 'مشروب'),
                    str_contains($categoryIdentity, 'drink'),
                    str_contains($categoryIdentity, 'beverage'),
                    str_contains($categoryIdentity, 'משקאות') => 'drinks',
                    str_contains($categoryIdentity, 'صوص'),
                    str_contains($categoryIdentity, 'صلص'),
                    str_contains($categoryIdentity, 'sauce'),
                    str_contains($categoryIdentity, 'רטבים') => 'sauces',
                    str_contains($categoryIdentity, 'مميز'),
                    str_contains($categoryIdentity, 'featured'),
                    str_contains($categoryIdentity, 'מיוחד'),
                    str_contains($categoryIdentity, 'מומלץ') => 'featured',
                    default => 'regular',
                };

                return [
                    'key' => (string) $category->id,
                    'name' => $category->localized('name'),
                    'identity' => $categoryIdentity,
                    'placement' => $categoryPlacement,
                    'image' => $category->image ?: $products->first(fn($product) => $product->category_id === $category->id && filled($product->main_image))?->main_image,
                    'background' => $category->background_video
                        ? asset($category->background_video)
                        : $defaultCategoryBackground,
                    'products' => $products->where('category_id', $category->id)->values(),
                ];
            })
            ->values();
        $uncategorizedProducts = $products->whereNull('category_id')->values();
        if ($uncategorizedProducts->isNotEmpty()) {
            $restaurantCategories->push([
                'key' => 'uncategorized',
                'name' => $copy['meals'],
                'identity' => 'uncategorized',
                'placement' => 'regular',
                'image' => $uncategorizedProducts->first(fn($product) => filled($product->main_image))?->main_image,
                'background' => $defaultCategoryBackground,
                'products' => $uncategorizedProducts,
            ]);
        }
        $restaurantCategories = $restaurantCategories
            ->where('placement', 'regular')
            ->concat($restaurantCategories->where('placement', 'featured'))
            ->concat($restaurantCategories->where('placement', 'sauces'))
            ->concat($restaurantCategories->where('placement', 'drinks'))
            ->values();
        $newProductsIndex = $restaurantCategories->search(fn ($category) =>
            str_contains($category['identity'], 'new product')
            || str_contains($category['identity'], 'منتجات جديدة')
            || str_contains($category['identity'], 'מוצרים חדשים')
        );
        $specialRollsIndex = $restaurantCategories->search(fn ($category) =>
            str_contains($category['identity'], 'special roll')
            || str_contains($category['identity'], 'رولات مميزة')
            || str_contains($category['identity'], 'رول مميز')
            || str_contains($category['identity'], 'רולים מיוחדים')
        );
        if ($newProductsIndex !== false && $specialRollsIndex !== false && $newProductsIndex < $specialRollsIndex) {
            $newProductsCategory = $restaurantCategories->get($newProductsIndex);
            $restaurantCategories->put($newProductsIndex, $restaurantCategories->get($specialRollsIndex));
            $restaurantCategories->put($specialRollsIndex, $newProductsCategory);
        }
    @endphp
    @php
        $storyLabel = match ($locale) {
            'he' => 'פתחו את הסטורי לצפייה במבצעים האחרונים',
            'en' => 'Open the story to view the latest offers',
            default => 'افتح الستوري لمشاهدة آخر العروض',
        };
        $availabilityShortLabel = match ($locale) {
            'he' => $shop->is_accepting_orders ? 'פתוח' : 'סגור',
            'en' => $shop->is_accepting_orders ? 'Open' : 'Closed',
            default => $shop->is_accepting_orders ? 'مفتوح' : 'مغلق',
        };
        $displayScreenLabel = match ($locale) {
            'he' => 'מסך תצוגה',
            'en' => 'DISPLAY',
            default => 'شاشة العرض',
        };
        $browseAllShopsLabel = match ($locale) {
            'he' => 'לכל החנויות',
            'en' => 'Browse all stores',
            default => 'تصفح جميع المحلات',
        };
        $socialMediaLabel = match ($locale) {
            'he' => 'רשתות חברתיות',
            'en' => 'Social media',
            default => 'منصات التواصل الاجتماعي',
        };
        $normalizeSocialProfile = function (?string $value, string $base, bool $withAt = false): ?string {
            if (! filled($value)) {
                return null;
            }

            $value = trim($value);
            if (preg_match('/^https?:\/\//i', $value)) {
                return $value;
            }
            if (preg_match('/^(?:www\.)?[\w.-]+\.[a-z]{2,}\//i', $value)) {
                return 'https://'.ltrim($value, '/');
            }

            $handle = ltrim($value, '@/');
            return $handle === '' ? null : $base.($withAt ? '@' : '').$handle;
        };
        $normalizeSocialWhatsapp = function (?string $value): ?string {
            if (! filled($value)) {
                return null;
            }

            $value = trim($value);
            if (preg_match('/^https?:\/\//i', $value)) {
                return $value;
            }

            $digits = preg_replace('/\D+/', '', $value);
            return $digits ? 'https://wa.me/'.$digits : null;
        };
        $restaurantSocial = optional($shop->social);
        $restaurantSocialLinks = collect([
            ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'ti-brand-facebook', 'url' => $normalizeSocialProfile($restaurantSocial->facebook, 'https://facebook.com/')],
            ['key' => 'instagram', 'label' => 'Instagram', 'icon' => 'ti-brand-instagram', 'url' => $normalizeSocialProfile($restaurantSocial->instagram, 'https://instagram.com/')],
            ['key' => 'tiktok', 'label' => 'TikTok', 'icon' => 'ti-brand-tiktok', 'url' => $normalizeSocialProfile($restaurantSocial->tiktok, 'https://tiktok.com/', true)],
            ['key' => 'telegram', 'label' => 'Telegram', 'icon' => 'ti-brand-telegram', 'url' => $normalizeSocialProfile($restaurantSocial->telegram, 'https://t.me/')],
            ['key' => 'snapchat', 'label' => 'Snapchat', 'icon' => 'ti-brand-snapchat', 'url' => $normalizeSocialProfile($restaurantSocial->snapchat, 'https://snapchat.com/add/')],
            ['key' => 'twitter', 'label' => 'X', 'icon' => 'ti-brand-x', 'url' => $normalizeSocialProfile($restaurantSocial->twitter, 'https://x.com/')],
            ['key' => 'youtube', 'label' => 'YouTube', 'icon' => 'ti-brand-youtube', 'url' => $normalizeSocialProfile($restaurantSocial->youtube, 'https://youtube.com/', true)],
            ['key' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'ti-brand-whatsapp', 'url' => $normalizeSocialWhatsapp($restaurantSocial->whatsapp ?: $shop->whatsapp)],
        ])->filter(fn ($link) => filled($link['url']))->values();
        $restaurantWhatsappLink = $restaurantSocialLinks->firstWhere('key', 'whatsapp');
        $restaurantLocationLabel = match ($locale) {'en' => 'Restaurant location', 'he' => 'מיקום המסעדה', default => 'موقع المطعم'};
        $restaurantMapQuery = is_numeric($shop->latitude) && is_numeric($shop->longitude)
            ? $shop->latitude.','.$shop->longitude
            : (filled($shop->address) ? implode(', ', array_filter([$shop->name, $shop->address, $shop->city, $shop->country])) : null);
        $restaurantMapUrl = $restaurantMapQuery ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($restaurantMapQuery) : null;
        $restaurantSocialLinks = $restaurantSocialLinks->reject(fn ($link) => $link['key'] === 'whatsapp')->values();
        $restaurantDisplayItems = collect($displayItems ?? [])->filter(fn ($item) => filled($item->media))->values();
        $displayMediaUrl = function (?string $path): string {
            if (! filled($path)) {
                return '';
            }

            return preg_match('/^https?:\/\//i', $path) ? $path : asset($path);
        };
        $displayYoutubeEmbedUrl = function (?string $url): string {
            if (! filled($url)) {
                return '';
            }

            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]+)/', $url, $matches)) {
                return 'https://www.youtube.com/embed/'.$matches[1].'?autoplay=0&mute=1&playsinline=1&rel=0&enablejsapi=1';
            }

            return $url;
        };
    @endphp
    <div class="shell">
        <div class="restaurant-hero-layout">
            <section class="restaurant-display-screen" aria-label="{{ $displayScreenLabel }}">
                <div class="restaurant-display-slider" data-restaurant-display>
                    @if ($restaurantDisplayItems->isNotEmpty())
                        @foreach ($restaurantDisplayItems as $item)
                            <article class="restaurant-display-slide {{ $loop->first ? 'active' : '' }}"
                                data-display-duration="{{ max((int) ($item->duration ?? 8), 1) * 1000 }}">
                                @if ($item->type === 'video')
                                    <video src="{{ $displayMediaUrl($item->media) }}" muted playsinline loop preload="metadata"></video>
                                @elseif ($item->type === 'youtube')
                                    <iframe src="{{ $displayYoutubeEmbedUrl($item->media) }}"
                                        title="{{ $item->title ?: $displayScreenLabel }}"
                                        allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                                @else
                                    <img src="{{ $displayMediaUrl($item->media) }}"
                                        alt="{{ $item->title ?: $displayScreenLabel }}">
                                @endif
                            </article>
                        @endforeach
                    @elseif ($defaultCategoryBackground)
                        <article class="restaurant-display-slide active" data-display-duration="14000">
                            <video src="{{ $defaultCategoryBackground }}" muted playsinline loop preload="metadata"></video>
                        </article>
                    @else
                        <article class="restaurant-display-slide active" data-display-duration="10000">
                            <img src="{{ $restaurantImage }}" alt="{{ $shop->name }}">
                        </article>
                    @endif
                </div>
                <div class="restaurant-display-shade" aria-hidden="true"></div>
                @include('front.partials.display_sound_toggle')
            </section>

            <header class="hero">
                <div class="hero-tools">
                    @include('front.partials.public_language_switcher')
                    <a class="ozman-directory-link" href="{{ route('front.home') }}"
                        aria-label="{{ $browseAllShopsLabel }}">
                        <img src="{{ $ozmanLogo ? asset($ozmanLogo) : asset('ozman-favicon.png') }}" alt="" aria-hidden="true">
                        <span>{{ $browseAllShopsLabel }}</span>
                    </a>
                </div>
                <div class="brand">
                    <div class="restaurant-logo-stack">
                        @if ($shop->logo)
                            <button type="button"
                                class="restaurant-story-avatar {{ $hasActiveStories ? 'has-shop-story' : '' }}"
                                data-shop-story-trigger data-story-shop-id="{{ $shop->id }}"
                                aria-label="{{ $storyLabel }} — {{ $shop->name }}"
                                @disabled(! $hasActiveStories)>
                                <span class="restaurant-story-logo-frame">
                                    <img class="logo" src="{{ asset($shop->logo) }}" alt="{{ $shop->name }}">
                                </span>
                                <span class="restaurant-story-live"><i class="ti ti-player-play-filled" aria-hidden="true"></i> {{ $storyLabel }}</span>
                            </button>
                        @endif
                    </div>
                    @if($restaurantSocialLinks->isNotEmpty())
                        <div class="restaurant-contact-stack">
                        <nav class="restaurant-social-links" aria-label="{{ $socialMediaLabel }}">
                            @foreach($restaurantSocialLinks as $socialLink)
                                <a class="restaurant-social-link is-{{ $socialLink['key'] }}"
                                    href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer"
                                    aria-label="{{ $socialLink['label'] }}" title="{{ $socialLink['label'] }}">
                                    <i class="ti {{ $socialLink['icon'] }}" aria-hidden="true"></i>
                                </a>
                            @endforeach
                        </nav>
                        </div>
                    @endif
                </div>
                <div class="restaurant-quick-actions">
                    <span class="restaurant-availability {{ $shop->is_accepting_orders ? 'is-open' : 'is-closed' }}"><i aria-hidden="true"></i>{{ $availabilityShortLabel }}</span>
                    @if($restaurantWhatsappLink)
                        <a class="restaurant-availability is-open restaurant-whatsapp" href="{{ $restaurantWhatsappLink['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="ti ti-brand-whatsapp" aria-hidden="true"></i></a>
                    @endif
                </div>
                @if($restaurantMapUrl)
                    <a class="restaurant-location-link" href="{{ $restaurantMapUrl }}" target="_blank" rel="noopener noreferrer">
                        <i class="ti ti-map-pin" aria-hidden="true"></i><span>{{ $restaurantLocationLabel }}@if(filled($shop->address))<small>{{ $shop->address }}</small>@endif</span>
                    </a>
                @endif
                @if($hasCustomerWelcome && $shop->slug !== 'bankai-sushi')
                    <button type="button" class="bankai-profile-edit" id="bankaiProfileEdit"><i class="ti ti-user-circle" aria-hidden="true"></i> {{ match ($locale) { 'en' => 'My details', 'he' => 'הפרטים שלי', default => 'بياناتي' } }}</button>
                @endif
            </header>
        </div>

        @unless($shop->is_accepting_orders)
            <div class="restaurant-closed-notice" role="status">
                <i class="ti ti-door-off" aria-hidden="true"></i>
                <div>
                    <strong>{{ $copy['restaurant_closed'] }}</strong>
                    <span>{{ $copy['closed_detail'] }}</span>
                </div>
            </div>
        @endunless

        <div class="layout">
            <section class="menu-panel">
                @if($restaurantCategories->isNotEmpty())
                    <div class="menu-browser" id="menuBrowser">
                        <nav class="category-rail" id="categoryRail" aria-label="{{ $copy['menu_sections'] }}">
                            @foreach($restaurantCategories as $category)
                                <button type="button" class="category-tab @if($loop->first) active @endif"
                                    data-category-key="{{ $category['key'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                    @if($category['image'])
                                        <img src="{{ asset($category['image']) }}" alt="" loading="lazy">
                                    @else
                                        <span class="category-tab-icon"><i class="ti ti-tools-kitchen-2"></i></span>
                                    @endif
                                    <span>{{ $category['name'] }}</span>
                                </button>
                            @endforeach
                        </nav>
                        <div class="category-content" id="categoryContent">
                            <div class="category-background-stage" aria-hidden="true">
                                <video class="category-background" id="categoryBackground" muted loop playsinline
                                    preload="metadata" disablepictureinpicture
                                    @if($isSushiRestaurant) data-fallback-src="{{ $remoteSushiBackground }}" @endif></video>
                            </div>
                            @foreach($restaurantCategories as $category)
                                <section class="category category-pane" data-category-pane="{{ $category['key'] }}"
                                    @if($category['background']) data-background-video="{{ $category['background'] }}" @endif
                                    @if(!$loop->first) hidden @endif>
                                    <div class="meals">
                            @forelse ($category['products'] as $product)
                                <article class="meal" data-product-id="{{ $product->id }}" role="button" tabindex="0"
                                    aria-label="{{ $copy['show_options'] }} {{ $product->localized('name') }}">
                                    @if ($product->main_image)
                                        <img class="meal-image" src="{{ asset($product->main_image) }}"
                                        alt="{{ $product->localized('name') }}">@else<div class="meal-image"></div>
                                    @endif
                                    @if (data_get($product->catalog_attributes, 'photo_is_illustrative'))
                                        <span class="meal-photo-note">{{ match ($locale) {
                                            'he' => 'תמונה להמחשה',
                                            'en' => 'Illustrative photo',
                                            default => 'صورة توضيحية',
                                        } }}</span>
                                    @endif
                                    <div class="meal-body">
                                        <div class="meal-title-row"><h3>{{ $product->localized('name') }}</h3>@include('front.partials.product_like', ['product' => $product, 'variant' => 'inline'])</div>
                                        <p class="meal-desc">
                                            {{ $product->localized('description') ?: $copy['fresh_meal'] }}</p>
                                        @if((int) data_get($product->catalog_attributes, 'preparation_time', 0) > 0)
                                            <span class="meal-prep"><i class="ti ti-clock"></i> {{ $copy['prep_around'] }} {{ (int) data_get($product->catalog_attributes, 'preparation_time') }} {{ $copy['minute'] }}</span>
                                        @endif
                                        <div class="meal-bottom"><span class="price">{{ $copy['from'] }}
                                                {{ number_format((float) ($product->discount_price ?: $product->price), 2) }}
                                                ₪</span><button class="add-btn"
                                                data-product-id="{{ $product->id }}" @disabled(! $shop->is_accepting_orders)><i class="ti ti-plus"></i>
                                                {{ $copy['add'] }}</button></div>
                                    </div>
                                </article>
                            @empty
                                <div class="empty"><i class="ti ti-tools-kitchen-off"></i>{{ $copy['empty_section'] }}</div>
                            @endforelse
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty"><i class="ti ti-tools-kitchen-off"></i>{{ $copy['empty_menu'] }}</div>
                @endif
            </section>

            <button type="button" class="cart-backdrop" id="cartBackdrop" aria-label="{{ $copy['close_order'] }}"></button>
            <aside class="cart" id="cartPanel" aria-hidden="true" aria-label="{{ $copy['order_details'] }}">
                <div class="cart-title">
                    <h2>{{ $copy['your_order'] }}</h2>
                    <div style="display:flex;align-items:center;gap:9px">
                        <span class="count" id="cartCount">0</span>
                        <button type="button" class="cart-close" id="cartClose" aria-label="{{ $copy['close_order'] }}">
                            <i class="ti ti-x" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="cart-items" id="cartItems"></div>
                <div class="cart-total"><span>{{ $copy['total'] }}</span><strong><span id="total">0.00</span> ₪</strong></div>@include('front.partials.loyalty_hint', ['total' => '#total'])
                @if($hasCustomerWelcome)<p id="signupDiscountLine" hidden style="color:#51e4b0;font-size:12px" aria-live="polite"></p>@endif
                @if($hasCustomerWelcome)<div id="signupGiftLine" hidden style="color:#51e4b0;padding:12px 0;font-size:13px" aria-live="polite"><img id="signupCartGiftImage" alt="" hidden style="width:64px;height:64px;object-fit:contain;vertical-align:middle;border-radius:10px;background:white;margin-inline-end:10px"><span id="signupCartGiftLabel"></span></div>@endif
                @if($table)
                    <div class="table-context">
                        <i class="ti ti-table" aria-hidden="true"></i>
                        <span><small>{{ $copy['registered_on'] }}</small><strong>{{ $table->name }}</strong></span>
                    </div>
                @endif
                @unless ($table)
                    <select class="field" id="type">
                        <option value="delivery">{{ $copy['delivery_to_address'] }}</option>
                        <option value="pickup">{{ $copy['pickup'] }}</option>
                </select>@else<input type="hidden" id="type" value="dine_in">
                @endunless
                <div class="fields-row"><input class="field" id="name"
                        placeholder="{{ $table ? $copy['customer_name'] : $copy['name'] }}"><input class="field" id="phone"
                        inputmode="tel" placeholder="{{ $copy['phone'] }}"></div>
                @unless ($table)
                    <input class="field" id="address" placeholder="{{ $copy['address'] }}">
                    <div class="location-box" id="locationBox"><button class="location-btn" type="button"
                            id="detectLocation"><i class="ti ti-current-location"></i> {{ $copy['locate_me'] }}</button>
                        <p class="location-status" id="locationStatus">{{ $copy['location_required'] }}</p>
                    </div>
                @else
                    <input type="hidden" id="address">
                @endunless
                <input type="hidden" id="latitude"><input type="hidden" id="longitude">
                <textarea class="field" id="orderNotes" placeholder="{{ $copy['notes'] }}"></textarea>
                <button class="primary-btn" id="send" @disabled(! $shop->is_accepting_orders)><i class="ti ti-send"></i> {{ $shop->is_accepting_orders ? $copy['submit'] : $copy['restaurant_closed'] }}</button>
                <p class="message" id="message"></p>
                <section class="order-tracking" id="orderTracking" hidden aria-live="polite">
                    <div class="tracking-head">
                        <strong>{{ $copy['track_order'] }}</strong>
                        <span class="tracking-live">{{ $copy['live_update'] }}</span>
                    </div>
                    <div class="tracking-meta">
                        <span id="trackingNumber"></span>
                        <strong id="trackingStatus"></strong>
                    </div>
                    <div class="tracking-prep" id="trackingPrep" hidden>
                        <i class="ti ti-clock-hour-4"></i>
                        <span id="trackingPrepStatic">{{ $copy['estimated_prep'] }} <strong id="trackingMinutes"></strong> {{ $copy['minute'] }}</span>
                        <span id="trackingCountdown" class="tracking-countdown" hidden><span id="trackingCountdownLabel">{{ $copy['ready_in'] }}</span> <strong id="trackingCountdownValue" dir="ltr"></strong></span>
                    </div>
                    <ol class="tracking-steps">
                        <li class="tracking-step" data-tracking-step="1"><span><i class="ti ti-receipt"></i></span>{{ $copy['received'] }}</li>
                        <li class="tracking-step" data-tracking-step="2"><span><i class="ti ti-tools-kitchen-2"></i></span>{{ $copy['preparing'] }}</li>
                        <li class="tracking-step" data-tracking-step="3"><span><i class="ti ti-bell-check"></i></span>{{ $copy['ready'] }}</li>
                        <li class="tracking-step" data-tracking-step="4"><span><i class="ti ti-circle-check"></i></span>{{ $copy['completed'] }}</li>
                    </ol>
                    <p class="tracking-message" id="trackingMessage"></p>
                    <a class="tracking-link" id="trackingLink" href="#" target="_blank" rel="noopener">{{ $copy['full_tracking'] }}</a>
                </section>
            </aside>
        </div>
    </div>

    <div class="restaurant-bottom-actions">
    <button class="mobile-cart" id="mobileCart"><i class="ti ti-shopping-bag"></i> {{ $copy['view_order'] }} (<span
            id="mobileCount">0</span>) — <span id="mobileTotal">0.00</span> ₪</button>
    @include('front.partials.restaurant_purchase_wheel')
    </div>
    <dialog id="mealModal">
        <div class="modal-head">
            <h3 id="modalName"></h3><button class="close" id="modalClose"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body">
            <p class="modal-prep" id="modalPrep" hidden><i class="ti ti-clock"></i><span></span></p>
            <section class="option-section" id="sizesSection">
                <h4>{{ $copy['choose_size'] }}</h4>
                <div class="choices" id="sizes"></div>
            </section>
            <div id="mealChoices"></div>
            <p id="mealChoicesError" role="alert" hidden style="color:#ff9da9"></p>
            <section class="option-section" id="addonsSection">
                <h4>{{ $copy['addons'] }}</h4>
                <div class="choices" id="addons"></div>
            </section>
            <section class="option-section" id="excludedSection">
                <h4>{{ $copy['remove_ingredients'] }}</h4>
                <div class="choices" id="excluded"></div>
            </section>
            <div class="qty-row"><label>{{ $copy['quantity'] }}<input class="field" id="qty" type="number" min="1"
                        max="100" value="1"></label>
                <textarea class="field" id="notes" placeholder="{{ $copy['meal_notes'] }}"></textarea>
            </div>
            <div class="modal-actions"><button class="primary-btn" id="confirm" @disabled(! $shop->is_accepting_orders)><i
                        class="ti ti-shopping-bag-plus"></i> {{ $shop->is_accepting_orders ? $copy['add_to_order'] : $copy['restaurant_closed'] }}</button><button class="secondary"
                    id="modalCancel">{{ $copy['cancel'] }}</button></div>
        </div>
    </dialog>
    @include('front.shop_stories', ['showStoryList' => false])
    <style>.meal-choice-section .choice{margin:7px 0}.meal-choice-hint{color:#a9c2cb;font-size:12px;margin:5px 0}.meal-choice-section [data-count]{color:#08dcf4}.meal-choice-qty{width:76px!important;min-width:60px;text-align:center}.meal-choice-section[data-valid="false"]{border-color:#4e6171}</style>
    <script>{!! file_get_contents(base_path('public/restaurant-meal-choices.js')) !!}</script>
    @include('front.partials.coupon_field')
    @if($hasCustomerWelcome)
        @if($shop->slug === 'bankai-sushi')
            @include('front.partials.restaurant_notification_reward')
        @else
            @include('front.partials.restaurant_customer_welcome')
        @endif
        @include('front.partials.shop_signup_wheel')
    @endif
    <script>
        (() => {
            const products = new Map(@json($restaurantProducts).map(product => [Number(product.id), product]));
            const cart = [];
            let current = null;
            let trackingTimer = null;
            let trackingUrl = null;
            const trackingStorageKey = @json('ozman.restaurant.'.$shop->id.'.active-order');
            const ui = @json($copy);
            const $ = id => document.getElementById(id);
            const modal = $('mealModal');
            const parseOptions = values => Object.fromEntries((values || []).map(value => {
                const [name, price] = String(value).split(':', 2);
                return [name.trim(), Number(price) || 0];
            }).filter(([name]) => name));
            const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [char]));

            const displaySlider = document.querySelector('[data-restaurant-display]');
            const displaySlides = displaySlider ? [...displaySlider.querySelectorAll('.restaurant-display-slide')] : [];
            let displayIndex = Math.max(0, displaySlides.findIndex(slide => slide.classList.contains('active')));
            let displayTimer = null;
            let displayVisible = true;

            const pauseDisplaySlide = slide => {
                slide?.querySelectorAll('video').forEach(video => video.pause());
                slide?.querySelectorAll('iframe[src*="youtube.com/embed"]').forEach(frame => {
                    frame.contentWindow?.postMessage(JSON.stringify({ event: 'command', func: 'pauseVideo' }), '*');
                });
            };

            const playDisplaySlide = slide => {
                if (!displayVisible || document.hidden) return;
                slide?.querySelectorAll('video').forEach(video => video.play().catch(() => {}));
                slide?.querySelectorAll('iframe[src*="youtube.com/embed"]').forEach(frame => {
                    frame.contentWindow?.postMessage(JSON.stringify({ event: 'command', func: 'playVideo' }), '*');
                });
            };

            const activateDisplaySlide = nextIndex => {
                clearTimeout(displayTimer);
                displaySlides.forEach((slide, index) => {
                    const active = index === nextIndex;
                    slide.classList.toggle('active', active);
                    if (!active) pauseDisplaySlide(slide);
                });
                displayIndex = nextIndex;
                const activeSlide = displaySlides[displayIndex];
                const duration = Math.max(1000, Number(activeSlide?.dataset.displayDuration) || 8000);
                playDisplaySlide(activeSlide);
                if (displaySlides.length > 1 && displayVisible && !document.hidden) {
                    displayTimer = setTimeout(() => activateDisplaySlide((displayIndex + 1) % displaySlides.length), duration);
                }
            };

            if (displaySlides.length) {
                displaySlides.forEach(pauseDisplaySlide);
                displaySlides.forEach(slide => {
                    slide.querySelectorAll('iframe[src*="youtube.com/embed"]').forEach(frame => {
                        frame.addEventListener('load', () => {
                            if (displayVisible && slide.classList.contains('active') && !document.hidden) {
                                playDisplaySlide(slide);
                            } else {
                                pauseDisplaySlide(slide);
                            }
                        });
                    });
                });
                const displayObserver = new IntersectionObserver(([entry]) => {
                    displayVisible = entry.isIntersecting && entry.intersectionRatio > .08;
                    if (displayVisible) {
                        activateDisplaySlide(displayIndex);
                    } else {
                        clearTimeout(displayTimer);
                        displaySlides.forEach(pauseDisplaySlide);
                    }
                }, { threshold: [0, .08, .25, .5] });
                displayObserver.observe(displaySlider);
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        clearTimeout(displayTimer);
                        displaySlides.forEach(pauseDisplaySlide);
                    } else if (displayVisible) {
                        activateDisplaySlide(displayIndex);
                    }
                });
            }

            const categoryContent = $('categoryContent');
            const categoryBackground = $('categoryBackground');
            const categoryTabs = [...document.querySelectorAll('.category-tab')];
            const categoryPanes = [...document.querySelectorAll('[data-category-pane]')];
            let activeCategory = categoryTabs[0]?.dataset.categoryKey;

            const setCategoryBackground = pane => {
                if (!categoryBackground || !categoryContent) return;
                const source = pane?.dataset.backgroundVideo || '';
                const currentSource = categoryBackground.getAttribute('src') || '';

                if (!source) {
                    categoryContent.classList.remove('has-video');
                    categoryBackground.pause();
                    categoryBackground.removeAttribute('src');
                    categoryBackground.load();
                    return;
                }

                if (currentSource !== source) {
                    categoryContent.classList.remove('has-video');
                    categoryBackground.src = source;
                    categoryBackground.load();
                }
                categoryBackground.play().then(() => categoryContent.classList.add('has-video')).catch(() => {});
            };

            categoryBackground?.addEventListener('canplay', () => {
                categoryContent?.classList.add('has-video');
                categoryBackground.play().catch(() => {});
            });

            categoryBackground?.addEventListener('error', () => {
                const fallbackSource = categoryBackground.dataset.fallbackSrc || '';
                const currentSource = categoryBackground.getAttribute('src') || '';

                if (fallbackSource && currentSource !== fallbackSource) {
                    categoryContent?.classList.remove('has-video');
                    categoryBackground.src = fallbackSource;
                    categoryBackground.load();
                    categoryBackground.play().catch(() => {});
                    return;
                }

                categoryContent?.classList.remove('has-video');
            });

            const activateCategory = (key, revealTab = false) => {
                if (!key || key === activeCategory && !revealTab) return;
                activeCategory = key;
                categoryTabs.forEach(tab => {
                    const active = tab.dataset.categoryKey === key;
                    tab.classList.toggle('active', active);
                    tab.setAttribute('aria-pressed', String(active));
                    if (active && revealTab) tab.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
                categoryPanes.forEach(pane => pane.hidden = pane.dataset.categoryPane !== key);
                const activePane = categoryPanes.find(pane => pane.dataset.categoryPane === key);
                setCategoryBackground(activePane);
            };

            categoryTabs.forEach(tab => tab.addEventListener('click', () =>
                activateCategory(tab.dataset.categoryKey, true)));

            requestAnimationFrame(() => {
                const activePane = categoryPanes.find(pane => !pane.hidden);
                setCategoryBackground(activePane);
            });

            const openMeal = productId => {
                current = products.get(Number(productId));
                if (!current) return;
                $('modalName').textContent = current.name;
                $('modalPrep').hidden = !Number(current.preparation_time);
                $('modalPrep').querySelector('span').textContent = Number(current.preparation_time)
                    ? `${ui.estimated_prep} ${Number(current.preparation_time)} ${ui.minute}`
                    : '';
                $('qty').value = 1;
                window.OzmanMealChoices.render(current.choice_groups);
                $('notes').value = '';
                const sizes = parseOptions(current.sizes),
                    addons = parseOptions(current.addons);
                $('sizesSection').hidden = !Object.keys(sizes).length;
                $('addonsSection').hidden = !Object.keys(addons).length;
                $('excludedSection').hidden = !(current.ingredients || []).length;
                $('sizes').innerHTML = Object.entries(sizes).map(([name, price], index) =>
                    `<label class="choice"><span><input type="radio" name="meal_size" value="${escapeHtml(name)}" data-price="${price}" ${index===0?'checked':''}> ${escapeHtml(name)}</span><span class="choice-price">${price.toFixed(2)} ₪</span></label>`
                    ).join('');
                $('addons').innerHTML = Object.entries(addons).map(([name, price]) =>
                    `<label class="choice"><span><input type="checkbox" value="${escapeHtml(name)}" data-price="${price}"> ${escapeHtml(name)}</span><span class="choice-price">+${price.toFixed(2)} ₪</span></label>`
                    ).join('');
                $('excluded').innerHTML = (current.ingredients || []).map(name =>
                    `<label class="choice"><span><input type="checkbox" value="${escapeHtml(name)}"> ${escapeHtml(ui.without)} ${escapeHtml(name)}</span></label>`
                    ).join('');
                modal.showModal();
            };

            document.querySelectorAll('.meal[data-product-id]').forEach(card => {
                card.addEventListener('click', event => {
                    if (event.target.closest('.oz-actions')) return;
                    openMeal(card.dataset.productId);
                });
                card.addEventListener('keydown', event => {
                    if (event.target.closest('.oz-actions')) return;
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openMeal(card.dataset.productId);
                    }
                });
            });
            const closeModal = () => modal.close();
            $('modalClose').onclick = closeModal;
            $('modalCancel').onclick = closeModal;
            let restaurantCartPopTimer;
            const popRestaurantCart = () => {
                const target = $('mobileCart');
                clearTimeout(restaurantCartPopTimer);
                target.classList.remove('cart-flight-pop');
                void target.offsetWidth;
                target.classList.add('cart-flight-pop');
                restaurantCartPopTimer = setTimeout(() => target.classList.remove('cart-flight-pop'), 950);
            };
            const flyRestaurantMealToCart = productId => {
                const target = $('mobileCart');
                const source = document.querySelector('.meal[data-product-id="' + productId + '"] .meal-image');
                if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches || !source) {
                    popRestaurantCart();
                    return;
                }
                const from = source.getBoundingClientRect(), to = target.getBoundingClientRect();
                const size = Math.min(68, Math.max(46, Math.min(from.width, from.height) * .34));
                const flight = document.createElement('div');
                flight.className = 'restaurant-cart-flight';
                flight.style.width = size + 'px';
                flight.style.height = size + 'px';
                flight.style.left = (from.left + (from.width - size) / 2) + 'px';
                flight.style.top = (from.top + (from.height - size) / 2) + 'px';
                const media = document.createElement('span');
                media.className = 'restaurant-cart-flight-media';
                const image = source.tagName === 'IMG' ? source : source.querySelector('img');
                if (image?.currentSrc || image?.src) {
                    const clone = document.createElement('img');
                    clone.src = image.currentSrc || image.src;
                    clone.alt = '';
                    media.append(clone);
                } else {
                    media.innerHTML = '<i class="ti ti-tools-kitchen-2"></i>';
                }
                const stars = document.createElement('span');
                stars.className = 'restaurant-cart-flight-stars';
                stars.innerHTML = '<b>✦</b><b>✧</b><b>✦</b>';
                flight.append(media, stars);
                document.body.append(flight);
                const x = to.left + to.width / 2 - (from.left + from.width / 2);
                const y = to.top + to.height / 2 - (from.top + from.height / 2);
                const distance = Math.hypot(x, y) || 1;
                stars.style.setProperty('--trail-x', (-x / distance * 56) + 'px');
                stars.style.setProperty('--trail-y', (-y / distance * 56) + 'px');
                flight.animate([
                    {transform:'translate(0,0) scale(1)',opacity:1},
                    {transform:'translate(' + (x * .35) + 'px,' + (y * .18 - 74) + 'px) scale(.82) rotate(-9deg)',opacity:1,offset:.42},
                    {transform:'translate(' + x + 'px,' + y + 'px) scale(.16) rotate(12deg)',opacity:.22}
                ], {duration:1100,easing:'cubic-bezier(.16,.8,.24,1)',fill:'forwards'}).finished.catch(() => {}).finally(() => {
                    flight.remove();
                    popRestaurantCart();
                });
            };
            $('confirm').onclick = () => {
                const mealChoices=window.OzmanMealChoices.selection();
                if (!mealChoices) return;
                const selectedSize = $('sizes').querySelector(':checked');
                const selectedAddons = [...$('addons').querySelectorAll(':checked')];
                const unit = selectedSize ? Number(selectedSize.dataset.price) : Number(current.price);
                const addonTotal = selectedAddons.reduce((sum, input) => sum + Number(input.dataset.price), 0);
                const line = {
                    product_id: current.id,
                    name: current.name,
                    qty: Math.max(1, Number($('qty').value) || 1),
                    size: selectedSize?.value || null,
                    addons: selectedAddons.map(input => input.value),
                    excluded: [...$('excluded').querySelectorAll(':checked')].map(input => input.value),
                    notes: $('notes').value,
                    unit: unit + addonTotal + mealChoices.extra,
                    choices: mealChoices.values,
                    choice_summary: mealChoices.summary,
                    preparation_time: Number(current.preparation_time) || 0
                };
                // The same meal with the same options adds to the existing line instead of a new row.
                const sameOptions = item => item.product_id === line.product_id && item.size === line.size && (item.notes || '') === (line.notes || '')
                    && JSON.stringify([...item.addons].sort()) === JSON.stringify([...line.addons].sort())
                    && JSON.stringify([...item.excluded].sort()) === JSON.stringify([...line.excluded].sort())
                    && JSON.stringify(item.choices || []) === JSON.stringify(line.choices || []);
                const existing = cart.find(sameOptions);
                if (existing) existing.qty = Math.min(100, existing.qty + line.qty); else cart.push(line);
                closeModal();
                render();
                flyRestaurantMealToCart(current.id);
            };
            window.removeRestaurantCartItem = index => {
                cart.splice(index, 1);
                render();
            };

            function render() {
                $('cartItems').innerHTML = cart.length ? cart.map((item, index) =>
                    `<article class="cart-item"><div class="cart-item-head"><strong>${item.qty}× ${escapeHtml(item.name)}</strong><button class="remove" onclick="removeRestaurantCartItem(${index})"><i class="ti ti-trash"></i></button></div><small>${escapeHtml(item.size||'')} ${item.addons.length?'• '+escapeHtml(item.addons.join('، ')):''}${item.preparation_time?' • '+escapeHtml(ui.about)+' '+item.preparation_time+' '+escapeHtml(ui.minute):''}</small><p>${escapeHtml(item.choice_summary||'')}</p><span class="price">${(item.unit*item.qty).toFixed(2)} ₪</span></article>`
                    ).join('') : `<div class="empty"><i class="ti ti-shopping-bag"></i>${escapeHtml(ui.empty_cart)}</div>`;
                const total = cart.reduce((sum, item) => sum + item.unit * item.qty, 0);
                const signupDiscount = window.OzmanSignupReward?.discount(total) || 0;
                if ($('signupGiftLine')) {
                    const gift = window.OzmanSignupReward?.gift();
                    $('signupGiftLine').hidden = !gift;
                    $('signupCartGiftLabel').textContent = gift ? @json(match ($locale) {'en' => 'Gift with your first order: ', 'he' => 'מתנה להזמנה הראשונה: ', default => 'هدية مع أول طلب: '}) + gift.label : '';
                    $('signupCartGiftImage').hidden = !gift?.gift_image;
                    if (gift?.gift_image) {$('signupCartGiftImage').src = gift.gift_image;$('signupCartGiftImage').alt = gift.label;}
                }
                $('total').textContent = $('mobileTotal').textContent = Math.max(0, total - signupDiscount).toFixed(2);
                if ($('signupDiscountLine')) {
                    $('signupDiscountLine').hidden = signupDiscount <= 0;
                    $('signupDiscountLine').textContent = (window.OzmanSignupReward?.discountLabel || '') + ' −' + signupDiscount.toFixed(2) + ' ₪';
                }
                $('cartCount').textContent = $('mobileCount').textContent = cart.reduce((sum, item) => sum + item.qty,
                    0);
            }
            const setCartOpen = open => {
                document.body.classList.toggle('cart-open', open);
                $('cartPanel').setAttribute('aria-hidden', String(!open));
            };
            $('mobileCart').onclick = () => setCartOpen(true);
            $('cartClose').onclick = () => setCartOpen(false);
            $('cartBackdrop').onclick = () => setCartOpen(false);
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && document.body.classList.contains('cart-open')) {
                    setCartOpen(false);
                }
            });
            const type = $('type'),
                address = $('address'),
                locationBox = $('locationBox');
            type?.addEventListener('change', () => {
                if (address && address.type !== 'hidden') {
                    const delivery = type.value === 'delivery';
                    address.hidden = !delivery;
                    address.required = delivery;
                    if (locationBox) locationBox.hidden = !delivery
                }
            });
            type?.dispatchEvent(new Event('change'));
            $('detectLocation')?.addEventListener('click', () => {
                const status = $('locationStatus');
                if (!navigator.geolocation) {
                    status.textContent = ui.geo_unsupported;
                    status.classList.remove('ready');
                    return
                }
                status.textContent = ui.locating;
                status.classList.remove('ready');
                navigator.geolocation.getCurrentPosition(position => {
                    $('latitude').value = position.coords.latitude.toFixed(7);
                    $('longitude').value = position.coords.longitude.toFixed(7);
                    status.innerHTML =
                        `<i class="ti ti-circle-check"></i> ${escapeHtml(ui.location_success)} — <a style="color:var(--cyan)" target="_blank" href="https://www.google.com/maps?q=${$('latitude').value},${$('longitude').value}">${escapeHtml(ui.open_location)}</a>`;
                    status.classList.add('ready');
                    document.dispatchEvent(new Event('restaurant:location-selected'));
                }, () => {
                    status.textContent = ui.location_failed;
                    status.classList.remove('ready')
                }, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0
                });
            });

            // Live countdown to when the kitchen expects the order ready (same moment as on the restaurant's screen).
            let trackingDue = null, clockOffset = 0, countdownTimer = null;
            const tickTrackingCountdown = () => {
                const box = $('trackingCountdown'), fixed = $('trackingPrepStatic');
                if (!box || !fixed) return;
                box.hidden = !trackingDue; fixed.hidden = Boolean(trackingDue);
                if (!trackingDue) { clearInterval(countdownTimer); countdownTimer = null; return; }
                if (!countdownTimer) countdownTimer = setInterval(tickTrackingCountdown, 1000);
                const left = Math.round((trackingDue - (Date.now() + clockOffset)) / 1000);
                $('trackingCountdownLabel').textContent = left > 0 ? ui.ready_in : ui.almost_ready;
                $('trackingCountdownValue').textContent = left > 0 ? `${String(Math.floor(left / 60)).padStart(2, '0')}:${String(left % 60).padStart(2, '0')}` : '';
            };

            const renderTracking = tracking => {
                if (!tracking) return;
                const box = $('orderTracking');
                const step = Number(tracking.step) || 0;
                box.hidden = false;
                box.classList.toggle('cancelled', Boolean(tracking.is_cancelled));
                $('trackingNumber').textContent = `${ui.order_number} ${tracking.order_number}`;
                $('trackingStatus').textContent = tracking.status_label;
                $('trackingMessage').textContent = tracking.status_message;
                $('trackingPrep').hidden = !Number(tracking.estimated_preparation_minutes) || tracking.is_cancelled;
                $('trackingMinutes').textContent = Number(tracking.estimated_preparation_minutes) || '';
                trackingDue = tracking.preparation_due_at && !tracking.is_cancelled ? Date.parse(tracking.preparation_due_at) : null;
                if (tracking.server_time) clockOffset = Date.parse(tracking.server_time) - Date.now();
                tickTrackingCountdown();
                document.querySelectorAll('[data-tracking-step]').forEach(item => {
                    const itemStep = Number(item.dataset.trackingStep);
                    item.classList.toggle('done', !tracking.is_cancelled && itemStep < step);
                    item.classList.toggle('active', !tracking.is_cancelled && itemStep === step);
                });
            };

            const pollTracking = async () => {
                if (!trackingUrl) return;
                try {
                    const response = await fetch(trackingUrl, {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });
                    if (!response.ok) throw new Error('tracking failed');
                    const data = await response.json();
                    renderTracking(data.tracking);
                    if (['completed', 'cancelled'].includes(data.tracking?.status)) {
                        clearInterval(trackingTimer);
                        trackingTimer = null;
                    }
                } catch (_) {}
            };

            const activateTracking = data => {
                trackingUrl = data.tracking_url;
                $('trackingLink').href = trackingUrl;
                renderTracking(data.tracking);
                try {
                    localStorage.setItem(trackingStorageKey, JSON.stringify({ url: trackingUrl }));
                } catch (_) {}
                clearInterval(trackingTimer);
                trackingTimer = setInterval(pollTracking, 6000);
            };

            try {
                const savedTracking = JSON.parse(localStorage.getItem(trackingStorageKey) || 'null');
                if (savedTracking?.url) {
                    trackingUrl = savedTracking.url;
                    $('trackingLink').href = trackingUrl;
                    pollTracking();
                    trackingTimer = setInterval(pollTracking, 6000);
                }
            } catch (_) {}

            let sendingOrder = false;
            window.OzmanCoupon?.mount($('send'));
            $('send').onclick = async () => {
                if (sendingOrder) return;
                const message = $('message');
                message.className = 'message';
                message.textContent = '';
                if (!cart.length) {
                    message.classList.add('error');
                    message.textContent = ui.add_meal_error;
                    return
                }
                if (type.value === 'delivery' && !address.value.trim()) {
                    message.classList.add('error');
                    message.textContent = ui.address_required;
                    address.focus();
                    return
                }
                sendingOrder = true;
                $('send').disabled = true;
                try {
                if (window.OzmanSignupReward && !await window.OzmanSignupReward.beforeOrder()) return;
                const payload = {
                    registration_token: window.OzmanRestaurantCustomer?.token() || null,
                    order_type: type.value,
                    table_code: @json($table?->code),
                    customer_name: $('name').value,
                    customer_phone: $('phone').value,
                    customer_address: address.value,
                    latitude: $('latitude').value || null,
                    longitude: $('longitude').value || null,
                    customer_notes: $('orderNotes').value,
                    coupon_code: window.OzmanCoupon?.code() || null,
                    items: cart
                };
                try {
                    const sendOrder = () => fetch(@json(route('restaurant.orders.store', $shop)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    let response = await sendOrder();
                    let data = await response.json();
                    if (response.status === 422 && data.errors?.signup_reward_used && window.confirm(data.errors.signup_reward_used.join(' '))) {
                        payload.skip_signup_reward = true;
                        response = await sendOrder();data = await response.json();
                    }
                    if (!response.ok) {
                        const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                        throw new Error(errors || data.message || 'تحقق من البيانات')
                    }
                    window.OzmanSignupReward?.consumed();
                    cart.length = 0;
                    render();
                    message.classList.add('success');
                    message.textContent = `${ui.sent_success} ${data.order_number}`
                        + (data.coupon_discount > 0 ? ` · ${@json(__('كود الخصم'))} -${Number(data.coupon_discount).toFixed(2)} ₪` : '')
                        + (data.coupon_error ? ` · ${data.coupon_error}` : '');
                    window.OzmanCoupon?.reset();
                    activateTracking(data);
                    if (window.RestaurantPurchaseWheel?.ordered(data)) {
                        // The wheel keeps the WhatsApp handoff available after spinning or skipping.
                    } else if (data.whatsapp_url) {
                        // Move to the restaurant chat after the order has safely been recorded.
                        // WhatsApp opens the fully prepared message for the customer to review and send.
                        setTimeout(() => window.location.assign(data.whatsapp_url), 700);
                    }
                } catch (error) {
                    message.classList.add('error');
                    message.textContent = error.message || ui.send_error
                }
                } finally {
                    sendingOrder = false;
                    $('send').disabled = @json(! $shop->is_accepting_orders);
                }
            };
            document.addEventListener('restaurant:reward-changed', render);
            render();
        })();
    </script>
@include('front.partials.shop_bottom_screens')
@include('front.partials.page_share', ['title' => $shop->name, 'url' => $shop->publicUrl()])
</body>

</html>
