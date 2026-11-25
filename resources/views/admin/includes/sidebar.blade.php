@once
    @viewStyle('admin.includes.sidebar')
@endonce


<div class="sidebar admin-neon-sidebar" id="admin-dashboard-sidebar">
    <div class="admin-sidebar-logo">
        <div class="admin-sidebar-logo-icon">O</div>
        <div>
            <div class="admin-sidebar-logo-text">Ozman</div>
            <div class="admin-sidebar-logo-sub">{{ $catalogTerms['dashboard_label'] ?? 'لوحة التحكم' }}</div>
        </div>
        <button type="button" class="admin-mobile-sidebar-close" data-admin-menu-close aria-label="إغلاق قائمة لوحة التحكم" style="display:none;margin-right:auto;width:42px;height:42px;border-radius:50%;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);color:#fff;align-items:center;justify-content:center;cursor:pointer">
            <i class="ti ti-x" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="admin-sidebar-nav">
        @if($useShopOwnerMenu)
            @include('admin.includes.sidebar_shop_owner')
        @else
        <div class="admin-sidebar-section">عام</div>

        @if($isSuperAdmin)
        <a href="{{ route('admin.analytics.index') }}" class="admin-sidebar-item nav-item">
            <i class="ti ti-chart-bar" aria-hidden="true"></i>
            إحصائيات كل المحلات
        </a>
        <a href="{{ route('admin.analytics.index') }}#adminPush" class="admin-sidebar-item nav-item">
            <i class="ti ti-device-mobile" aria-hidden="true"></i>
            تثبيت تطبيق الإدارة
        </a>
        <a href="{{ route('admin.promoter-requests') }}" class="admin-sidebar-item nav-item {{ request()->routeIs('admin.promoter-requests') ? 'active' : '' }}">
            <i class="ti ti-user-check" aria-hidden="true"></i>
            طلبات المروّجين
            @if($pendingPromoters)<span class="sidebar-count" style="margin-inline-start:auto;min-width:22px;height:22px;padding:0 7px;border-radius:11px;background:#ff4d6d;color:#fff;font-size:12px;font-weight:900;display:inline-grid;place-items:center" title="{{ __('طلبات مروّجين بانتظار الموافقة') }}">{{ $pendingPromoters }}</span>@endif
        </a>
        <a href="{{ route('promoter.accounts') }}" class="admin-sidebar-item nav-item">
            <i class="ti ti-user-star" aria-hidden="true"></i>
            حسابات المروّجين
        </a>
        <a href="{{ route('market.admin') }}" class="admin-sidebar-item nav-item">
            <i class="ti ti-building-store" aria-hidden="true"></i>
            المتجر الحر
        </a>
        @endif

        @if($canSee(['dashboard']))
        <a href="{{ route('dashboard') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
             <i class="ti ti-layout-dashboard" aria-hidden="true"></i>
             الرئيسية
        </a>
        @endif

        <div class="admin-sidebar-section">{{ $catalogTerms['place_singular'] ?? 'المتجر' }}</div>
        @php
            $signupNavShop = $restaurantNavShop ?: ($cosmeticsNavShop?->slug === 'elegance-perfumes' ? $cosmeticsNavShop : null);
        @endphp
        @if($signupNavShop && $canSee(['reward-wheels.shop-signup.edit']))
        <a href="{{ route('reward-wheels.shop-signup.edit', $signupNavShop) }}" class="admin-sidebar-item nav-item {{ request()->routeIs('reward-wheels.shop-signup.*') ? 'active' : '' }}">
             <i class="ti ti-discount" aria-hidden="true"></i> عجلة التسجيل
        </a>
        @endif
        @if($restaurantNavShop && $canSee(['restaurant.customers.index']))
        <a href="{{ route('restaurant.customers.index', $restaurantNavShop) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('restaurant.customers.index') ? 'active' : '' }}">
             <i class="ti ti-users" aria-hidden="true"></i>
             العملاء المسجّلون
        </a>
        @endif

        @if($isMarketer && ! $hasAssignedPermissions)
        @if($canSee(['front-orders.index']))
        <a href="{{ route('front-orders.index') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('front-orders.index') ? 'active' : '' }}">
             <i class="ti ti-receipt-2" aria-hidden="true"></i>
             طلباتي من الروابط
        </a>
        @endif
        @if($canSee(['reward-wheels.marketer.play']))
        <a href="{{ route('reward-wheels.marketer.play') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('reward-wheels.marketer.play') ? 'active' : '' }}">
             <i class="ti ti-disc" aria-hidden="true"></i>
             عجلة الأسئلة
        </a>
        @endif
        @if($canSee(['reward-wheels.marketer.direct.play']))
        <a href="{{ route('reward-wheels.marketer.direct.play') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('reward-wheels.marketer.direct.play') ? 'active' : '' }}">
             <i class="ti ti-bolt" aria-hidden="true"></i>
             العجلة المباشرة
        </a>
        @endif
        @else

        @if(($isSuperAdmin || $isPermissionManaged) && $canSee(['dashboard.main']))
        <a href="{{ route('dashboard.main') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('dashboard.main') ? 'active' : '' }}">
             <i class="ti ti-dashboard" aria-hidden="true"></i>
             لوحة التحكم الرئيسية
        </a>

        @endif

        @if(! $isCatalogOnlyUser && $canSee(['shops', 'shops.show', 'shops.create', 'shops.edit']))
        <a href="{{ $isShopOwner && $previewShopId ? route('shops.show', $previewShopId) : route('shops') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('shops') || request()->routeIs('shops.*') ? 'active' : '' }}">
             <i class="ti ti-building-store" aria-hidden="true"></i>
             {{ $isShopOwner ? 'بيانات متجري' : 'المتاجر' }}
        </a>
        @endif

        @if($isShopOwner && $previewShopId)
        <a href="{{ route('merchant-app.index') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('merchant-app.*') ? 'active' : '' }}">
             <i class="ti ti-device-mobile-down" aria-hidden="true"></i>
             تثبيت تطبيق محلي
        </a>
        @endif

        @if($canSee(['products', 'products.show', 'products.create', 'products.edit']))
        <a href="{{ route('products', $previewShopId ? ['shop_id' => $previewShopId] : []) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('products') || request()->routeIs('products.create') || request()->routeIs('products.edit') || request()->routeIs('products.show') ? 'active' : '' }}">
             <i class="ti ti-package" aria-hidden="true"></i>
             {{ $catalogItemsLabel }}
        </a>
        @endif

        @if($canSee(['demand.index']))
        <a href="{{ route('demand.index', $previewShopId ? ['shop_id' => $previewShopId] : []) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('demand.index') ? 'active' : '' }}">
             <i class="ti ti-zoom-question" aria-hidden="true"></i>
             {{ __('شو بدوّروا الزباين') }}
        </a>
        @endif

        @if($canSee(['store-points.till']))
        <a href="{{ route('store-points.till', $previewShopId ? ['shop_id' => $previewShopId] : []) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('store-points.*') ? 'active' : '' }}">
             <i class="ti ti-qrcode" aria-hidden="true"></i>
             {{ __('نقاط للزبون بالمحل') }}
        </a>
        @endif

        @if($canSee(['social.index']))
        <a href="{{ route('social.index', $previewShopId ? ['shop_id' => $previewShopId] : []) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('social.*') ? 'active' : '' }}">
             <i class="ti ti-brand-instagram" aria-hidden="true"></i>
             {{ __('السوشال ميديا') }}
        </a>
        @endif

        @if($canSee(['products.preview']))
        <a href="{{ $previewUrl }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('products.preview') ? 'active' : '' }}">
             <i class="ti ti-eye" aria-hidden="true"></i>
             معاينة المتجر
        </a>
        @endif

        @if($canSee(['categories', 'categories.show', 'categories.create', 'categories.edit']))
        <a href="{{ route('categories', $previewShopId ? ['shop_id' => $previewShopId] : []) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('categories*') ? 'active' : '' }}">
             <i class="ti ti-category" aria-hidden="true"></i>
             {{ $catalogCategoriesLabel }}
        </a>
        @endif

        @if(auth()->user()?->isSuperAdmin() || auth()->user()?->shops()->exists())
        <a href="{{ route('shop-stories.index') }}" class="admin-sidebar-item nav-item">
            <i class="ti ti-circle" aria-hidden="true"></i> ستوريات المحل
        </a>
        @endif
        @if(! $isCatalogOnlyUser && $canSee(['ads', 'ads.show', 'ads.create', 'ads.edit']))
        <div class="admin-sidebar-section">الإعلانات</div>

        <a href="{{ route('ads') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('ads*') ? 'active' : '' }}">
             <i class="ti ti-speakerphone" aria-hidden="true"></i>
             الإعلانات
        </a>
        @endif

        @if(($isSuperAdmin || $isPermissionManaged) && $canSee(['screens', 'screens.show', 'screens.create', 'screens.edit']))
        <a href="{{ route('screens') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('screens*') ? 'active' : '' }}">
             <i class="ti ti-device-tv" aria-hidden="true"></i>
             الشاشات
        </a>

        <div class="admin-sidebar-section">الإدارة</div>

        @endif

        @if(($isSuperAdmin || $isPermissionManaged) && $canSee(['users']))
        <a href="{{ route('users') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('users') ? 'active' : '' }}">
             <i class="ti ti-users" aria-hidden="true"></i>
             المستخدمون
        </a>
        @endif

        @if(($isSuperAdmin || $isPermissionManaged) && $canSee(['customer-points.index']))
        <a href="{{ route('customer-points.index') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('customer-points.*') ? 'active' : '' }}">
             <i class="ti ti-star" aria-hidden="true"></i>
             {{ __('نقاط العملاء') }}
        </a>
        @endif

        @if(($isSuperAdmin || $isPermissionManaged) && $canSee(['employees', 'employees.create', 'employees.edit', 'employees.permissions.edit']))
        <a href="{{ route('employees') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('employees*') ? 'active' : '' }}">
             <i class="ti ti-users-group" aria-hidden="true"></i>
             الموظفون
        </a>
        @endif

        @if($canSee(['visitor-registrations.index']))
        <a href="{{ route('visitor-registrations.index') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('visitor-registrations.index') ? 'active' : '' }}">
             <i class="ti ti-address-book" aria-hidden="true"></i>
             تسجيلات الزوار
        </a>
        @endif

        @if($restaurantNavShop && $canSee(['restaurant.dashboard']))
        <a href="{{ route('restaurant.dashboard', $restaurantNavShop) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('restaurant.dashboard') ? 'active' : '' }}">
             <i class="ti ti-tools-kitchen-2" aria-hidden="true"></i>
             إدارة المطعم والطلبات
        </a>
        @if($canSee(['restaurant.tables.index']))
        <a href="{{ route('restaurant.tables.index', $restaurantNavShop) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('restaurant.tables.*') ? 'active' : '' }}">
             <i class="ti ti-qrcode" aria-hidden="true"></i>
             طاولات المطعم وQR
        </a>
        @endif
        @if($canSee(['restaurant.drivers.index']))
        <a href="{{ route('restaurant.drivers.index', $restaurantNavShop) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('restaurant.drivers.*') ? 'active' : '' }}">
             <i class="ti ti-motorbike" aria-hidden="true"></i>
             مندوبي التوصيل
        </a>
        @endif
        @elseif($realEstateNavShop && $canSee(['real-estate.dashboard']))
        <a href="{{ route('real-estate.dashboard', $realEstateNavShop) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('real-estate.dashboard*') ? 'active' : '' }}">
             <i class="ti ti-building-community" aria-hidden="true"></i>
             إدارة الخدمات والطلبات
        </a>
        @elseif($canSeeSalonAppointments)
        <a href="{{ route('salon-appointments.index', $cosmeticsNavShop) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('salon-appointments.*') ? 'active' : '' }}">
             <i class="ti ti-calendar-heart" aria-hidden="true"></i>
             حجوزات الصالون
        </a>
        @elseif($canSee(['front-orders.index']))
        <a href="{{ route('front-orders.index') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('front-orders.index') ? 'active' : '' }}">
             <i class="ti ti-receipt-2" aria-hidden="true"></i>
             الطلبات
        </a>
        @endif

        @if($canSeeShopOutlets)
        <a href="{{ route('shop-outlets.index', $kremboNavShop) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('shop-outlets.*') ? 'active' : '' }}">
             <i class="ti ti-map-pins" aria-hidden="true"></i>
             نقاط البيع
        </a>
        @endif

        @if($canSee(['reward-wheels.marketer.play']))
        <a href="{{ route('reward-wheels.marketer.play') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('reward-wheels.marketer.play') ? 'active' : '' }}">
             <i class="ti ti-disc" aria-hidden="true"></i>
             عجلة الأسئلة
        </a>
        @endif

        @if($canSee(['reward-wheels.marketer.direct.play']))
        <a href="{{ route('reward-wheels.marketer.direct.play') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('reward-wheels.marketer.direct.play') ? 'active' : '' }}">
             <i class="ti ti-bolt" aria-hidden="true"></i>
             العجلة المباشرة
        </a>
        @endif

        @if($canSee(['raffle-cards.index']))
        <a href="{{ route('raffle-cards.index') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('raffle-cards.*') ? 'active' : '' }}">
             <i class="ti ti-ticket" aria-hidden="true"></i>
             بطاقات السحب
        </a>
        @elseif($canSee(['raffle-cards.inspector']))
        <a href="{{ route('raffle-cards.inspector') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('raffle-cards.inspector') ? 'active' : '' }}">
             <i class="ti ti-ticket" aria-hidden="true"></i>
             فحص بطاقة رابحة
        </a>
        @endif

        @if($canSee(['reward-wheels.customer-signup.edit', 'reward-wheels.customer-signup.update']))
        <a href="{{ route('reward-wheels.customer-signup.edit') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('reward-wheels.customer-signup.*') ? 'active' : '' }}">
             <i class="ti ti-disc" aria-hidden="true"></i>
             عجلات الربح
        </a>
        @endif

        @if($canSee(['reward-wheels.purchase.index', 'reward-wheels.purchase.edit', 'reward-wheels.purchase.store']))
        <a href="{{ route('reward-wheels.purchase.index', $catalogNavShop ? ['shop_id' => $catalogNavShop->id] : []) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('reward-wheels.purchase.*') ? 'active' : '' }}">
             <i class="ti ti-shopping-cart-star" aria-hidden="true"></i>
             عجلات الشراء
        </a>
        @endif

        @if($canSee(['reward-wheels.marketer.edit', 'reward-wheels.marketer.update']))
        <a href="{{ route('reward-wheels.marketer.edit') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('reward-wheels.marketer.edit') || request()->routeIs('reward-wheels.marketer.update') ? 'active' : '' }}">
             <i class="ti ti-target-arrow" aria-hidden="true"></i>
             عجلة أسئلة المسوقة
        </a>
        @endif

        @if($canSee(['reward-wheels.marketer.direct.edit', 'reward-wheels.marketer.direct.update']))
        <a href="{{ route('reward-wheels.marketer.direct.edit') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('reward-wheels.marketer.direct.*') ? 'active' : '' }}">
             <i class="ti ti-bolt" aria-hidden="true"></i>
             عجلة المسوقة المباشرة
        </a>
        @endif

        @if(! $isCatalogOnlyUser && $canSee(['agents', 'agents.show', 'agents.create', 'agents.edit', 'agents.permissions.edit']))
        <a href="{{ route('agents') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('agents*') ? 'active' : '' }}">
             <i class="ti ti-user-star" aria-hidden="true"></i>
             الوكلاء
        </a>
        @endif

        @if(! $isCatalogOnlyUser && $canSee(['distributors', 'distributors.show', 'distributors.create', 'distributors.edit', 'distributors.permissions.edit']))
        <a href="{{ route('distributors') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('distributors*') ? 'active' : '' }}">
             <i class="ti ti-truck-delivery" aria-hidden="true"></i>
             الموزعون
        </a>
        @endif

        @if(! $isCatalogOnlyUser && $canSee(['distributors.marketers.index', 'distributors.marketers.permissions.edit']))
        <a href="{{ route('distributors.marketers.index') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('distributors.marketers.*') ? 'active' : '' }}">
             <i class="ti ti-speakerphone" aria-hidden="true"></i>
             مسوقو الموزعين
        </a>
        @endif

        @if(($isSuperAdmin || $isPermissionManaged) && $canSee(['settings']))
        <a href="{{ route('settings') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
             <i class="ti ti-settings" aria-hidden="true"></i>
             الإعدادات
        </a>
        @endif
        @if($isSuperAdmin)
        <a href="{{ route('push-notifications.index') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('push-notifications.*') ? 'active' : '' }}">
             <i class="ti ti-bell-ringing" aria-hidden="true"></i>
             إشعارات التطبيق
        </a>
        @endif
        @endif
            @endif
    </nav>

    <form method="POST" action="{{ route('logout') }}" style="margin:0 12px 12px;">
        @csrf
        <button type="submit" class="admin-sidebar-item" style="width:100%;justify-content:flex-start;background:rgba(255,255,255,.05);cursor:pointer;font-family:inherit;">
            <i class="ti ti-logout" aria-hidden="true"></i>
            تسجيل الخروج
        </button>
    </form>

    <div class="admin-sidebar-footer">
        <i class="ti ti-circle-check" aria-hidden="true"></i>
        النظام متصل وجاهز
    </div>
