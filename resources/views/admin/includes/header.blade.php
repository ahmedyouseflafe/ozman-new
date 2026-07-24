@once
    <style>
        .topbar.admin-neon-header {
            background: rgba(0, 0, 0, .78);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, .1);
            padding: 0 28px;
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            position: sticky;
            top: 0;
            z-index: 30;
            box-shadow: 0 10px 35px rgba(0, 229, 255, .05);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
        }

        .admin-header-title-wrap,
        .topbar.admin-neon-header .topbar-right,
        .admin-notifications-head,
        .admin-notification-meta {
            display: flex;
            align-items: center;
        }

        .admin-header-title-wrap {
            gap: 12px;
            min-width: 0;
        }

        .topbar.admin-neon-header .topbar-right {
            gap: 12px;
            flex-shrink: 0;
        }

        .admin-header-pulse,
        .topbar.admin-neon-header .topbar-btn,
        .admin-header-avatar,
        .admin-notification-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .admin-header-pulse {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid #00e5ff;
            background: #000;
            color: #00e5ff;
            box-shadow: 0 0 18px rgba(0, 229, 255, .42);
            animation: adminHeaderFloat 3.5s ease-in-out infinite;
            flex-shrink: 0;
        }

        .topbar.admin-neon-header .topbar-title {
            color: #00e5ff;
            font-size: 16px;
            font-weight: 900;
            line-height: 1.2;
            text-shadow: 0 0 12px rgba(0, 229, 255, .45);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-header-subtitle {
            display: block;
            color: rgba(255, 255, 255, .45);
            font-size: 11px;
            font-weight: 700;
            margin-top: 2px;
        }

        .admin-header-search {
            position: relative;
        }

        .admin-header-search i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #00e5ff;
            font-size: 15px;
            pointer-events: none;
        }

        .admin-header-search input {
            width: 230px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            color: #fff;
            padding: 9px 14px 9px 38px;
            border-radius: 999px;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            transition: all .3s ease;
        }

        .admin-header-search input:focus {
            width: 270px;
            border-color: #00e5ff;
            box-shadow: 0 0 18px rgba(0, 229, 255, .24);
        }

        .admin-header-search input::placeholder {
            color: rgba(255, 255, 255, .42);
        }

        .topbar.admin-neon-header .topbar-btn {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            color: rgba(255, 255, 255, .72);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            transition: all .3s ease;
            text-decoration: none;
            position: relative;
            font-family: inherit;
        }

        .topbar.admin-neon-header .topbar-btn:hover {
            border-color: #00e5ff;
            color: #00e5ff;
            transform: translateY(-3px) scale(1.08);
            box-shadow: 0 0 16px rgba(0, 229, 255, .35);
        }

        .admin-header-dot,
        .admin-notifications-count {
            position: absolute;
            border-radius: 999px;
        }

        .admin-header-dot {
            top: 7px;
            right: 8px;
            width: 8px;
            height: 8px;
            background: #25d366;
            box-shadow: 0 0 10px rgba(37, 211, 102, .75);
        }

        .admin-notifications {
            position: relative;
        }

        .admin-notifications-count {
            top: -7px;
            right: -7px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            background: #ff3b30;
            color: #fff;
            font-size: 10px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 12px rgba(255, 59, 48, .55);
        }

        .admin-notifications-panel {
            position: absolute;
            top: calc(100% + 14px);
            left: 0;
            width: min(430px, calc(100vw - 32px));
            max-height: 520px;
            overflow: hidden;
            background: rgba(5, 8, 12, .97);
            border: 1px solid rgba(0, 229, 255, .22);
            border-radius: 22px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, .65), 0 0 30px rgba(0, 229, 255, .12);
            opacity: 0;
            pointer-events: none;
            transform: translateY(8px);
            transition: all .22s ease;
            z-index: 80;
            direction: rtl;
        }

        .admin-notifications.open .admin-notifications-panel {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .admin-notifications-head {
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .admin-notifications-title {
            color: #fff;
            font-weight: 900;
            font-size: 14px;
        }

        .admin-notifications-read {
            border: 0;
            background: transparent;
            color: #00e5ff;
            font-family: inherit;
            font-weight: 800;
            cursor: pointer;
            font-size: 11px;
        }

        .admin-notifications-filters {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            overflow-x: auto;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .admin-notifications-filter {
            border: 1px solid rgba(255, 255, 255, .1);
            background: rgba(255, 255, 255, .05);
            color: rgba(255, 255, 255, .78);
            border-radius: 999px;
            padding: 7px 12px;
            font-family: inherit;
            font-size: 11px;
            font-weight: 900;
            cursor: pointer;
            white-space: nowrap;
        }

        .admin-notifications-filter.active {
            color: #000;
            border-color: #00e5ff;
            background: #00e5ff;
            box-shadow: 0 0 15px rgba(0, 229, 255, .35);
        }

        .admin-notifications-list {
            max-height: 355px;
            overflow-y: auto;
            padding: 8px;
        }

        .admin-notification-item {
            display: grid;
            grid-template-columns: 36px 1fr;
            gap: 10px;
            padding: 12px;
            border-radius: 16px;
            text-decoration: none;
            color: inherit;
            border: 1px solid transparent;
        }

        .admin-notification-item:hover {
            border-color: rgba(0, 229, 255, .2);
            background: rgba(0, 229, 255, .06);
        }

        .admin-notification-item.unread {
            background: rgba(0, 229, 255, .05);
        }

        .admin-notification-icon {
            width: 36px;
            height: 36px;
            border-radius: 14px;
            background: rgba(0, 229, 255, .12);
            color: #00e5ff;
        }

        .admin-notification-title {
            color: #fff;
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 4px;
            display: block;
        }

        .admin-notification-message {
            color: rgba(255, 255, 255, .68);
            font-size: 11px;
            line-height: 1.6;
            display: block;
        }

        .admin-notification-meta {
            justify-content: space-between;
            gap: 10px;
            color: rgba(255, 255, 255, .42);
            font-size: 10px;
            font-weight: 800;
            margin-top: 6px;
        }

        .admin-notifications-empty {
            padding: 28px 16px;
            text-align: center;
            color: rgba(255, 255, 255, .48);
            font-size: 12px;
            font-weight: 800;
        }

        .admin-header-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #000;
            border: 1px solid #00e5ff;
            color: #00e5ff;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 0 16px rgba(0, 229, 255, .3);
        }

        @keyframes adminHeaderFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @media(max-width: 760px) {
            .topbar.admin-neon-header {
                padding: 0 16px;
            }

            .admin-header-search,
            .admin-header-subtitle {
                display: none;
            }
        }
    </style>
@endonce

@php
    $adminNotifications = collect();
    $adminUnreadNotificationsCount = 0;
    $currentUser = auth()->user();

    if ($currentUser?->isSuperAdmin()) {
        $adminNotifications = \App\Models\AdminNotification::query()
            ->with(['shop', 'user'])
            ->latest()
            ->limit(30)
            ->get();

        $adminUnreadNotificationsCount = \App\Models\AdminNotification::query()
            ->whereNull('read_at')
            ->count();
    }

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
            <span class="admin-header-subtitle">Ozman admin panel</span>
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
            <input type="search" placeholder="بحث سريع...">
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const wrapper = document.querySelector('[data-admin-notifications]');
        if (!wrapper) return;

        const toggle = wrapper.querySelector('[data-admin-notifications-toggle]');
        const filters = wrapper.querySelectorAll('[data-notification-filter]');
        const items = wrapper.querySelectorAll('[data-notification-type]');

        toggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            wrapper.classList.toggle('open');
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                wrapper.classList.remove('open');
            }
        });

        filters.forEach((filter) => {
            filter.addEventListener('click', () => {
                const type = filter.dataset.notificationFilter;

                filters.forEach((item) => item.classList.remove('active'));
                filter.classList.add('active');

                items.forEach((item) => {
                    item.style.display = type === 'all' || item.dataset.notificationType === type ? 'grid' : 'none';
                });
            });
        });
    });
</script>
