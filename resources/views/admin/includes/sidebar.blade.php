@once
    <style>
        .sidebar.admin-neon-sidebar {
            width: 245px;
            background: rgba(0, 0, 0, .78);
            backdrop-filter: blur(18px);
            border-left: 1px solid rgba(255, 255, 255, .1);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            right: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 25;
            box-shadow: -10px 0 35px rgba(0, 229, 255, .05);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
        }

        .sidebar.admin-neon-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar.admin-neon-sidebar::-webkit-scrollbar-thumb {
            background: rgba(0, 229, 255, .28);
            border-radius: 999px;
        }

        .admin-sidebar-logo {
            padding: 24px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, .1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-sidebar-logo-icon {
            width: 58px;
            height: 58px;
            background: #000;
            border: 2px solid #00e5ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00e5ff;
            font-size: 22px;
            font-weight: 900;
            flex-shrink: 0;
            box-shadow: 0 0 22px rgba(0, 229, 255, .45);
            position: relative;
            overflow: hidden;
        }

        .admin-sidebar-logo-icon::after {
            content: '';
            position: absolute;
            inset: 7px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .admin-sidebar-logo-text {
            font-size: 17px;
            font-weight: 900;
            color: #fff;
            line-height: 1.2;
        }

        .admin-sidebar-logo-sub {
            font-size: 11px;
            color: #00e5ff;
            margin-top: 3px;
            text-shadow: 0 0 10px rgba(0, 229, 255, .45);
        }

        .admin-sidebar-nav {
            padding: 18px 12px 26px;
            flex: 1;
        }

        .admin-sidebar-section {
            padding: 16px 10px 7px;
            font-size: 11px;
            color: rgba(255, 255, 255, .42);
            font-weight: 900;
        }

        .admin-sidebar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 13px;
            margin-bottom: 7px;
            color: rgba(255, 255, 255, .72);
            border: 1px solid transparent;
            border-radius: 15px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 800;
            transition: all .3s cubic-bezier(.175, .885, .32, 1.275);
            position: relative;
        }

        .admin-sidebar-item i {
            width: 20px;
            text-align: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .admin-sidebar-item:hover {
            background: rgba(0, 229, 255, .09);
            border-color: rgba(0, 229, 255, .2);
            color: #00e5ff;
            transform: translateX(-4px);
            filter: drop-shadow(0 0 8px rgba(0, 229, 255, .45));
        }

        .admin-sidebar-item.active {
            background: #00e5ff;
            color: #000;
            border-color: #00e5ff;
            box-shadow: 0 0 18px rgba(0, 229, 255, .45);
        }

        .admin-sidebar-item.active::before {
            content: '';
            position: absolute;
            right: -13px;
            width: 4px;
            height: 60%;
            border-radius: 999px;
            background: #00e5ff;
            box-shadow: 0 0 12px rgba(0, 229, 255, .7);
        }

        .admin-sidebar-footer {
            margin: 0 12px 16px;
            padding: 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .72);
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .admin-sidebar-footer i {
            color: #25d366;
            font-size: 18px;
            filter: drop-shadow(0 0 8px rgba(37, 211, 102, .55));
        }

        @media(max-width: 900px) {
            .sidebar.admin-neon-sidebar {
                display: none;
            }
        }
    </style>
@endonce

@php
    $currentUser = auth()->user();
    $isSuperAdmin = $currentUser?->isSuperAdmin();
    $isAgent = $currentUser?->isAgent();
    $isDistributor = $currentUser?->isDistributor();
    $isMarketer = $currentUser?->isMarketer();
    $isEmployee = $currentUser?->isEmployee();
    $hasAssignedPermissions = $currentUser?->hasAssignedPermissions() ?? false;
    $isPermissionManaged = $isEmployee || (($isAgent || $isDistributor || $isMarketer) && $hasAssignedPermissions);
    $isCatalogOnlyUser = ($isAgent || $isDistributor) && ! $hasAssignedPermissions;
    $previewShopId = request()->integer('shop_id') ?: session('current_shop_id');
    $canSee = fn(array $routes) => ! $isPermissionManaged || $currentUser?->canAccessAnyRoute($routes);

    foreach (request()->route()?->parameters() ?? [] as $parameter) {
        if (! $parameter instanceof \Illuminate\Database\Eloquent\Model) {
            continue;
        }

        $routeShopId = $parameter instanceof \App\Models\Shop
            ? $parameter->getKey()
            : $parameter->getAttribute('shop_id');

        if ($routeShopId) {
            $previewShopId = (int) $routeShopId;
            break;
        }
    }

    if (! $isSuperAdmin) {
        $ownedPreviewShopIds = auth()->user()?->accessibleShopIds() ?? [];
        if ($previewShopId && ! in_array((int) $previewShopId, $ownedPreviewShopIds, true)) {
            $previewShopId = null;
        }
    }

    if (! $previewShopId) {
        $previewShopId = auth()->user()?->accessibleShopIds()[0] ?? null;
    }
@endphp

<div class="sidebar admin-neon-sidebar">
    <div class="admin-sidebar-logo">
        <div class="admin-sidebar-logo-icon">O</div>
        <div>
            <div class="admin-sidebar-logo-text">Ozman</div>
            <div class="admin-sidebar-logo-sub">لوحة التحكم</div>
        </div>
    </div>

    <nav class="admin-sidebar-nav">
        <div class="admin-sidebar-section">عام</div>

        @if($canSee(['dashboard']))
        <a href="{{ route('dashboard') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
             <i class="ti ti-layout-dashboard" aria-hidden="true"></i>
             الرئيسية
        </a>
        @endif

        <div class="admin-sidebar-section">المتجر</div>

        @if($isMarketer)
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
        <a href="{{ route('shops') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('shops') || request()->routeIs('shops.*') ? 'active' : '' }}">
             <i class="ti ti-building-store" aria-hidden="true"></i>
             المتاجر
        </a>
        @endif

        @if($canSee(['products', 'products.show', 'products.create', 'products.edit']))
        <a href="{{ route('products') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('products') || request()->routeIs('products.create') || request()->routeIs('products.edit') || request()->routeIs('products.show') ? 'active' : '' }}">
             <i class="ti ti-package" aria-hidden="true"></i>
             المنتجات
        </a>
        @endif

        @if($canSee(['products.preview']))
        <a href="{{ route('products.preview', $previewShopId ? ['shop_id' => $previewShopId] : []) }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('products.preview') ? 'active' : '' }}">
             <i class="ti ti-eye" aria-hidden="true"></i>
             معاينة المتجر
        </a>
        @endif

        @if($canSee(['categories', 'categories.show', 'categories.create', 'categories.edit']))
        <a href="{{ route('categories') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('categories*') ? 'active' : '' }}">
             <i class="ti ti-category" aria-hidden="true"></i>
             الفئات
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

        @if($canSee(['front-orders.index']))
        <a href="{{ route('front-orders.index') }}"
             class="admin-sidebar-item nav-item {{ request()->routeIs('front-orders.index') ? 'active' : '' }}">
             <i class="ti ti-receipt-2" aria-hidden="true"></i>
             طلبات الواجهة
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
        <a href="{{ route('reward-wheels.purchase.index') }}"
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
