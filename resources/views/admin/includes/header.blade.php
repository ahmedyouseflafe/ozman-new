@include('admin.analytics.notifications')
@once
    @viewStyle('admin.includes.header')
@endonce

@php
    // $adminNotifications and $adminUnreadNotificationsCount come from App\View\Composers\AdminHeaderComposer.
    $currentUser = auth()->user();

    $notificationIcons = [
        'shop_created' => 'ti ti-building-store',
        'product_created' => 'ti ti-package',
        'product_out_of_stock' => 'ti ti-package-off',
        'category_created' => 'ti ti-category',
        'advertisement_created' => 'ti ti-speakerphone',
        'campaign_created' => 'ti ti-ad',
        'front_order_created' => 'ti ti-receipt-2',
        'front_order_reward_won' => 'ti ti-gift',
        'user_created' => 'ti ti-user-plus',
    ];
@endphp

<div class="topbar admin-neon-header">
    <div class="admin-header-title-wrap">
        <div class="admin-header-pulse">
            <i class="ti ti-bolt" aria-hidden="true"></i>
        </div>
        <div>
            <div class="topbar-title">
                {{ $title ?? trim($__env->yieldContent('title')) ?: 'لوحة التحكم' }}
            </div>
            <span class="admin-header-subtitle">لوحة تحكم أوزمان</span>
        </div>
    </div>

    <div class="topbar-right">
        @if(session()->has('impersonator_admin_id'))
            <form method="POST" action="{{ route('admin.return-from-shop') }}">
                @csrf
                <button type="submit" class="topbar-btn"
                    style="width:auto;padding:0 14px;border-radius:20px;gap:7px;color:#ffd60a;border-color:rgba(255,214,10,.45)"
                    title="الرجوع إلى لوحة الأدمن">
                    <i class="ti ti-arrow-back-up" aria-hidden="true"></i>
                    <span style="font-size:11px;font-weight:900">الرجوع للأدمن</span>
                </button>
            </form>
        @endif

        <div class="admin-header-search">
            <i class="ti ti-search" aria-hidden="true"></i>
            <input type="search" placeholder="ابحث عن صفحة…" list="adminPageSearchList" data-admin-page-search aria-label="ابحث عن صفحة في لوحة التحكم">
            <datalist id="adminPageSearchList"></datalist>
            @viewScript('admin.includes.header')
        </div>

        <a href="{{ route('lang.switch', app()->getLocale() === 'ar' ? 'he' : 'ar') }}"
           class="topbar-btn"
           aria-label="اللغة"
           style="width: auto; padding: 0 12px; border-radius: 20px; font-size: 11px; font-weight: 800; gap: 5px;">
            <i class="ti ti-world"></i>
            {{ app()->getLocale() === 'ar' ? 'HE' : 'AR' }}
        </a>

        @if($currentUser?->isSuperAdmin())
            <div class="admin-notifications" data-admin-notifications>
                <button type="button" class="topbar-btn" aria-label="الإشعارات" data-admin-notifications-toggle>
                    <i class="ti ti-bell" aria-hidden="true"></i>
                    @if($adminUnreadNotificationsCount > 0)
                        <span class="admin-notifications-count">{{ $adminUnreadNotificationsCount > 99 ? '99+' : $adminUnreadNotificationsCount }}</span>
                    @else
                        <span class="admin-header-dot"></span>
                    @endif
                </button>

                <div class="admin-notifications-panel">
                    <div class="admin-notifications-head">
                        <div class="admin-notifications-title">إشعارات المتاجر</div>
                        <form method="POST" action="{{ route('admin.notifications.readAll') }}">
                            @csrf
                            <button type="submit" class="admin-notifications-read">تعيين الكل كمقروء</button>
                        </form>
                    </div>

                    <div class="admin-notifications-filters">
                        <button type="button" class="admin-notifications-filter active" data-notification-filter="all">الكل</button>
                        <button type="button" class="admin-notifications-filter" data-notification-filter="shop_created">متاجر</button>
                        <button type="button" class="admin-notifications-filter" data-notification-filter="product_created">منتجات</button>
                        <button type="button" class="admin-notifications-filter" data-notification-filter="product_out_of_stock">منتهية</button>
                        <button type="button" class="admin-notifications-filter" data-notification-filter="category_created">فئات</button>
                        <button type="button" class="admin-notifications-filter" data-notification-filter="advertisement_created">إعلانات</button>
                        <button type="button" class="admin-notifications-filter" data-notification-filter="campaign_created">حملات</button>
                        <button type="button" class="admin-notifications-filter" data-notification-filter="front_order_created">طلبات</button>
                        <button type="button" class="admin-notifications-filter" data-notification-filter="front_order_reward_won">هدايا</button>
                        <button type="button" class="admin-notifications-filter" data-notification-filter="user_created">مستخدمون</button>
                    </div>

                    <div class="admin-notifications-list">
                        @forelse($adminNotifications as $notification)
                            <a href="{{ $notification->url ?: '#' }}"
                               class="admin-notification-item {{ $notification->read_at ? '' : 'unread' }}"
                               data-notification-type="{{ $notification->type }}">
                                <span class="admin-notification-icon">
                                    <i class="{{ $notificationIcons[$notification->type] ?? 'ti ti-bell' }}"></i>
                                </span>
                                <span>
                                    <span class="admin-notification-title">{{ $notification->title }}</span>
                                    <span class="admin-notification-message">{{ $notification->message }}</span>
                                    <span class="admin-notification-meta">
                                        <span>{{ $notification->shop?->name ?? 'عام' }}</span>
                                        <span>{{ $notification->created_at?->diffForHumans() }}</span>
                                    </span>
                                </span>
                            </a>
                        @empty
                            <div class="admin-notifications-empty">لا توجد إشعارات بعد</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        @if($currentUser?->isSuperAdmin())
            <a href="{{ route('settings') }}" class="topbar-btn" aria-label="الإعدادات">
                <i class="ti ti-settings" aria-hidden="true"></i>
            </a>
        @endif

        <div class="admin-header-avatar" title="{{ $currentUser?->name ?? 'المشرف' }}">
            {{ mb_substr($currentUser?->name ?? 'A', 0, 1) }}
        </div>
    </div>
</div>

@viewScript('admin.includes.header-2')
