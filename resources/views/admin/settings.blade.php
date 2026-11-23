<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الإعدادات - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.settings')
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
                        <p>{{ auth()->user()?->isSuperAdmin() ? 'إدارة بيانات الحساب، كلمة المرور، تفضيلات التنبيهات، وإعدادات النظام.' : 'اسمك وإيميلك ورقمك، وكلمة المرور.' }}</p>
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
                                <p class="panel-subtitle">الاسم والإيميل والرقم اللي بتدخل فيهم على لوحة التحكم</p>
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
                                <p class="panel-subtitle">غيّر كلمة المرور اللي بتدخل فيها</p>
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

                    @if(auth()->user()?->canAccessRouteName('settings.notifications.update'))
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
                    @endif

                    @if(auth()->user()?->canAccessRouteName('settings.system.update'))
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
                    @endif

                    @if(auth()->user()?->canAccessRouteName('settings.loyalty.update'))
                    <form class="panel" action="{{ route('settings.loyalty.update') }}" method="post" id="loyalty">
                        @csrf
                        @method('PUT')
                        <div class="panel-head">
                            <div class="panel-icon"><i class="ti ti-star" aria-hidden="true"></i></div>
                            <div>
                                <h2 class="panel-title">{{ __('برنامج النقاط') }}</h2>
                                <p class="panel-subtitle">{{ __('نقاط ولاء لعملاء المجتمع المسجّلين على مشترياتهم.') }}</p>
                            </div>
                        </div>

                        <div class="toggle-list">
                            <label class="toggle-row">
                                <span>
                                    <span class="toggle-title">{{ __('تفعيل البرنامج') }}</span>
                                    <span class="toggle-subtitle">{{ __('عند الإيقاف الطلبات الجديدة ما بتاخد نقاط. النقاط الموجودة بتضل.') }}</span>
                                </span>
                                <span class="switch">
                                    <input type="checkbox" name="loyalty_enabled" value="1" @checked(old('loyalty_enabled', $loyaltySettings['enabled']))>
                                    <span class="slider"></span>
                                </span>
                            </label>
                        </div>

                        <div class="form-grid">
                            <div class="field">
                                <label for="loyaltyAmount">{{ __('كل مبلغ (₪)') }}</label>
                                <input id="loyaltyAmount" type="number" name="loyalty_amount" min="1" max="100000" step="1" inputmode="numeric" required value="{{ old('loyalty_amount', $loyaltySettings['amount']) }}">
                            </div>
                            <div class="field">
                                <label for="loyaltyPoints">{{ __('بيعطي نقاط') }}</label>
                                <input id="loyaltyPoints" type="number" name="loyalty_points" min="1" max="100000" step="1" inputmode="numeric" required value="{{ old('loyalty_points', $loyaltySettings['points']) }}">
                            </div>

                            <button class="btn-primary" type="submit">
                                <i class="ti ti-device-floppy" aria-hidden="true"></i>
                                {{ __('حفظ إعدادات النقاط') }}
                            </button>
                        </div>
                    </form>
                    @endif
                </section>
            </div>
        </main>
    </div>
</body>

</html>