</div>

<button type="button" class="admin-mobile-menu-overlay" data-admin-menu-close aria-label="إغلاق قائمة لوحة التحكم"></button>

@php
    $mobileNavCount = $useShopOwnerMenu ? ($catalogNavShop->catalog_type === 'real_estate' ? 3 : 4) : 1
        + ($canSee(['dashboard']) ? 1 : 0)
        + ((($restaurantNavShop && $canSee(['restaurant.dashboard'])) || ($realEstateNavShop && $canSee(['real-estate.dashboard'])) || $canSeeSalonAppointments || $canSee(['front-orders.index'])) ? 1 : 0)
        + ($canSee(['products']) ? 1 : 0)
        + ($canSee(['products.preview']) ? 1 : 0);
@endphp
<nav class="admin-mobile-nav" aria-label="تنقل لوحة التحكم للجوال" style="--admin-mobile-nav-count: {{ min($mobileNavCount, 5) }}">
    @if($useShopOwnerMenu)
        @php
            $mobileOrders = match ($catalogNavShop->catalog_type) {
                'restaurant' => [route('restaurant.dashboard', $catalogNavShop), request()->routeIs('restaurant.dashboard', 'restaurant.orders.*'), 'الطلبات', 'ti-receipt-2'],
                'real_estate' => [route('real-estate.dashboard', $catalogNavShop), request()->routeIs('real-estate.dashboard*'), 'الطلبات', 'ti-building-community'],
                default => [route('shop-orders.dashboard', $catalogNavShop), request()->routeIs('shop-orders.*'), 'الطلبات', 'ti-receipt-2'],
            };
        @endphp
        <a href="{{ $mobileOrders[0] }}" class="admin-mobile-nav-item {{ $mobileOrders[1] ? 'active' : '' }}">
            <i class="ti {{ $mobileOrders[3] }}" aria-hidden="true"></i>
            {{ $mobileOrders[2] }}
        </a>
        @if($catalogNavShop->catalog_type !== 'real_estate')
            <a href="{{ route('products', ['shop_id' => $catalogNavShop->id]) }}" class="admin-mobile-nav-item {{ request()->routeIs('products*') ? 'active' : '' }}">
                <i class="ti ti-package" aria-hidden="true"></i>
                {{ $catalogItemsLabel }}
            </a>
        @endif
        <a href="{{ route('shops.show', $catalogNavShop) }}" class="admin-mobile-nav-item {{ request()->routeIs('shops.show', 'shops.edit') ? 'active' : '' }}">
            <i class="ti ti-building-store" aria-hidden="true"></i>
            {{ match ($catalogNavShop->catalog_type) { 'restaurant' => 'المطعم', 'dental' => 'العيادة', 'krembo', 'advertising_services', 'real_estate' => 'الشركة', default => 'المحل' } }}
        </a>
        <button type="button" class="admin-mobile-nav-item" data-admin-menu-open aria-controls="admin-dashboard-sidebar" aria-expanded="false">
            <i class="ti ti-menu-2" aria-hidden="true"></i>
            كل الأقسام
        </button>
    @else
    @if($canSee(['dashboard']))
        <a href="{{ route('dashboard') }}" class="admin-mobile-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="ti ti-layout-dashboard" aria-hidden="true"></i>
            الرئيسية
        </a>
    @endif

    @if($restaurantNavShop && $canSee(['restaurant.dashboard']))
        <a href="{{ route('restaurant.dashboard', $restaurantNavShop) }}" class="admin-mobile-nav-item {{ request()->routeIs('restaurant.*') ? 'active' : '' }}">
            <i class="ti ti-tools-kitchen-2" aria-hidden="true"></i>
            المطعم
        </a>
    @elseif($realEstateNavShop && $canSee(['real-estate.dashboard']))
        <a href="{{ route('real-estate.dashboard', $realEstateNavShop) }}" class="admin-mobile-nav-item {{ request()->routeIs('real-estate.dashboard*') ? 'active' : '' }}">
            <i class="ti ti-building-community" aria-hidden="true"></i>
            الخدمات
        </a>
    @elseif($canSeeSalonAppointments)
        <a href="{{ route('salon-appointments.index', $cosmeticsNavShop) }}" class="admin-mobile-nav-item {{ request()->routeIs('salon-appointments.*') ? 'active' : '' }}">
            <i class="ti ti-calendar-heart" aria-hidden="true"></i>
            الحجوزات
        </a>
    @elseif($canSee(['front-orders.index']))
        <a href="{{ route('front-orders.index') }}" class="admin-mobile-nav-item {{ request()->routeIs('front-orders.index') ? 'active' : '' }}">
            <i class="ti ti-receipt-2" aria-hidden="true"></i>
            الطلبات
        </a>
    @endif

    @if($canSee(['products']))
        <a href="{{ route('products', $previewShopId ? ['shop_id' => $previewShopId] : []) }}" class="admin-mobile-nav-item {{ request()->routeIs('products*') ? 'active' : '' }}">
            <i class="ti ti-package" aria-hidden="true"></i>
            {{ $catalogItemsLabel }}
        </a>
    @endif

    <button type="button" class="admin-mobile-nav-item" data-admin-menu-open aria-controls="admin-dashboard-sidebar" aria-expanded="false">
        <i class="ti ti-menu-2" aria-hidden="true"></i>
        كل الأقسام
    </button>

    @if($canSee(['products.preview']))
        <a href="{{ $previewUrl }}" class="admin-mobile-nav-item {{ request()->routeIs('products.preview') ? 'active' : '' }}">
            <i class="ti ti-eye" aria-hidden="true"></i>
            معاينة
        </a>
    @endif

    @endif
</nav>

@include('admin.analytics.notifications')
@once
@viewScript('admin.includes.sidebar')
@endonce
{{-- Fulla introduces herself to shop owners the first time they open the dashboard. --}}
@include('front.partials.fulla_intro', ['onDashboard' => true])
