<div class="sidebar">

    <div class="logo">
        <div class="logo-icon">H</div>

        <div>
            <div class="logo-text">
                Healthy Shop
            </div>

            <div class="logo-sub">
                لوحة التحكم
            </div>
        </div>
    </div>

    <nav>

        <div class="nav-section">عام</div>

        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="ti ti-layout-dashboard"></i>
            الرئيسية
        </a>

        <div class="nav-section">المتجر</div>

        <a href="{{ route('shops') }}"
           class="nav-item {{ request()->routeIs('shops') ? 'active' : '' }}">
            <i class="ti ti-building-store"></i>
            المتاجر
        </a>

        <a href="{{ route('products') }}"
           class="nav-item {{ request()->routeIs('products') ? 'active' : '' }}">
            <i class="ti ti-package"></i>
            المنتجات
        </a>

        <a href="{{ route('categories') }}"
           class="nav-item {{ request()->routeIs('categories') ? 'active' : '' }}">
            <i class="ti ti-category"></i>
            الفئات
        </a>

        <div class="nav-section">الإعلانات</div>

        <a href="{{ route('ads') }}"
           class="nav-item {{ request()->routeIs('ads') ? 'active' : '' }}">
            <i class="ti ti-speakerphone"></i>
            الإعلانات
        </a>

        <a href="{{ route('screens') }}"
           class="nav-item {{ request()->routeIs('screens') ? 'active' : '' }}">
            <i class="ti ti-device-tv"></i>
            الشاشات
        </a>

        <div class="nav-section">الإدارة</div>

        <a href="{{ route('users') }}"
           class="nav-item {{ request()->routeIs('users') ? 'active' : '' }}">
            <i class="ti ti-users"></i>
            المستخدمون
        </a>

        <a href="{{ route('settings') }}"
           class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}">
            <i class="ti ti-settings"></i>
            الإعدادات
        </a>

    </nav>

</div>
