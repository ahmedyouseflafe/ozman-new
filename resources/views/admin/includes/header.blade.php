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

        .admin-header-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .admin-header-pulse {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid #00e5ff;
            background: #000;
            color: #00e5ff;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .topbar.admin-neon-header .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all .3s ease;
            text-decoration: none;
            position: relative;
        }

        .topbar.admin-neon-header .topbar-btn:hover {
            border-color: #00e5ff;
            color: #00e5ff;
            transform: translateY(-3px) scale(1.08);
            box-shadow: 0 0 16px rgba(0, 229, 255, .35);
        }

        .admin-header-dot {
            position: absolute;
            top: 7px;
            right: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #25d366;
            box-shadow: 0 0 10px rgba(37, 211, 102, .75);
        }

        .admin-header-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #000;
            border: 1px solid #00e5ff;
            color: #00e5ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 0 16px rgba(0, 229, 255, .3);
        }

        @keyframes adminHeaderFloat {
            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-4px);
            }
        }

        @media(max-width: 760px) {
            .topbar.admin-neon-header {
                padding: 0 16px;
            }

            .admin-header-search {
                display: none;
            }

            .admin-header-subtitle {
                display: none;
            }
        }
    </style>
@endonce

<div class="topbar admin-neon-header">
    <div class="admin-header-title-wrap">
        <div class="admin-header-pulse">
            <i class="ti ti-bolt" aria-hidden="true"></i>
        </div>
        <div>
            <div class="topbar-title">
                {{ $title ?? trim($__env->yieldContent('title')) ?: __('لوحة التحكم') }}
            </div>
            <span class="admin-header-subtitle">{{ __('Ozman admin panel') }}</span>
        </div>
    </div>

    <div class="topbar-right">
        <div class="admin-header-search">
            <i class="ti ti-search" aria-hidden="true"></i>
            <input type="search" placeholder="{{ __('بحث سريع...') }}">
        </div>

        <!-- تبديل اللغة -->
        <a href="{{ route('lang.switch', app()->getLocale() === 'ar' ? 'he' : 'ar') }}" class="topbar-btn" aria-label="{{ __('اللغة') }}" style="width: auto; padding: 0 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
            <i class="ti ti-world"></i>
            {{ app()->getLocale() === 'ar' ? 'HE' : 'AR' }}
        </a>

        <button type="button" class="topbar-btn" aria-label="{{ __('إشعارات') }}">
            <i class="ti ti-bell" aria-hidden="true"></i>
            <span class="admin-header-dot"></span>
        </button>

        <a href="{{ route('settings') }}" class="topbar-btn" aria-label="{{ __('الإعدادات') }}">
            <i class="ti ti-settings" aria-hidden="true"></i>
        </a>

        <div class="admin-header-avatar" title="{{ __('المشرف') }}">A</div>
    </div>
</div>
