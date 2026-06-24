<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الإعدادات - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #00e5ff;
            --accent: #7000ff;
            --green: #25d366;
            --yellow: #ffd60a;
            --danger: #ff3b30;
            --bg: #050505;
            --border: rgba(255, 255, 255, .1);
            --text: #fff;
            --muted: rgba(255, 255, 255, .64);
            --dim: rgba(255, 255, 255, .4);
        }

        html,
        body {
            min-height: 100%;
            background:
                radial-gradient(circle at 15% 14%, rgba(112, 0, 255, .14), transparent 29%),
                radial-gradient(circle at 78% 8%, rgba(0, 229, 255, .14), transparent 34%),
                linear-gradient(180deg, #030303 0%, #050505 52%, #08020f 100%);
            color: var(--text);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            direction: rtl;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, .026) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .026) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, black, transparent 82%);
        }

        .shell { min-height: 100vh; }

        .main {
            min-height: 100vh;
            margin-right: 245px;
            position: relative;
            z-index: 1;
        }

        .content { padding: 28px 34px 46px; }

        .hero-strip {
            min-height: 136px;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: linear-gradient(90deg, rgba(0, 229, 255, .08), rgba(255, 255, 255, .035), rgba(112, 0, 255, .08));
            backdrop-filter: blur(18px);
            overflow: hidden;
            display: flex;
            align-items: center;
            box-shadow: 0 22px 60px rgba(0, 0, 0, .42), inset 0 0 45px rgba(0, 229, 255, .035);
            margin-bottom: 28px;
        }

        .ticker {
            display: flex;
            gap: 54px;
            width: max-content;
            white-space: nowrap;
            animation: slideRtl 24s linear infinite;
            color: var(--primary);
            font-size: 18px;
            font-weight: 900;
            text-shadow: 0 0 14px rgba(0, 229, 255, .55);
        }

        @keyframes slideRtl {
            from { transform: translateX(0); }
            to { transform: translateX(50%); }
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 22px;
        }

        .page-kicker {
            color: var(--primary);
            font-size: 13px;
            font-weight: 900;
            text-shadow: 0 0 12px rgba(0, 229, 255, .5);
            margin-bottom: 6px;
        }

        h1 {
            font-size: 34px;
            line-height: 1.1;
            font-weight: 900;
            color: var(--primary);
            text-shadow: 0 0 20px rgba(0, 229, 255, .42);
        }

        .page-head p {
            margin-top: 8px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .panel {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025));
            backdrop-filter: blur(16px);
            border-radius: 26px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .34);
            padding: 24px;
            min-height: 100%;
        }

        .panel-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 18px;
        }

        .panel-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid var(--primary);
            color: var(--primary);
            background: #000;
            display: grid;
            place-items: center;
            font-size: 20px;
            box-shadow: 0 0 18px rgba(0, 229, 255, .3);
            flex-shrink: 0;
        }

        .panel-title {
            color: #fff;
            font-size: 18px;
            font-weight: 900;
            line-height: 1.2;
        }

        .panel-subtitle {
            color: var(--dim);
            font-size: 12px;
            font-weight: 700;
            margin-top: 4px;
        }

        .form-grid {
            display: grid;
            gap: 14px;
        }

        .field label {
            display: block;
            color: rgba(255, 255, 255, .72);
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .field input,
        .field select {
            width: 100%;
            min-height: 46px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            border-radius: 16px;
            color: #fff;
            padding: 0 15px;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            transition: all .3s ease;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 18px rgba(0, 229, 255, .22);
        }

        .field input::placeholder { color: var(--dim); }

        .field select option {
            color: #111;
            background: #fff;
        }

        .btn-primary {
            border: 0;
            color: #001014;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            min-height: 46px;
            padding: 0 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 0 22px rgba(0, 229, 255, .34);
            transition: transform .3s ease, box-shadow .3s ease;
            margin-top: 4px;
            width: max-content;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 34px rgba(0, 229, 255, .58);
        }

        .toggle-list {
            display: grid;
            gap: 14px;
        }

        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: rgba(255, 255, 255, .035);
        }

        .toggle-title {
            color: #fff;
            font-size: 13px;
            font-weight: 900;
        }

        .toggle-subtitle {
            color: var(--dim);
            font-size: 11px;
            font-weight: 700;
            margin-top: 3px;
        }

        .switch {
            position: relative;
            width: 58px;
            height: 32px;
            flex-shrink: 0;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            inset: 0;
            cursor: pointer;
            background: rgba(255, 255, 255, .08);
            border: 1px solid var(--border);
            border-radius: 999px;
            transition: all .3s ease;
        }

        .slider::before {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            right: 4px;
            top: 3px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .72);
            transition: all .3s ease;
        }

        .switch input:checked + .slider {
            background: rgba(37, 211, 102, .22);
            border-color: var(--green);
            box-shadow: 0 0 18px rgba(37, 211, 102, .25);
        }

        .switch input:checked + .slider::before {
            transform: translateX(-26px);
            background: var(--green);
            box-shadow: 0 0 12px rgba(37, 211, 102, .7);
        }

        @media(max-width: 1100px) {
            .settings-grid { grid-template-columns: 1fr; }
        }

        @media(max-width: 900px) {
            .main { margin-right: 0; }
        }

        @media(max-width: 680px) {
            .content { padding: 20px 16px 34px; }
            .page-head { align-items: stretch; flex-direction: column; }
            h1 { font-size: 28px; }
            .btn-primary { width: 100%; }
        }
    </style>
