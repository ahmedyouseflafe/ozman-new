<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>لوحة التحكم الرئيسية - Ozman</title>
    @viewStyle('admin.dashboard_main')
</head>

<body>

    <h2 class="sr-only">لوحة تحكم هيلني شوب - نظرة عامة على المتجر والمنتجات والمستخدمين</h2>

    <div class="shell dashboard-main-shell">
        @include('admin.includes.sidebar')
        <div class="main">
            <div class="topbar">
                <div class="topbar-title" id="page-title">لوحة التحكم</div>
                <div class="topbar-right">
                    <div class="input-wrap">
                        <i class="ti ti-search search-icon" aria-hidden="true"></i>
                        <input class="search-inp" placeholder="بحث...">
                    </div>
                    <button class="topbar-btn" aria-label="إشعارات"><i class="ti ti-bell"
                            aria-hidden="true"></i></button>
                    <button class="topbar-btn" aria-label="إعدادات"><i class="ti ti-settings"
                            aria-hidden="true"></i></button>
                    <div class="avatar" title="المشرف">A</div>
                </div>
            </div>

            <div class="content">

                <!-- DASHBOARD -->
                <div class="page active" id="page-dashboard">
                    <div class="page-header">
                        <h1>نظرة عامة</h1>
                        <p>مرحباً بك — آخر تحديث اليوم</p>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card" style="--accent:#E74C3C">
                            <div class="stat-label">إجمالي المتاجر</div>
                            <div class="stat-val">{{ $shopsCount ?? 0 }}</div>
                            <div class="stat-sub stat-up">{{ $shopsDelta ?? '' }}</div>
                            <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                        </div>
                        <div class="stat-card" style="--accent:#8E44AD">
                            <div class="stat-label">المنتجات النشطة</div>
                            <div class="stat-val">{{ $activeProductsCount ?? 0 }}</div>
                            <div class="stat-sub stat-up">{{ $activeProductsDelta ?? '' }}</div>
                            <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                        </div>
                        <div class="stat-card" style="--accent:#2980B9">
                            <div class="stat-label">إجمالي المستخدمين</div>
                            <div class="stat-val">{{ $usersCount ?? 0 }}</div>
                            <div class="stat-sub stat-up">{{ $usersDelta ?? '' }}</div>
                            <i class="ti ti-users stat-icon" aria-hidden="true"></i>
                        </div>
                        <div class="stat-card" style="--accent:#16A085">
                            <div class="stat-label">الوكلاء والموزعون</div>
                            <div class="stat-val">{{ $agentsAndDistributorsCount ?? 0 }}</div>
                            <div class="stat-sub stat-down">{{ $agentsStatusDelta ?? '' }}</div>
                            <i class="ti ti-truck stat-icon" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="grid2">
                        <div class="card">
                            <div class="card-hd">
                                <h3>المبيعات الشهرية</h3><span class="badge">{{ $salesYear ?? date('Y') }}</span>
                            </div>
                            <div class="chart-bars" id="bars">
                                @forelse($monthlySales ?? [] as $item)
                                    <div class="bar-wrap">
                                        <div class="bar"
                                            style="height:{{ min(100, max(0, data_get($item, 'value', 0))) }}%"></div>
                                        <div class="bar-label">{{ data_get($item, 'label', '') }}</div>
                                    </div>
                                @empty
                                    <div
                                        style="width:100%;color:var(--text3);font-size:12px;text-align:center;padding:24px 0">
                                        لا توجد بيانات مبيعات</div>
                                @endforelse
                            </div>
                            <div style="display:flex;justify-content:space-between;margin-top:4px">
                                <span
                                    style="font-size:10px;color:var(--text3)">{{ $salesRangeStart ?? 'بداية الشهر' }}</span>
                                <span
                                    style="font-size:10px;color:var(--text3)">{{ $salesRangeEnd ?? 'نهاية الشهر' }}</span>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-hd">
                                <h3>آخر المنتجات المضافة</h3><button class="btn-red"
                                    onclick="showPage('products',null)"><i class="ti ti-plus" aria-hidden="true"></i>
                                    إضافة</button>
                            </div>
                            <div class="recent-list">
                                @forelse($recentProducts ?? [] as $product)
                                    <div class="recent-item">
                                        <div class="ri-icon"><i class="ti ti-package" aria-hidden="true"></i></div>
                                        <div class="ri-info">
                                            <div class="ri-title">{{ data_get($product, 'name', '-') }}</div>
                                            <div class="ri-sub">{{ data_get($product, 'category', '-') }} ·
                                                {{ data_get($product, 'added_at_label', '') }}</div>
                                        </div>
                                        <span class="ri-val">{{ data_get($product, 'price_formatted', '-') }}</span>
                                    </div>
                                @empty
                                    <div class="recent-item">
                                        <div class="ri-info">
                                            <div class="ri-title">لا توجد منتجات جديدة</div>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hd">
                            <h3>آخر المتاجر المسجلة</h3><span
                                class="badge">{{ $recentShopsLabel ?? 'آخر تحديث' }}</span>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>اسم المتجر</th>
                                    <th>المدينة</th>
                                    <th>المنتجات</th>
                                    <th>الحالة</th>
                                    <th>تاريخ التسجيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestShops ?? [] as $shop)
                                    <tr>
                                        <td>{{ data_get($shop, 'name', '-') }}</td>
                                        <td>{{ data_get($shop, 'city', '-') }}</td>
                                        <td>{{ data_get($shop, 'products_count', '-') }}</td>
                                        <td><span
                                                class="tag {{ data_get($shop, 'status_class', 'tag-g') }}">{{ data_get($shop, 'status_label', '-') }}</span>
                                        </td>
                                        <td>{{ data_get($shop, 'registered_at', '-') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align:center;color:var(--text3)">لا توجد متاجر
                                            مسجلة حالياً</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SHOPS -->
                <div class="page" id="page-shops">
                    <div class="page-header"
                        style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h1>إدارة المتاجر</h1>
                            <p>{{ $shopsCount ?? 0 }} متجر مسجل في النظام</p>
                        </div>
                        <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> متجر جديد</button>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">إجمالي المتاجر</div>
                            <div class="stat-val">{{ $shopsCount ?? 0 }}</div><i
                                class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">نشط</div>
                            <div class="stat-val" style="color:#27AE60">{{ $activeShopsCount ?? 0 }}</div><i
                                class="ti ti-check stat-icon" aria-hidden="true"></i>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">معلق</div>
                            <div class="stat-val" style="color:#F1C40F">{{ $pendingShopsCount ?? 0 }}</div><i
                                class="ti ti-clock stat-icon" aria-hidden="true"></i>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">غير نشط</div>
                            <div class="stat-val" style="color:var(--red-light)">{{ $inactiveShopsCount ?? 0 }}</div>
                            <i class="ti ti-x stat-icon" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hd">
                            <h3>قائمة المتاجر</h3>
                            <div class="input-wrap"><i class="ti ti-search search-icon" aria-hidden="true"></i><input
                                    class="search-inp" placeholder="بحث بالاسم..."></div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>الشعار</th>
                                    <th>الاسم</th>
                                    <th>المدينة</th>
                                    <th>الهاتف</th>
                                    <th>المنتجات</th>
                                    <th>أوقات العمل</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shops ?? [] as $shop)
                                    <tr>
                                        <td>
                                            <div
                                                style="width:30px;height:30px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">
                                                {{ strtoupper(mb_substr(data_get($shop, 'name', ''), 0, 1)) }}</div>
                                        </td>
                                        <td style="color:var(--text);font-weight:500">
                                            {{ data_get($shop, 'name', '-') }}</td>
                                        <td>{{ data_get($shop, 'city', '-') }}</td>
                                        <td dir="ltr">{{ data_get($shop, 'phone', '-') }}</td>
                                        <td>{{ data_get($shop, 'products_count', '-') }}</td>
                                        <td>{{ data_get($shop, 'hours', '-') }}</td>
                                        <td><span
                                                class="tag {{ data_get($shop, 'status_class', 'tag-g') }}">{{ data_get($shop, 'status_label', '-') }}</span>
                                        </td>
                                        <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                                    class="ti ti-edit" aria-hidden="true"></i></button><button
                                                class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                                    aria-hidden="true"></i></button></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" style="text-align:center;color:var(--text3)">لا توجد متاجر
                                            لعرضها</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PRODUCTS -->
                <div class="page" id="page-products">
                    <div class="page-header"
                        style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h1>إدارة المنتجات</h1>
                            <p>{{ $productsCount ?? 0 }} منتج في جميع المتاجر</p>
                        </div>
                        <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> منتج جديد</button>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">إجمالي المنتجات</div>
                            <div class="stat-val">{{ $productsCount ?? 0 }}</div><i class="ti ti-package stat-icon"
                                aria-hidden="true"></i>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">المميزة</div>
                            <div class="stat-val" style="color:var(--gold)">{{ $featuredProductsCount ?? 0 }}</div><i
                                class="ti ti-star stat-icon" aria-hidden="true"></i>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">نفد المخزون</div>
                            <div class="stat-val" style="color:var(--red-light)">{{ $outOfStockCount ?? 0 }}</div><i
                                class="ti ti-alert-triangle stat-icon" aria-hidden="true"></i>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">متوسط السعر</div>
                            <div class="stat-val">{{ $averagePrice ?? '-' }}</div><i class="ti ti-coin stat-icon"
                                aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hd">
                            <h3>قائمة المنتجات</h3>
                            <div style="display:flex;gap:8px">
                                <select
                                    style="background:var(--bg3);border:1px solid var(--border2);color:var(--text2);padding:5px 8px;border-radius:6px;font-size:12px">
                                    <option>كل الفئات</option>
                                </select>
                                <div class="input-wrap"><i class="ti ti-search search-icon"
                                        aria-hidden="true"></i><input class="search-inp" placeholder="بحث..."></div>
                            </div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>الصورة</th>
                                    <th>الاسم</th>
                                    <th>الفئة</th>
                                    <th>السعر</th>
                                    <th>سعر الخصم</th>
                                    <th>الكمية</th>
                                    <th>التقييم</th>
                                    <th>مميز</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products ?? [] as $product)
                                    <tr>
                                        <td>
                                            <div
                                                style="width:32px;height:32px;border-radius:6px;background:var(--bg4);display:flex;align-items:center;justify-content:center">
                                                <i class="ti ti-box" style="color:var(--red-light);font-size:16px"
                                                    aria-hidden="true"></i>
                                            </div>
                                        </td>
                                        <td style="color:var(--text);font-weight:500">
                                            {{ data_get($product, 'name', '-') }}</td>
                                        <td>{{ data_get($product, 'category', '-') }}</td>
                                        <td style="color:var(--red-light);font-weight:600">
                                            {{ data_get($product, 'price_formatted', '-') }}</td>
                                        <td style="color:#27AE60">
                                            {{ data_get($product, 'discount_price_formatted', '—') }}</td>
                                        <td>{{ data_get($product, 'quantity', '-') }}</td>
                                        <td>{{ data_get($product, 'rating', '-') }}</td>
                                        <td><span
                                                class="tag {{ data_get($product, 'featured_class', 'tag-r') }}">{{ data_get($product, 'featured_label', '-') }}</span>
                                        </td>
                                        <td><span
                                                class="tag {{ data_get($product, 'status_class', 'tag-g') }}">{{ data_get($product, 'status_label', '-') }}</span>
                                        </td>
                                        <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                                    class="ti ti-edit" aria-hidden="true"></i></button><button
                                                class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                                    aria-hidden="true"></i></button></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" style="text-align:center;color:var(--text3)">لا توجد منتجات
                                            لعرضها</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CATEGORIES -->
                <div class="page" id="page-categories">
                    <div class="page-header"
                        style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h1>الفئات</h1>
                            <p>تصنيف منتجات المتاجر</p>
                        </div>
                        <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> فئة جديدة</button>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                        @forelse($categories ?? [] as $category)
                            <div class="card" style="text-align:center">
                                <div
                                    style="width:50px;height:50px;border-radius:12px;background:rgba(192,57,43,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 10px">
                                    <i class="ti ti-tag" style="font-size:22px;color:var(--red-light)"
                                        aria-hidden="true"></i>
                                </div>
                                <div style="font-size:14px;font-weight:600;color:var(--text)">
                                    {{ data_get($category, 'name', '-') }}</div>
                                <div style="font-size:11px;color:var(--text3);margin:4px 0 10px">
                                    {{ data_get($category, 'products_count', 0) }} منتج</div>
                                <div style="display:flex;gap:6px;justify-content:center">
                                    <button class="topbar-btn" aria-label="تعديل"><i class="ti ti-edit"
                                            aria-hidden="true"></i></button>
                                    <button class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                            aria-hidden="true"></i></button>
                                </div>
                            </div>
                        @empty
                            <div class="card" style="text-align:center;grid-column:1/-1;color:var(--text3)">لا توجد
                                فئات لعرضها</div>
                        @endforelse
                    </div>
                </div>

                <!-- AGENTS -->
                <div class="page" id="page-agents">
                    <div class="page-header"
                        style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h1>الوكلاء</h1>
                            <p>إدارة وكلاء المبيعات</p>
                        </div>
                        <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> وكيل جديد</button>
                    </div>
                    <div class="card">
                        <table>
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>الهاتف</th>
                                    <th>واتساب</th>
                                    <th>البريد</th>
                                    <th>المدينة</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agents ?? [] as $agent)
                                    <tr>
                                        <td style="color:var(--text);font-weight:500">
                                            {{ data_get($agent, 'name', '-') }}</td>
                                        <td dir="ltr">{{ data_get($agent, 'phone', '-') }}</td>
                                        <td dir="ltr">{{ data_get($agent, 'whatsapp', '-') }}</td>
                                        <td>{{ data_get($agent, 'email', '-') }}</td>
                                        <td>{{ data_get($agent, 'city', '-') }}</td>
                                        <td><span
                                                class="tag {{ data_get($agent, 'status_class', 'tag-g') }}">{{ data_get($agent, 'status_label', '-') }}</span>
                                        </td>
                                        <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                                    class="ti ti-edit" aria-hidden="true"></i></button><button
                                                class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                                    aria-hidden="true"></i></button></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="text-align:center;color:var(--text3)">لا توجد وكلاء
                                            لعرضهم</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- DISTRIBUTORS -->
                <div class="page" id="page-distributors">
                    <div class="page-header"
                        style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h1>الموزعون</h1>
                            <p>شبكة توزيع المنتجات</p>
                        </div>
                        <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> موزع جديد</button>
                    </div>
                    <div class="card">
                        <table>
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>الهاتف</th>
                                    <th>المدينة</th>
                                    <th>المتجر</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($distributors ?? [] as $distributor)
                                    <tr>
                                        <td style="color:var(--text);font-weight:500">
                                            {{ data_get($distributor, 'name', '-') }}</td>
                                        <td dir="ltr">{{ data_get($distributor, 'phone', '-') }}</td>
                                        <td>{{ data_get($distributor, 'city', '-') }}</td>
                                        <td>{{ data_get($distributor, 'shop_name', '-') }}</td>
                                        <td><span
                                                class="tag {{ data_get($distributor, 'status_class', 'tag-g') }}">{{ data_get($distributor, 'status_label', '-') }}</span>
                                        </td>
                                        <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                                    class="ti ti-edit" aria-hidden="true"></i></button><button
                                                class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                                    aria-hidden="true"></i></button></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:var(--text3)">لا توجد موزعين
                                            لعرضهم</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ADS -->
                <div class="page" id="page-ads">
                    <div class="page-header"
                        style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h1>الإعلانات</h1>
                            <p>إدارة إعلانات المتاجر</p>
                        </div>
                        <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> إعلان جديد</button>
                    </div>
                    <div class="card">
                        <table>
                            <thead>
                                <tr>
                                    <th>العنوان</th>
                                    <th>النوع</th>
                                    <th>المتجر</th>
                                    <th>المدة (ث)</th>
                                    <th>الترتيب</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ads ?? [] as $ad)
                                    <tr>
                                        <td style="color:var(--text);font-weight:500">
                                            {{ data_get($ad, 'title', '-') }}
                                        </td>
                                        <td><span
                                                class="tag {{ data_get($ad, 'type_class', '') }}">{{ data_get($ad, 'type_label', '-') }}</span>
                                        </td>
                                        <td>{{ data_get($ad, 'shop_name', '-') }}</td>
                                        <td>{{ data_get($ad, 'duration', '-') }}</td>
                                        <td>{{ data_get($ad, 'order', '-') }}</td>
                                        <td><span
                                                class="tag {{ data_get($ad, 'status_class', 'tag-g') }}">{{ data_get($ad, 'status_label', '-') }}</span>
                                        </td>
                                        <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                                    class="ti ti-edit" aria-hidden="true"></i></button><button
                                                class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                                    aria-hidden="true"></i></button></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" style="text-align:center;color:var(--text3)">لا توجد
                                            إعلانات لعرضها</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SCREENS -->
                <div class="page" id="page-screens">
                    <div class="page-header"
                        style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h1>الشاشات الرئيسية</h1>
                            <p>محتوى شاشات العرض</p>
                        </div>
                        <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> شاشة جديدة</button>
                    </div>
                    <div class="card">
                        <table>
                            <thead>
                                <tr>
                                    <th>العنوان</th>
                                    <th>النوع</th>
                                    <th>المدة (ث)</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($screens ?? [] as $screen)
                                    <tr>
                                        <td style="color:var(--text);font-weight:500">
                                            {{ data_get($screen, 'title', '-') }}</td>
                                        <td><span
                                                class="tag {{ data_get($screen, 'type_class', '') }}">{{ data_get($screen, 'type_label', '-') }}</span>
                                        </td>
                                        <td>{{ data_get($screen, 'duration', '-') }}</td>
                                        <td><span
                                                class="tag {{ data_get($screen, 'status_class', 'tag-g') }}">{{ data_get($screen, 'status_label', '-') }}</span>
                                        </td>
                                        <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                                    class="ti ti-edit" aria-hidden="true"></i></button><button
                                                class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                                    aria-hidden="true"></i></button></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align:center;color:var(--text3)">لا توجد شاشات
                                            لعرضها</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- USERS -->
                <div class="page" id="page-users">
                    <div class="page-header"
                        style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h1>إدارة المستخدمين</h1>
                            <p>{{ $usersCount ?? 0 }} مستخدم مسجل</p>
                        </div>
                        <button class="btn-red"><i class="ti ti-plus" aria-hidden="true"></i> مستخدم جديد</button>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Super Admin</div>
                            <div class="stat-val">{{ $superAdminCount ?? 0 }}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">مشرف شركة</div>
                            <div class="stat-val">{{ $companyAdminCount ?? 0 }}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">أصحاب متاجر</div>
                            <div class="stat-val">{{ $shopOwnerCount ?? 0 }}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">عملاء</div>
                            <div class="stat-val">{{ $customerCount ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-hd">
                            <h3>قائمة المستخدمين</h3>
                            <div style="display:flex;gap:8px">
                                <select
                                    style="background:var(--bg3);border:1px solid var(--border2);color:var(--text2);padding:5px 8px;border-radius:6px;font-size:12px">
                                    <option>كل الأدوار</option>
                                </select>
                                <div class="input-wrap"><i class="ti ti-search search-icon"
                                        aria-hidden="true"></i><input class="search-inp" placeholder="بحث..."></div>
                            </div>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>الإيميل</th>
                                    <th>الهاتف</th>
                                    <th>الدور</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users ?? [] as $user)
                                    <tr>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <div
                                                    style="width:28px;height:28px;border-radius:50%;background:var(--bg4);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700">
                                                    {{ strtoupper(mb_substr(data_get($user, 'name', ''), 0, 1)) }}
                                                </div><span
                                                    style="color:var(--text)">{{ data_get($user, 'name', '-') }}</span>
                                            </div>
                                        </td>
                                        <td>{{ data_get($user, 'email', '-') }}</td>
                                        <td dir="ltr">{{ data_get($user, 'phone', '-') }}</td>
                                        <td><span
                                                class="tag {{ data_get($user, 'role_class', '') }}">{{ data_get($user, 'role_label', '-') }}</span>
                                        </td>
                                        <td><span
                                                class="tag {{ data_get($user, 'status_class', 'tag-g') }}">{{ data_get($user, 'status_label', '-') }}</span>
                                        </td>
                                        <td><button class="topbar-btn" style="margin-left:4px" aria-label="تعديل"><i
                                                    class="ti ti-edit" aria-hidden="true"></i></button><button
                                                class="topbar-btn" aria-label="حذف"><i class="ti ti-trash"
                                                    aria-hidden="true"></i></button></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align:center;color:var(--text3)">لا توجد
                                            مستخدمين لعرضهم</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SETTINGS -->
                <div class="page" id="page-settings">
                    <div class="page-header">
                        <h1>الإعدادات</h1>
                        <p>إعدادات النظام العامة</p>
                    </div>
                    <div class="grid2">
                        <div class="card">
                            <div class="card-hd">
                                <h3><i class="ti ti-user" aria-hidden="true"
                                        style="margin-left:6px;color:var(--red-light)"></i>الملف الشخصي</h3>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:10px">
                                <div>
                                    <div style="font-size:11px;color:var(--text3);margin-bottom:4px">الاسم</div><input
                                        style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                        value="{{ $profileName ?? '' }}" />
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--text3);margin-bottom:4px">البريد الإلكتروني
                                    </div><input
                                        style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                        value="{{ $profileEmail ?? '' }}" />
                                </div>
                                <button class="btn-red" style="width:fit-content">حفظ التغييرات</button>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-hd">
                                <h3><i class="ti ti-lock" aria-hidden="true"
                                        style="margin-left:6px;color:var(--red-light)"></i>تغيير كلمة المرور</h3>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:10px">
                                <div>
                                    <div style="font-size:11px;color:var(--text3);margin-bottom:4px">كلمة المرور
                                        الحالية</div><input type="password"
                                        style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                        placeholder="••••••••" />
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--text3);margin-bottom:4px">كلمة المرور
                                        الجديدة</div><input type="password"
                                        style="width:100%;background:var(--bg3);border:1px solid var(--border2);color:var(--text);padding:8px 10px;border-radius:6px;font-size:13px;outline:none"
                                        placeholder="••••••••" />
                                </div>
                                <button class="btn-red" style="width:fit-content">تحديث</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @viewScript('admin.dashboard_main')
</body>

</html>
