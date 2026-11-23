<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الرئيسية — Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.dashboard')
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <div class="main">
            @include('admin.includes.header', ['title' => 'الرئيسية'])

            <div class="content">
                <div class="hero-panel">
                    <div class="display-screen">
                        <div class="story-slider">
                            <span class="welcome-msg">لوحة Ozman الرئيسية تعرض المتاجر والمنتجات والمستخدمين من مكان واحد</span>
                            <span class="welcome-msg">{{ $shopsCount ?? 0 }} متجر، {{ $activeProductsCount ?? 0 }} منتج نشط، {{ $usersCount ?? 0 }} مستخدم</span>
                            <span class="welcome-msg">تابع آخر المنتجات والمتاجر والتحديثات اليومية بسرعة</span>
                        </div>
                    </div>
                    <div class="hero-orb">
                        <i class="ti ti-layout-dashboard" aria-hidden="true"></i>
                        <span>الرئيسية</span>
                    </div>
                </div>

                <div class="page-header">
                    <h1>نظرة عامة</h1>
                    <p>مرحباً بك، آخر تحديث اليوم.</p>
                </div>
                @if($pendingPromoterCount)
                    <a href="{{ route('admin.promoter-requests') }}" class="promoter-requests-alert" style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 18px;padding:16px 20px;border:1px solid #e0b85a;border-radius:18px;background:rgba(224,184,90,.12);color:#ffe2a0;text-decoration:none;font-weight:800">
                        <span><i class="ti ti-user-check" aria-hidden="true"></i> {{ __('في :count طلب مروّج بانتظار موافقتك', ['count' => $pendingPromoterCount]) }}</span>
                        <span style="padding:6px 14px;border-radius:12px;background:#e0b85a;color:#2a1f05">{{ __('راجع الطلبات ←') }}</span>
                    </a>
                @endif

                <div class="stats-grid">
                    <div class="stat-card" style="--accent: var(--danger)">
                        <div class="stat-label">إجمالي المتاجر</div>
                        <div class="stat-val">{{ $shopsCount ?? 0 }}</div>
                        <div class="stat-sub">{{ $shopsDelta ?? '' }}</div>
                        <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--accent-color)">
                        <div class="stat-label">المنتجات النشطة</div>
                        <div class="stat-val">{{ $activeProductsCount ?? 0 }}</div>
                        <div class="stat-sub">{{ $activeProductsDelta ?? '' }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: #3498db">
                        <div class="stat-label">إجمالي المستخدمين</div>
                        <div class="stat-val">{{ $usersCount ?? 0 }}</div>
                        <div class="stat-sub">{{ $usersDelta ?? '' }}</div>
                        <i class="ti ti-users stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--green)">
                        <div class="stat-label">الوكلاء والموزعون</div>
                        <div class="stat-val">{{ $agentsAndDistributorsCount ?? 0 }}</div>
                        <div class="stat-sub stat-down">{{ $agentsStatusDelta ?? '' }}</div>
                        <i class="ti ti-truck stat-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="grid2">
                    <div class="card">
                        <div class="card-hd">
                            <h3>المبيعات الشهرية</h3>
                            <span class="badge">{{ $salesYear ?? date('Y') }}</span>
                        </div>
                        <div class="chart-bars">
                            @forelse($monthlySales ?? [] as $item)
                                <div class="bar-wrap">
                                    <div class="bar" style="height:{{ min(100, max(0, data_get($item, 'value', 0))) }}%"></div>
                                    <div class="bar-label">{{ data_get($item, 'label', '') }}</div>
                                </div>
                            @empty
                                <div class="empty-text">لا توجد بيانات مبيعات</div>
                            @endforelse
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-top:8px">
                            <span style="font-size:10px;color:var(--text-muted)">{{ $salesRangeStart ?? 'بداية الشهر' }}</span>
                            <span style="font-size:10px;color:var(--text-muted)">{{ $salesRangeEnd ?? 'نهاية الشهر' }}</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-hd">
                            <h3>آخر المنتجات المضافة</h3>
                            <a href="{{ route('products') }}" class="btn-primary">
                                <i class="ti ti-plus" aria-hidden="true"></i>
                                إضافة
                            </a>
                        </div>
                        <div class="recent-list">
                            @forelse($recentProducts ?? [] as $product)
                                <div class="recent-item">
                                    <div class="ri-icon"><i class="ti ti-package" aria-hidden="true"></i></div>
                                    <div class="ri-info">
                                        <div class="ri-title">{{ data_get($product, 'name', '-') }}</div>
                                        <div class="ri-sub">{{ data_get($product, 'category', '-') }} - {{ data_get($product, 'added_at_label', '') }}</div>
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
                        <h3>آخر المتاجر المسجلة</h3>
                        <span class="badge">{{ $recentShopsLabel ?? 'آخر تحديث' }}</span>
                    </div>
                    <div class="table-wrap">
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
                                        <td style="color:var(--text-main);font-weight:900">{{ data_get($shop, 'name', '-') }}</td>
                                        <td>{{ data_get($shop, 'city', '-') }}</td>
                                        <td>{{ data_get($shop, 'products_count', '-') }}</td>
                                        <td>
                                            <span class="tag {{ data_get($shop, 'status_class', 'tag-g') }}">
                                                {{ data_get($shop, 'status_label', '-') }}
                                            </span>
                                        </td>
                                        <td>{{ data_get($shop, 'registered_at', '-') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align:center;color:var(--text-muted)">لا توجد متاجر مسجلة حالياً</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

</html>