</head>

<body>
    @php
        $adminName = $adminName ?? data_get(auth()->user(), 'name', 'Admin');
        $adminEmail = $adminEmail ?? data_get(auth()->user(), 'email', 'admin@ozman.com');
        $adminPhone = $adminPhone ?? '059-000-0001';
        $systemName = $systemName ?? 'Ozman';
        $defaultCurrency = $defaultCurrency ?? '₪ شيكل';
        $notificationSettings = $notificationSettings ?? [
            'new_shops' => true,
            'out_of_stock' => true,
            'new_users' => false,
        ];
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'الإعدادات'])

            <div class="content">
                <section class="hero-strip" aria-label="شريط الإعدادات">
                    <div class="ticker">
                        <span>إعدادات الحساب والنظام في مكان واحد</span>
                        <span>تحكم بالإشعارات، العملة، وبيانات لوحة Ozman</span>
                        <span>واجهة إدارة واضحة وسريعة لفريق المتجر</span>
                        <span>إعدادات الحساب والنظام في مكان واحد</span>
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">الإدارة</div>
                        <h1>الإعدادات</h1>
                        <p>إدارة بيانات الحساب، كلمة المرور، تفضيلات التنبيهات، وإعدادات النظام.</p>
                    </div>
                </header>

                @if (session('status'))
                    <div class="panel"
                        style="margin-bottom:18px;border-color:rgba(37,211,102,.35);background:rgba(37,211,102,.08);color:#fff;min-height:auto">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="panel"
                        style="margin-bottom:18px;border-color:rgba(255,59,48,.35);background:rgba(255,59,48,.08);color:#ffb4bd;min-height:auto">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <section class="settings-grid">
                    <form class="panel" action="{{ route('settings.profile.update') }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="panel-head">
                            <div class="panel-icon"><i class="ti ti-user" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="panel-title">الملف الشخصي</h2>
                                <p class="panel-subtitle">بيانات حساب المسؤول الظاهرة داخل لوحة التحكم</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label for="adminName">الاسم الكامل</label>
                                <input id="adminName" type="text" name="name" value="{{ old('name', $adminName) }}">
                            </div>
                            <div class="field">
                                <label for="adminEmail">البريد الإلكتروني</label>
                                <input id="adminEmail" type="email" name="email" value="{{ old('email', $adminEmail) }}" dir="ltr">
                            </div>
                            <div class="field">
                                <label for="adminPhone">رقم الهاتف</label>
                                <input id="adminPhone" type="tel" name="phone" value="{{ old('phone', $adminPhone) }}" dir="ltr">
                            </div>
                            <button class="btn-primary" type="submit">
                                <i class="ti ti-device-floppy" aria-hidden="true"></i>
                                حفظ التغييرات
                            </button>
                        </div>
                    </form>

                    <form class="panel" action="{{ route('settings.password.update') }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="panel-head">
                            <div class="panel-icon"><i class="ti ti-lock" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="panel-title">تغيير كلمة المرور</h2>
                                <p class="panel-subtitle">تحديث كلمة المرور الخاصة بحساب الإدارة</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label for="currentPassword">كلمة المرور الحالية</label>
                                <input id="currentPassword" type="password" name="current_password" placeholder="••••••••">
                            </div>
                            <div class="field">
                                <label for="newPassword">كلمة المرور الجديدة</label>
                                <input id="newPassword" type="password" name="password" placeholder="••••••••">
                            </div>
                            <div class="field">
                                <label for="confirmPassword">تأكيد كلمة المرور</label>
                                <input id="confirmPassword" type="password" name="password_confirmation" placeholder="••••••••">
                            </div>
                            <button class="btn-primary" type="submit">
                                <i class="ti ti-refresh" aria-hidden="true"></i>
                                تحديث كلمة المرور
                            </button>
                        </div>
                    </form>

                    <form class="panel" action="{{ route('settings.notifications.update') }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="panel-head">
                            <div class="panel-icon"><i class="ti ti-bell" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="panel-title">الإشعارات</h2>
                                <p class="panel-subtitle">اختيار أنواع التنبيهات التي تظهر للمسؤول</p>
                            </div>
                        </div>

                        <div class="toggle-list">
                            <label class="toggle-row">
                                <span>
                                    <span class="toggle-title">إشعارات المتاجر الجديدة</span>
                                    <span class="toggle-subtitle">تنبيه عند تسجيل متجر جديد داخل النظام</span>
                                </span>
                                <span class="switch">
                                    <input type="checkbox" name="new_shops" value="1" @checked(old('new_shops', $notificationSettings['new_shops'] ?? true))>
                                    <span class="slider"></span>
                                </span>
                            </label>

                            <label class="toggle-row">
                                <span>
                                    <span class="toggle-title">إشعارات المنتجات المنتهية</span>
                                    <span class="toggle-subtitle">تنبيه عند وصول الكمية إلى صفر</span>
                                </span>
                                <span class="switch">
                                    <input type="checkbox" name="out_of_stock" value="1" @checked(old('out_of_stock', $notificationSettings['out_of_stock'] ?? true))>
                                    <span class="slider"></span>
                                </span>
                            </label>

                            <label class="toggle-row">
                                <span>
                                    <span class="toggle-title">إشعارات المستخدمين الجدد</span>
                                    <span class="toggle-subtitle">تنبيه عند إنشاء حساب مستخدم جديد</span>
                                </span>
                                <span class="switch">
                                    <input type="checkbox" name="new_users" value="1" @checked(old('new_users', $notificationSettings['new_users'] ?? false))>
                                    <span class="slider"></span>
                                </span>
                            </label>
                        </div>
                        <button class="btn-primary" type="submit">
                            <i class="ti ti-device-floppy" aria-hidden="true"></i>
                            حفظ الإشعارات
                        </button>
                    </form>

                    <form class="panel" action="{{ route('settings.system.update') }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="panel-head">
                            <div class="panel-icon"><i class="ti ti-building-store" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="panel-title">إعدادات النظام</h2>
                                <p class="panel-subtitle">اسم النظام والعملة الافتراضية وبعض حالة المنصة</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label for="systemName">اسم النظام</label>
                                <input id="systemName" type="text" name="system_name" value="{{ old('system_name', $systemName) }}">
                            </div>
                            <div class="field">
                                <label for="currency">العملة الافتراضية</label>
                                <select id="currency" name="currency">
                                    <option @selected(old('currency', $defaultCurrency) === '₪ شيكل')>₪ شيكل</option>
                                    <option @selected(old('currency', $defaultCurrency) === '$ دولار')>$ دولار</option>
                                    <option @selected(old('currency', $defaultCurrency) === '€ يورو')>€ يورو</option>
                                </select>
                            </div>

                            <button class="btn-primary" type="submit">
                                <i class="ti ti-device-floppy" aria-hidden="true"></i>
                                حفظ الإعدادات
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</body>

</html>
