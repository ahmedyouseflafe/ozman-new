@php
    $locale = app()->getLocale();
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
            'address' => 'عنوان التوصيل بالتفصيل', 'locate_me' => 'حدد موقعي للتوصيل', 'location_required' => 'يجب تحديد موقعك على الخريطة لإرسال طلب التوصيل.',
            'notes' => 'ملاحظات عامة للمطعم', 'submit' => 'تأكيد وإرسال الطلب', 'track_order' => 'تتبّع طلبك', 'live_update' => 'تحديث مباشر',
            'estimated_prep' => 'مدة التجهيز المتوقعة:', 'received' => 'تم الاستلام', 'preparing' => 'قيد التحضير', 'ready' => 'جاهز',
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
            'address' => 'כתובת מלאה למשלוח', 'locate_me' => 'איתור המיקום למשלוח', 'location_required' => 'יש לסמן את המיקום במפה כדי לשלוח את ההזמנה.',
            'notes' => 'הערות כלליות למסעדה', 'submit' => 'אישור ושליחת ההזמנה', 'track_order' => 'מעקב הזמנה', 'live_update' => 'עדכון חי',
            'estimated_prep' => 'זמן הכנה משוער:', 'received' => 'התקבלה', 'preparing' => 'בהכנה', 'ready' => 'מוכנה',
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
            'address' => 'Full delivery address', 'locate_me' => 'Set my delivery location', 'location_required' => 'Set your location on the map to send a delivery order.',
            'notes' => 'Notes for the restaurant', 'submit' => 'Confirm and send order', 'track_order' => 'Track your order', 'live_update' => 'Live update',
            'estimated_prep' => 'Estimated preparation time:', 'received' => 'Received', 'preparing' => 'Preparing', 'ready' => 'Ready',
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root {
            --cyan: #08def4;
            --cyan-soft: rgba(8, 222, 244, .11);
            --green: #27dd86;
            --red: #ff6678;
            --bg: #05070a;
            --card: #10151a;
            --card2: #0a0e12;
            --border: rgba(150, 174, 190, .18);
            --muted: #9ca9b4
        }

        * {
            box-sizing: border-box
        }

        html {
            scroll-behavior: smooth
        }

        body {
            margin: 0;
            background: radial-gradient(circle at 85% 5%, rgba(8, 222, 244, .11), transparent 27%), radial-gradient(circle at 8% 30%, rgba(101, 42, 255, .1), transparent 25%), var(--bg);
            color: #fff;
            font-family: "Cairo", Arial, sans-serif
        }

        button,
        input,
        select,
        textarea {
            font: inherit
        }

        .shell {
            width: min(1380px, calc(100% - 32px));
            margin: auto;
            padding: 20px 0 55px
        }

        .hero {
            position: relative;
            overflow: hidden;
            min-height: 310px;
            border: 1px solid var(--border);
            border-radius: 28px;
            background: linear-gradient(110deg, rgba(15, 18, 28, .96), rgba(5, 25, 28, .9));
            padding: 30px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .28)
        }

        .hero-tools {
            position: absolute;
            z-index: 4;
            top: 18px;
            right: 20px;
            left: auto
        }

        .public-language-switcher {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px;
            border: 1px solid rgba(8, 222, 244, .24);
            border-radius: 13px;
            background: rgba(3, 10, 14, .86);
            box-shadow: 0 8px 28px rgba(0, 0, 0, .24);
            backdrop-filter: blur(12px)
        }

        .public-language-switcher > i {
            margin-inline: 5px 2px;
            color: var(--cyan)
        }

        .public-language-switcher a {
            padding: 6px 8px;
            border-radius: 9px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-decoration: none
        }

        .public-language-switcher a.active {
            background: var(--cyan);
            color: #001114
        }

        .hero:after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: rgba(8, 222, 244, .12);
            filter: blur(70px);
            z-index: 1;
            pointer-events: none;
            left: -100px;
            top: -180px
        }

        .brand {
            display: flex;
            align-items: flex-end;
            gap: 28px;
            position: absolute;
            z-index: 2;
            right: 30px;
            bottom: 24px;
            direction: rtl
        }

        .restaurant-logo-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px
        }

        .logo {
            width: 166px;
            height: 166px;
            border-radius: 34px;
            object-fit: cover;
            border: 2px solid var(--cyan);
            box-shadow: 0 0 28px rgba(8, 222, 244, .3)
        }

        .restaurant-story-avatar {
            position: relative;
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 9px;
            padding: 0;
            border: 0;
            border-radius: 28px;
            background: transparent;
            color: #fff
        }

        .restaurant-story-avatar:disabled {
            cursor: default
        }

        .restaurant-story-logo-frame,
        .restaurant-story-avatar .logo {
            display: block
        }

        .restaurant-story-avatar.has-shop-story {
            cursor: pointer
        }

        .restaurant-story-avatar.has-shop-story .restaurant-story-logo-frame {
            padding: 4px;
            border-radius: 32px;
            background: linear-gradient(145deg, #08def4, #27dd86, #7868ff);
            box-shadow: 0 0 0 4px rgba(8, 222, 244, .1), 0 0 30px rgba(8, 222, 244, .42)
        }

        .restaurant-story-avatar.has-shop-story .logo {
            border-color: #06151a;
            box-shadow: none
        }

        .restaurant-story-avatar.has-shop-story.seen .restaurant-story-logo-frame {
            background: #66777d;
            box-shadow: 0 0 0 4px rgba(126, 144, 149, .1)
        }

        .restaurant-story-live {
            display: none;
            width: 190px;
            max-width: 100%;
            color: var(--cyan);
            font-size: 11px;
            font-weight: 900;
            line-height: 1.5;
            text-align: center;
            text-wrap: balance
        }

        .restaurant-story-avatar.has-shop-story .restaurant-story-live {
            display: block
        }

        .restaurant-story-avatar.has-shop-story:focus-visible {
            outline: 3px solid #fff;
            outline-offset: 5px
        }

        .brand-kicker {
            color: var(--cyan);
            font-size: 13px;
            font-weight: 900
        }

        .hero h1 {
            font-size: clamp(36px, 5vw, 60px);
            margin: 0 0 42px
        }

        .hero p {
            color: var(--muted);
            margin: 0;
            font-size: 16px;
            font-weight: 600
        }

        .service-badge {
            position: absolute;
            z-index: 2;
            left: 30px;
            bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 17px;
            border-radius: 16px;
            background: var(--cyan-soft);
            border: 1px solid rgba(8, 222, 244, .25);
            color: var(--cyan);
            font-weight: 800
        }

        .restaurant-availability {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            width: fit-content;
            margin-top: 0;
            padding: 8px 15px;
            border: 1px solid currentColor;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 900
        }

        .restaurant-availability i {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 12px currentColor
        }

        .restaurant-availability.is-open {
            color: var(--green);
            background: rgba(39, 221, 134, .09)
        }

        .restaurant-availability.is-closed {
            color: var(--red);
            background: rgba(255, 102, 120, .1)
        }

        .restaurant-closed-notice {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-top: 16px;
            padding: 16px 18px;
            border: 1px solid rgba(255, 102, 120, .38);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255, 102, 120, .14), rgba(16, 20, 25, .95));
            color: #fff
        }

        .restaurant-closed-notice > i {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            width: 45px;
            height: 45px;
            border-radius: 14px;
            background: rgba(255, 102, 120, .14);
            color: var(--red);
            font-size: 24px
        }

        .restaurant-closed-notice strong,
        .restaurant-closed-notice span {
            display: block
        }

        .restaurant-closed-notice span {
            margin-top: 2px;
            color: var(--muted);
            font-size: 12px
        }

        .layout {
            display: block;
            margin-top: 22px
        }

        .menu-panel,
        .cart {
            border: 1px solid var(--border);
            border-radius: 25px;
            background: linear-gradient(145deg, rgba(17, 21, 27, .94), rgba(8, 13, 16, .94));
            padding: 22px
        }

        .menu-panel {
            min-height: calc(100vh - 315px)
        }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px
        }

        .section-head h2 {
            font-size: 26px;
            margin: 0
        }

        .section-head p {
            color: var(--muted);
            font-size: 13px;
            margin: 2px 0
        }

        .section-head > .count {
            display: none
        }

        .count {
            display: inline-grid;
            place-items: center;
            min-width: 43px;
            height: 43px;
            border-radius: 14px;
            background: var(--cyan-soft);
            border: 1px solid rgba(8, 222, 244, .25);
            color: var(--cyan);
            font-weight: 900
        }

        .category {
            margin-bottom: 27px
        }

        .menu-browser {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 140px;
            gap: 4px;
            align-items: start;
            direction: ltr;
            min-height: 520px
        }

        .category-rail {
            grid-column: 2;
            direction: rtl;
            position: sticky;
            top: 18px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 14px;
            height: min(720px, calc(100vh - 70px));
            overflow-y: auto;
            overscroll-behavior: contain;
            scroll-snap-type: y mandatory;
            scrollbar-width: none;
            padding: 0;
            background: transparent
        }

        .category-content {
            grid-column: 1;
            grid-row: 1;
            direction: rtl;
            position: relative;
            isolation: isolate;
            min-width: 0;
            min-height: 520px;
            overflow: clip;
            border-radius: 22px
        }

        .category-background-stage {
            position: absolute;
            z-index: -2;
            inset: 0;
            pointer-events: none
        }

        .category-background {
            position: sticky;
            top: 12px;
            display: block;
            width: 100%;
            height: min(760px, calc(100svh - 24px));
            min-height: 520px;
            object-fit: cover;
            object-position: center center;
            opacity: 0;
            filter: saturate(1.08) contrast(1.08);
            transform: scale(1.015);
            transition: opacity .5s ease
        }

        .category-content.has-video .category-background {
            opacity: .52
        }

        .category-content::after {
            content: "";
            position: absolute;
            z-index: -1;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(90deg, rgba(5, 9, 12, .66), rgba(5, 9, 12, .36) 50%, rgba(5, 9, 12, .7))
        }

        .category-content.has-video .meal {
            background: linear-gradient(155deg, rgba(20, 27, 33, .93), rgba(7, 11, 15, .94));
            backdrop-filter: blur(3px)
        }

        .category-rail::before,
        .category-rail::after {
            content: "";
            flex: 0 0 calc(50% - 58px)
        }

        .category-rail::-webkit-scrollbar {
            display: none
        }

        .category-tab {
            flex: 0 0 auto;
            display: grid;
            justify-items: center;
            width: 100%;
            justify-self: stretch;
            scroll-snap-align: center;
            border: 0;
            background: transparent;
            color: var(--muted);
            padding: 8px 5px 10px;
            cursor: pointer;
            transform: translateX(var(--arc-x, 0px)) scale(var(--arc-scale, .88));
            opacity: var(--arc-opacity, .62);
            transition: transform .2s ease, opacity .2s ease, color .2s ease, filter .2s ease;
            will-change: transform
        }

        .category-tab img,
        .category-tab-icon {
            display: grid;
            place-items: center;
            width: 68px;
            height: 68px;
            margin: 0;
            justify-self: center;
            border-radius: 50%;
            object-fit: cover;
            background: #161d23;
            border: 3px solid rgba(255, 255, 255, .08);
            font-size: 29px;
            transition: .24s
        }

        .category-tab span:last-child {
            display: block;
            width: 68px;
            justify-self: center;
            text-align: center;
            margin-top: 5px;
            font-size: 11px;
            line-height: 1.45;
            font-weight: 800
        }

        .category-tab.active {
            color: #fff;
            background: transparent;
            opacity: 1;
            filter: brightness(1.16);
            z-index: 4
        }

        .category-tab.active img,
        .category-tab.active .category-tab-icon {
            border-color: var(--cyan);
            box-shadow: 0 0 0 5px rgba(8, 222, 244, .14), 0 0 34px rgba(8, 222, 244, .7)
        }

        .category-pane {
            animation: categoryReveal .28s ease
        }

        .category-pane[hidden] {
            display: none
        }

        @keyframes categoryReveal {
            from { opacity: 0; transform: translateY(10px) }
            to { opacity: 1; transform: translateY(0) }
        }

        .category-title {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 19px;
            margin: 0 0 13px
        }

        .category-title:before {
            content: "";
            width: 5px;
            height: 24px;
            border-radius: 10px;
            background: var(--cyan);
            box-shadow: 0 0 12px var(--cyan)
        }

        .meals {
            position: relative;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(205px, 1fr));
            gap: 18px;
            align-items: stretch;
            padding: 8px 4px 22px
        }

        .meal {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            width: 100%;
            min-width: 0;
            overflow: hidden;
            border: 1px solid rgba(150, 174, 190, .2);
            border-radius: 18px;
            background: linear-gradient(155deg, rgba(20, 27, 33, .98), rgba(7, 11, 15, .98));
            padding: 7px;
            box-shadow: 0 14px 34px rgba(0, 0, 0, .28);
            transition: transform .24s ease, border-color .24s ease, box-shadow .24s ease
        }

        .meal[data-product-id] {
            cursor: pointer
        }

        .meal:focus-visible {
            outline: 3px solid rgba(8, 222, 244, .55);
            outline-offset: 3px
        }

        .meal:hover {
            transform: translateY(-5px);
            border-color: rgba(8, 222, 244, .65);
            box-shadow: 0 18px 42px rgba(0, 0, 0, .42), 0 0 22px rgba(8, 222, 244, .13)
        }

        .meal-image {
            display: block;
            width: 100%;
            height: auto;
            aspect-ratio: 1 / 1;
            border-radius: 13px;
            object-fit: cover;
            background: #050709;
            border: 1px solid rgba(8, 222, 244, .24);
            transition: transform .3s ease, filter .3s ease
        }

        .meal:hover .meal-image,
        .meal:focus-visible .meal-image {
            transform: scale(1.025);
            filter: brightness(1.06)
        }

        .meal-body {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            flex: 1;
            width: 100%;
            min-width: 0;
            padding: 10px 6px 5px
        }

        .meal h3 {
            width: 100%;
            min-height: 0;
            margin: 0 0 5px;
            padding: 0;
            border: 0;
            background: transparent;
            font-size: 15px;
            line-height: 1.5;
            text-align: start;
            box-shadow: none
        }

        .meal-desc {
            display: -webkit-box;
            overflow: hidden;
            min-height: 38px;
            margin: 0 0 8px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.65;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2
        }

        .meal-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: auto;
            padding-top: 8px;
            border-top: 1px solid rgba(150, 174, 190, .12)
        }

        .price {
            color: var(--cyan);
            font-size: 14px;
            font-weight: 900
        }

        .meal .add-btn {
            position: static;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: auto;
            height: 34px;
            padding: 0 10px;
            border: 0;
            border-radius: 10px;
            background: var(--cyan);
            color: #001114;
            font-size: 11px;
            box-shadow: none
        }

        .meal .add-btn i {
            font-size: 20px
        }

        .meal .add-btn:hover {
            filter: brightness(1.08);
            transform: translateY(-1px)
        }

        .add-btn,
        .primary-btn {
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #09dcec, #1cc0ef);
            color: #001114;
            font-weight: 900;
            padding: 9px 13px;
            cursor: pointer
        }

        .add-btn:hover,
        .primary-btn:hover {
            filter: brightness(1.08)
        }

        .add-btn:disabled,
        .primary-btn:disabled {
            cursor: not-allowed;
            filter: grayscale(.7);
            opacity: .52
        }

        .cart {
            position: fixed;
            z-index: 40;
            top: 18px;
            bottom: 18px;
            left: 18px;
            width: min(390px, calc(100% - 36px));
            overflow-y: auto;
            transform: translateX(calc(-100% - 30px));
            transition: transform .32s ease;
            box-shadow: 20px 0 80px rgba(0, 0, 0, .55)
        }

        body.cart-open {
            overflow: hidden
        }

        body.cart-open .cart {
            transform: translateX(0)
        }

        .cart-backdrop {
            position: fixed;
            z-index: 35;
            inset: 0;
            border: 0;
            background: rgba(0, 3, 6, .74);
            backdrop-filter: blur(5px);
            opacity: 0;
            visibility: hidden;
            transition: .25s
        }

        body.cart-open .cart-backdrop {
            opacity: 1;
            visibility: visible
        }

        .cart-close {
            width: 40px;
            height: 40px;
            border: 1px solid var(--border);
            border-radius: 50%;
            background: #070b0f;
            color: #fff;
            cursor: pointer
        }

        .cart-title {
            display: flex;
            align-items: center;
            justify-content: space-between
        }

        .cart h2 {
            margin: 0
        }

        .cart-items {
            max-height: 310px;
            overflow: auto;
            margin: 15px 0
        }

        .empty {
            text-align: center;
            color: var(--muted);
            padding: 28px 10px
        }

        .empty i {
            display: block;
            font-size: 42px;
            color: #52606c;
            margin-bottom: 7px
        }

        .cart-item {
            border: 1px solid var(--border);
            background: rgba(3, 7, 10, .65);
            border-radius: 14px;
            padding: 11px;
            margin-bottom: 9px
        }

        .cart-item-head {
            display: flex;
            justify-content: space-between;
            gap: 8px
        }

        .cart-item strong {
            font-size: 14px
        }

        .cart-item small {
            display: block;
            color: var(--muted);
            line-height: 1.7
        }

        .remove {
            border: 0;
            background: transparent;
            color: var(--red);
            cursor: pointer
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            margin-bottom: 13px
        }

        .cart-total strong {
            color: var(--cyan);
            font-size: 23px
        }

        .table-context {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 12px;
            padding: 13px 14px;
            border: 1px solid rgba(8, 222, 244, .36);
            border-radius: 14px;
            background: var(--cyan-soft);
            color: #fff
        }

        .table-context i {
            color: var(--cyan);
            font-size: 24px
        }

        .table-context small {
            display: block;
            color: var(--muted);
            font-size: 11px
        }

        .table-context strong {
            display: block;
            color: var(--cyan);
            font-size: 16px
        }

        .field {
            width: 100%;
            min-height: 47px;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: #070b0f;
            color: #fff;
            padding: 10px 13px;
            outline: 0;
            margin-bottom: 9px
        }

        .field:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(8, 222, 244, .08)
        }

        textarea.field {
            resize: vertical;
            min-height: 75px
        }

        .fields-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px
        }

        .location-box {
            border: 1px solid rgba(8, 222, 244, .24);
            border-radius: 15px;
            background: var(--cyan-soft);
            padding: 11px;
            margin-bottom: 9px
        }

        .location-btn {
            width: 100%;
            border: 1px solid rgba(8, 222, 244, .35);
            border-radius: 12px;
            background: #07151a;
            color: var(--cyan);
            padding: 10px;
            font-weight: 900;
            cursor: pointer
        }

        .location-status {
            color: var(--muted);
            font-size: 12px;
            margin: 7px 2px 0
        }

        .location-status.ready {
            color: var(--green)
        }

        .primary-btn {
            width: 100%;
            min-height: 50px;
            font-size: 15px
        }

        .message {
            font-size: 13px;
            line-height: 1.7;
            margin: 10px 0 0
        }

        .message.error {
            color: #ff9eaa
        }

        .message.success {
            color: #6df0ab
        }

        .meal-prep {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            width: fit-content;
            margin: 1px 0 9px;
            color: #b8f8ff;
            font-size: 11px;
            font-weight: 800
        }

        .modal-prep {
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 0 0 16px;
            padding: 10px 12px;
            border: 1px solid rgba(8, 222, 244, .2);
            border-radius: 12px;
            background: rgba(8, 222, 244, .07);
            color: #b8f8ff;
            font-size: 13px
        }

        .order-tracking {
            margin-top: 14px;
            padding: 15px;
            border: 1px solid rgba(8, 222, 244, .34);
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(8, 222, 244, .1), rgba(104, 61, 255, .08));
            box-shadow: inset 0 1px rgba(255, 255, 255, .03)
        }

        .order-tracking.cancelled {
            border-color: rgba(255, 86, 107, .45);
            background: rgba(255, 86, 107, .08)
        }

        .tracking-head,
        .tracking-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px
        }

        .tracking-head strong {
            font-size: 16px
        }

        .tracking-live {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #6df0ab;
            font-size: 11px;
            font-weight: 800
        }

        .tracking-live::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 10px currentColor
        }

        .tracking-meta {
            margin-top: 9px;
            color: var(--muted);
            font-size: 11px
        }

        .tracking-prep {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 0;
            padding: 10px 11px;
            border-radius: 12px;
            background: #07151a;
            color: #d9fbff;
            font-size: 12px
        }

        .tracking-prep i {
            color: var(--cyan);
            font-size: 18px
        }

        .tracking-steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
            margin: 14px 0 12px;
            padding: 0;
            list-style: none
        }

        .tracking-step {
            position: relative;
            display: grid;
            justify-items: center;
            gap: 6px;
            color: #687680;
            text-align: center;
            font-size: 9px;
            font-weight: 800
        }

        .tracking-step:not(:last-child)::after {
            content: '';
            position: absolute;
            z-index: 0;
            top: 13px;
            right: 57%;
            width: 86%;
            height: 2px;
            background: #26323a
        }

        .tracking-step.done:not(:last-child)::after {
            background: var(--cyan)
        }

        .tracking-step span {
            position: relative;
            z-index: 1;
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border: 2px solid #26323a;
            border-radius: 50%;
            background: #080d11
        }

        .tracking-step.done,
        .tracking-step.active {
            color: #eaffff
        }

        .tracking-step.done span,
        .tracking-step.active span {
            border-color: var(--cyan);
            color: #001114;
            background: var(--cyan);
            box-shadow: 0 0 14px rgba(8, 222, 244, .34)
        }

        .tracking-step.active span {
            animation: trackingPulse 1.8s infinite
        }

        .tracking-message {
            margin: 8px 0 0;
            color: #d7e1e7;
            font-size: 12px;
            line-height: 1.7
        }

        .tracking-link {
            display: block;
            margin-top: 10px;
            color: var(--cyan);
            font-size: 12px;
            font-weight: 800;
            text-align: center
        }

        @keyframes trackingPulse {
            50% { box-shadow: 0 0 0 7px rgba(8, 222, 244, 0) }
        }

        dialog {
            width: min(560px, calc(100% - 24px));
            max-height: 90vh;
            overflow: auto;
            border: 1px solid rgba(8, 222, 244, .35);
            border-radius: 24px;
            background: linear-gradient(145deg, #141923, #071114);
            color: #fff;
            padding: 0;
            box-shadow: 0 25px 100px rgba(0, 0, 0, .65)
        }

        dialog::backdrop {
            background: rgba(0, 3, 6, .82);
            backdrop-filter: blur(8px)
        }

        .modal-head {
            position: sticky;
            top: 0;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 22px;
            border-bottom: 1px solid var(--border);
            background: rgba(13, 18, 23, .97)
        }

        .modal-head h3 {
            margin: 0;
            font-size: 22px
        }

        .close {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: #151b20;
            color: #fff;
            cursor: pointer
        }

        .modal-body {
            padding: 20px 22px
        }

        .option-section {
            margin-bottom: 19px
        }

        .option-section h4 {
            margin: 0 0 10px
        }

        .choices {
            display: grid;
            gap: 8px
        }

        .choice {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border: 1px solid var(--border);
            border-radius: 13px;
            background: #080d11;
            padding: 11px 13px;
            cursor: pointer
        }

        .choice input {
            accent-color: var(--cyan)
        }

        .choice-price {
            color: var(--cyan);
            font-weight: 800
        }

        .qty-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 10px;
            align-items: end
        }

        .modal-actions {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 9px
        }

        .secondary {
            border: 1px solid var(--border);
            background: #171c21;
            color: #fff;
            border-radius: 12px;
            padding: 10px 17px;
            cursor: pointer
        }

        .mobile-cart {
            display: block;
            position: fixed;
            right: 50%;
            left: auto;
            width: min(480px, calc(100% - 24px));
            transform: translateX(50%);
            bottom: 12px;
            z-index: 15;
            min-height: 55px;
            border: 0;
            border-radius: 17px;
            background: linear-gradient(135deg, #09dcec, #1cc0ef);
            color: #001114;
            font-weight: 900;
            box-shadow: 0 10px 35px rgba(8, 222, 244, .3)
        }

        @media(max-width:1050px) {
            .meals {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr))
            }
        }

        @media(max-width:720px) {
            .category-background {
                top: 0;
                height: 100svh;
                min-height: 460px;
                object-position: 50% 50%;
                transform: scale(1.02)
            }

            .category-content.has-video .category-background {
                opacity: .6
            }

            .category-content::after {
                background: linear-gradient(180deg, rgba(4, 8, 11, .34), rgba(4, 8, 11, .5) 48%, rgba(4, 8, 11, .68))
            }

            .shell {
                width: calc(100% - 18px);
                padding-top: 9px
            }

            .hero {
                min-height: 340px;
                border-radius: 21px;
                padding: 64px 16px 18px
            }

            .hero-tools {
                top: 12px;
                right: 12px;
                left: auto
            }

            .brand {
                align-items: flex-end;
                gap: 14px;
                right: 14px;
                bottom: 16px;
                max-width: calc(100% - 28px)
            }

            .logo {
                width: 138px;
                height: 138px;
                border-radius: 29px
            }

            .restaurant-story-avatar {
                width: 146px;
                border-radius: 25px
            }

            .restaurant-story-avatar.has-shop-story .restaurant-story-logo-frame {
                border-radius: 27px
            }

            .restaurant-story-live {
                width: 146px;
                padding: 0;
                font-size: 9px
            }

            .service-badge {
                display: none
            }

            .layout {
                margin-top: 12px
            }

            .menu-panel,
            .cart {
                padding: 14px;
                border-radius: 20px
            }

            .menu-panel {
                padding-inline: 9px
            }

            .menu-browser {
                grid-template-columns: minmax(0, 1fr) 110px;
                gap: 3px;
                min-height: 480px
            }

            .category-rail {
                top: 8px;
                height: calc(100vh - 76px);
                padding-inline: 3px
            }

            .category-tab {
                border-radius: 15px;
                padding-inline: 2px
            }

            .category-tab img,
            .category-tab-icon {
                width: 57px;
                height: 57px;
                font-size: 24px
            }

            .category-tab span:last-child {
                width: 57px;
                font-size: 10px
            }

            .category-title {
                font-size: 17px
            }

            .meals {
                grid-template-columns: 1fr;
                gap: 12px;
                padding-inline: 0
            }

            .meal {
                width: 100%;
                min-height: 0;
                padding: 6px
            }

            .meal h3 {
                min-height: 0;
                margin: 0 0 4px;
                padding: 0;
                font-size: 13px
            }

            .meal-bottom {
                display: flex
            }

            .add-btn {
                width: auto
            }

            .meal .add-btn {
                width: auto;
                height: 34px
            }

            .meal .add-btn i {
                font-size: 17px
            }

            .price {
                font-size: 11px
            }

            .fields-row {
                grid-template-columns: 1fr
            }

            .cart {
                margin-bottom: 0
            }

            .qty-row {
                grid-template-columns: 1fr
            }

            .modal-actions {
                grid-template-columns: 1fr
            }

            .secondary {
                width: 100%
            }
        }
    </style>
