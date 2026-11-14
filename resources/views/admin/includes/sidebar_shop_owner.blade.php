{{-- The shop owner's menu: what they use every day first, then grouped by purpose.
     Included from sidebar.blade.php, which sets $catalogNavShop and the rest. --}}
@php
    $shop = $catalogNavShop;
    $type = $shop->catalog_type;
    $shopParam = ['shop_id' => $shop->id];
    // One short word for "the shop", used the same way everywhere in this menu.
    $place = match ($type) {
        'restaurant' => 'المطعم',
        'dental' => 'العيادة',
        'krembo', 'advertising_services', 'real_estate' => 'الشركة',
        default => 'المحل',
    };
    // Each link names the routes it needs, so an owner whose account was limited only sees what they may open.
    $item = fn (string $href, string $icon, string $label, bool $active, array $needs = []) => ($needs === [] || $canSee($needs)) ? ['href' => $href, 'icon' => $icon, 'label' => $label, 'active' => $active] : null;

    // 1) Daily work: orders, bookings or requests, depending on the kind of shop.
    $daily = [];
    if ($type === 'restaurant') {
        $daily[] = $item(route('restaurant.dashboard', $shop), 'ti-receipt-2', 'الطلبات', request()->routeIs('restaurant.dashboard', 'restaurant.orders.*'), ['restaurant.dashboard']);
        $daily[] = $item(route('restaurant.tables.index', $shop), 'ti-qrcode', 'الطاولات وQR', request()->routeIs('restaurant.tables.*'), ['restaurant.tables.index']);
        $daily[] = $item(route('restaurant.drivers.index', $shop), 'ti-motorbike', 'مندوبو التوصيل', request()->routeIs('restaurant.drivers.*'), ['restaurant.drivers.index']);
        $daily[] = $item(route('restaurant.customers.index', $shop), 'ti-users', 'العملاء المسجّلون', request()->routeIs('restaurant.customers.*'), ['restaurant.customers.index']);
    } elseif ($type === 'real_estate') {
        $daily[] = $item(route('real-estate.dashboard', $shop), 'ti-building-community', 'الخدمات والطلبات', request()->routeIs('real-estate.dashboard*'), ['real-estate.dashboard']);
    } else {
        if ($type === 'cosmetics') {
            $daily[] = $item(route('salon-appointments.index', $shop), 'ti-calendar-heart', 'الحجوزات', request()->routeIs('salon-appointments.*'));
        }
        $daily[] = $item(route('shop-orders.dashboard', $shop), 'ti-receipt-2', 'الطلبات', request()->routeIs('shop-orders.*', 'front-orders.*'), ['front-orders.index', 'shop-orders.dashboard']);
        if ($type === 'krembo') {
            $daily[] = $item(route('shop-outlets.index', $shop), 'ti-map-pins', 'نقاط البيع', request()->routeIs('shop-outlets.*'));
        }
    }

    // 2) What the shop sells.
    $catalog = $type === 'real_estate' ? [] : [
        $item(route('products', $shopParam), 'ti-package', $catalogItemsLabel, request()->routeIs('products', 'products.create', 'products.edit', 'products.show'), ['products']),
        $item(route('categories', $shopParam), 'ti-category', $catalogCategoriesLabel, request()->routeIs('categories*'), ['categories']),
    ];

    // 3) Rewards that bring customers back.
    $rewards = [];
    if ($type === 'restaurant' || $shop->slug === 'elegance-perfumes') {
        $rewards[] = $item(route('reward-wheels.shop-signup.edit', $shop), 'ti-discount', 'عجلة التسجيل', request()->routeIs('reward-wheels.shop-signup.*'), ['reward-wheels.shop-signup.edit']);
    }
    if ($type !== 'real_estate') {
        $rewards[] = $item(route('reward-wheels.purchase.index', $shopParam), 'ti-shopping-cart-star', 'عجلات الشراء', request()->routeIs('reward-wheels.purchase.*'), ['reward-wheels.purchase.index']);
    }

    // 4) Getting the word out.
    $marketing = [
        $item(route('shop-stories.index'), 'ti-circle', 'الستوريات', request()->routeIs('shop-stories.*')),
        $item(route('social.index', $shopParam), 'ti-brand-instagram', 'السوشال ميديا', request()->routeIs('social.*'), ['social.index']),
        $item(route('ads'), 'ti-speakerphone', 'الإعلانات', request()->routeIs('ads*'), ['ads']),
        $item(route('screens'), 'ti-device-tv', 'الشاشات', request()->routeIs('screens*'), ['screens']),
    ];

    // 5) The shop itself.
    $myShop = [
        $item(route('shops.show', $shop), 'ti-building-store', 'بيانات '.$place, request()->routeIs('shops.show', 'shops.edit', 'dashboard.main'), ['shops.show']),
        $item($previewUrl, 'ti-eye', 'معاينة '.$place.' كزبون', false, ['products.preview']),
        $item(route('merchant-app.index'), 'ti-device-mobile-down', 'تطبيق '.$place, request()->routeIs('merchant-app.*')),
    ];

    // Krembo works through agents and distributors.
    $network = $type !== 'krembo' ? [] : [
        $item(route('agents'), 'ti-user-star', 'الوكلاء', request()->routeIs('agents*'), ['agents']),
        $item(route('distributors'), 'ti-truck-delivery', 'الموزعون', request()->routeIs('distributors') || request()->routeIs('distributors.show', 'distributors.create', 'distributors.edit'), ['distributors']),
    ];

    $sections = array_filter(array_map(fn ($links) => array_values(array_filter($links)), [
        'اليومي' => $daily,
        'المنتجات' => $catalog,
        'العروض والجوائز' => $rewards,
        'التسويق' => $marketing,
        $place => $myShop,
        'الوكلاء والموزعون' => $network,
        'الحساب' => [$item(route('settings'), 'ti-settings', 'الإعدادات', request()->routeIs('settings'), ['settings'])],
    ]));
@endphp

@foreach($sections as $title => $links)
    <div class="admin-sidebar-section">{{ $title }}</div>
    @foreach($links as $link)
        <a href="{{ $link['href'] }}" class="admin-sidebar-item nav-item {{ $link['active'] ? 'active' : '' }}">
            <i class="ti {{ $link['icon'] }}" aria-hidden="true"></i>
            {{ $link['label'] }}
        </a>
    @endforeach
@endforeach