</head>

<body>
    @php
        $restaurantProducts = $products
            ->map(function ($product) {
                $attributes = $product->catalog_attributes ?? [];
                return [
                    'id' => $product->id,
                    'name' => $product->localized('name'),
                    'price' => (float) ($product->discount_price ?: $product->price),
                    'sizes' => $attributes['meal_size_prices'] ?? [],
                    'addons' => $attributes['addon_prices'] ?? [],
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
                return [
                    'key' => (string) $category->id,
                    'name' => $category->localized('name'),
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
                'image' => $uncategorizedProducts->first(fn($product) => filled($product->main_image))?->main_image,
                'background' => $defaultCategoryBackground,
                'products' => $uncategorizedProducts,
            ]);
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
    @endphp
    <div class="shell">
        <header class="hero">
            <div class="hero-tools">
                @include('front.partials.public_language_switcher')
            </div>
            <div class="brand">
                <div class="restaurant-logo-stack">
                    <span class="restaurant-availability {{ $shop->is_accepting_orders ? 'is-open' : 'is-closed' }}">
                        <i aria-hidden="true"></i>
                        {{ $availabilityShortLabel }}
                    </span>
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
            </div>
            <div class="service-badge"><i
                    class="ti {{ $table ? 'ti-tools-kitchen-2' : 'ti-truck-delivery' }}"></i>{{ $table ? $copy['inside_restaurant'] : $copy['delivery_pickup'] }}
            </div>
        </header>

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
                <div class="section-head">
                    <div>
                        <h2>{{ $copy['food_menu'] }}</h2>
                        <p>{{ $copy['food_hint'] }}</p>
                    </div><span class="count">{{ $products->count() }}</span>
                </div>
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
                                    <h3 class="category-title">{{ $category['name'] }}</h3>
                                    <div class="meals">
                            @forelse ($category['products'] as $product)
                                <article class="meal" data-product-id="{{ $product->id }}" role="button" tabindex="0"
                                    aria-label="{{ $copy['show_options'] }} {{ $product->localized('name') }}">
                                    @if ($product->main_image)
                                        <img class="meal-image" src="{{ asset($product->main_image) }}"
                                        alt="{{ $product->localized('name') }}">@else<div class="meal-image"></div>
                                    @endif
                                    <div class="meal-body">
                                        <h3>{{ $product->localized('name') }}</h3>
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
                <div class="cart-total"><span>{{ $copy['total'] }}</span><strong><span id="total">0.00</span> ₪</strong></div>
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
                        <span>{{ $copy['estimated_prep'] }} <strong id="trackingMinutes"></strong> {{ $copy['minute'] }}</span>
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

    <button class="mobile-cart" id="mobileCart"><i class="ti ti-shopping-bag"></i> {{ $copy['view_order'] }} (<span
            id="mobileCount">0</span>) — <span id="mobileTotal">0.00</span> ₪</button>
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

            const categoryRail = $('categoryRail');
            const categoryContent = $('categoryContent');
            const categoryBackground = $('categoryBackground');
            const categoryTabs = [...document.querySelectorAll('.category-tab')];
            const categoryPanes = [...document.querySelectorAll('[data-category-pane]')];
            let activeCategory = categoryTabs[0]?.dataset.categoryKey;
            let categoryFrame = null;

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

            const positionCategoryArc = () => {
                if (!categoryRail || !categoryTabs.length) return;
                const railBox = categoryRail.getBoundingClientRect();
                const center = railBox.top + railBox.height / 2;
                const reach = Math.max(1, railBox.height / 2);

                categoryTabs.forEach(tab => {
                    const box = tab.getBoundingClientRect();
                    const distance = Math.min(1, Math.abs(box.top + box.height / 2 - center) / reach);
                    const curve = Math.cos(distance * Math.PI / 2);
                    const arcDepth = window.innerWidth <= 720 ? 14 : 22;
                    tab.style.setProperty('--arc-x', `${-arcDepth * curve}px`);
                    tab.style.setProperty('--arc-scale', String(.78 + curve * .52));
                    tab.style.setProperty('--arc-opacity', String(.38 + curve * .62));
                });
            };

            const activateCategory = (key, centerTab = false) => {
                if (!key || key === activeCategory && !centerTab) return;
                activeCategory = key;
                categoryTabs.forEach(tab => {
                    const active = tab.dataset.categoryKey === key;
                    tab.classList.toggle('active', active);
                    tab.setAttribute('aria-pressed', String(active));
                    if (active && centerTab) tab.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
                categoryPanes.forEach(pane => pane.hidden = pane.dataset.categoryPane !== key);
                const activePane = categoryPanes.find(pane => pane.dataset.categoryPane === key);
                setCategoryBackground(activePane);
            };

            categoryTabs.forEach(tab => tab.addEventListener('click', () =>
                activateCategory(tab.dataset.categoryKey, true)));

            categoryRail?.addEventListener('scroll', () => {
                cancelAnimationFrame(categoryFrame);
                categoryFrame = requestAnimationFrame(() => {
                    positionCategoryArc();
                    const railBox = categoryRail.getBoundingClientRect();
                    const center = railBox.top + railBox.height / 2;
                    const closest = categoryTabs.reduce((best, tab) => {
                        const box = tab.getBoundingClientRect();
                        const distance = Math.abs(box.top + box.height / 2 - center);
                        return !best || distance < best.distance ? { tab, distance } : best;
                    }, null);
                    if (closest) activateCategory(closest.tab.dataset.categoryKey);
                });
            }, { passive: true });

            window.addEventListener('resize', () => {
                positionCategoryArc();
            }, { passive: true });
            requestAnimationFrame(() => {
                positionCategoryArc();
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
                card.addEventListener('click', () => openMeal(card.dataset.productId));
                card.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openMeal(card.dataset.productId);
                    }
                });
            });
            const closeModal = () => modal.close();
            $('modalClose').onclick = closeModal;
            $('modalCancel').onclick = closeModal;
            $('confirm').onclick = () => {
                const selectedSize = $('sizes').querySelector(':checked');
                const selectedAddons = [...$('addons').querySelectorAll(':checked')];
                const unit = selectedSize ? Number(selectedSize.dataset.price) : Number(current.price);
                const addonTotal = selectedAddons.reduce((sum, input) => sum + Number(input.dataset.price), 0);
                cart.push({
                    product_id: current.id,
                    name: current.name,
                    qty: Math.max(1, Number($('qty').value) || 1),
                    size: selectedSize?.value || null,
                    addons: selectedAddons.map(input => input.value),
                    excluded: [...$('excluded').querySelectorAll(':checked')].map(input => input.value),
                    notes: $('notes').value,
                    unit: unit + addonTotal,
                    preparation_time: Number(current.preparation_time) || 0
                });
                closeModal();
                render();
            };
            window.removeRestaurantCartItem = index => {
                cart.splice(index, 1);
                render();
            };

            function render() {
                $('cartItems').innerHTML = cart.length ? cart.map((item, index) =>
                    `<article class="cart-item"><div class="cart-item-head"><strong>${item.qty}× ${escapeHtml(item.name)}</strong><button class="remove" onclick="removeRestaurantCartItem(${index})"><i class="ti ti-trash"></i></button></div><small>${escapeHtml(item.size||'')} ${item.addons.length?'• '+escapeHtml(item.addons.join('، ')):''}${item.preparation_time?' • '+escapeHtml(ui.about)+' '+item.preparation_time+' '+escapeHtml(ui.minute):''}</small><span class="price">${(item.unit*item.qty).toFixed(2)} ₪</span></article>`
                    ).join('') : `<div class="empty"><i class="ti ti-shopping-bag"></i>${escapeHtml(ui.empty_cart)}</div>`;
                const total = cart.reduce((sum, item) => sum + item.unit * item.qty, 0);
                $('total').textContent = $('mobileTotal').textContent = total.toFixed(2);
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
                }, () => {
                    status.textContent = ui.location_failed;
                    status.classList.remove('ready')
                }, {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0
                });
            });

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

            $('send').onclick = async () => {
                const message = $('message');
                message.className = 'message';
                message.textContent = '';
                if (!cart.length) {
                    message.classList.add('error');
                    message.textContent = ui.add_meal_error;
                    return
                }
                if (type.value === 'delivery' && (!$('latitude').value || !$('longitude').value)) {
                    message.classList.add('error');
                    message.textContent = ui.set_location_error;
                    return
                }
                const payload = {
                    order_type: type.value,
                    table_code: @json($table?->code),
                    customer_name: $('name').value,
                    customer_phone: $('phone').value,
                    customer_address: address.value,
                    latitude: $('latitude').value || null,
                    longitude: $('longitude').value || null,
                    customer_notes: $('orderNotes').value,
                    items: cart
                };
                try {
                    const response = await fetch(@json(route('restaurant.orders.store', $shop)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                        throw new Error(errors || data.message || 'تحقق من البيانات')
                    }
                    cart.length = 0;
                    render();
                    message.classList.add('success');
                    message.textContent = `${ui.sent_success} ${data.order_number}`;
                    activateTracking(data);
                } catch (error) {
                    message.classList.add('error');
                    message.textContent = error.message || ui.send_error
                }
            };
            render();
        })();
    </script>
</body>

</html>
