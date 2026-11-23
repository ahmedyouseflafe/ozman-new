<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>قائمة المتاجر - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.shop.shops')
</head>

<body>
    @php
        $currentUser = auth()->user();
        $canCreateShop = $currentUser?->isSuperAdmin()
            || $currentUser?->canAccessRouteName('shops.create');
        $canOpenOzmanShop = $currentUser?->isSuperAdmin();

        $shopItems = collect(
            $shops ?? [
                [
                    'id' => 1,
                    'name' => 'Ozman الرئيسي',
                    'slug' => 'ozman-main',
                    'city' => 'غزة',
                    'phone' => '059-000-0001',
                    'products_count' => 128,
                    'open_time' => '09:00',
                    'close_time' => '22:00',
                    'is_active' => true,
                    'status_label' => 'نشط',
                    'status_class' => 'tag-g',
                ],
                [
                    'id' => 2,
                    'name' => 'Healthy Shop',
                    'slug' => 'healthy-shop',
                    'city' => 'رام الله',
                    'phone' => '059-111-2222',
                    'products_count' => 86,
                    'open_time' => '10:00',
                    'close_time' => '21:00',
                    'is_active' => true,
                    'status_label' => 'نشط',
                    'status_class' => 'tag-g',
                ],
                [
                    'id' => 3,
                    'name' => 'متجر الجمال الفاخر',
                    'slug' => 'beauty-lux',
                    'city' => 'الخليل',
                    'phone' => '059-333-4444',
                    'products_count' => 42,
                    'open_time' => '11:00',
                    'close_time' => '20:00',
                    'is_active' => false,
                    'status_label' => 'غير نشط',
                    'status_class' => 'tag-r',
                ],
            ],
        );

        $shopsTotal = $shopsCount ?? $shopItems->count();
        $activeShops =
            $activeShopsCount ??
            $shopItems
                ->filter(fn($shop) => (bool) data_get($shop, 'is_active', data_get($shop, 'status_class') === 'tag-g'))
                ->count();
        $inactiveShops = $inactiveShopsCount ?? max($shopsTotal - $activeShops, 0);
        $productsTotal = $shopItems->sum(fn($shop) => (int) data_get($shop, 'products_count', 0));
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'قائمة المتاجر'])

            <div class="content">
                <section class="hero-strip" aria-label="شريط المتاجر">
                    <div class="ticker">
                        <span>إدارة متاجر Ozman من مكان واحد</span>
                        <span>تحكم بالحالة، المنتجات، أوقات العمل، وبيانات التواصل</span>
                        <span>كل متجر جاهز للتعديل والربط مع الكنترولر لاحقا</span>
                        <span>إدارة متاجر Ozman من مكان واحد</span>
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">المتجر</div>
                        <h1>قائمة المتاجر</h1>
                        <p>{{ $shopsTotal }} متجر داخل النظام مع متابعة الحالة وعدد المنتجات.</p>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end">
                    @if($canOpenOzmanShop)
                    <a href="{{ route('shops.ozman') }}" class="btn-primary">
                        <i class="ti ti-building-store" aria-hidden="true"></i>
                        متجر Ozman
                    </a>
                    @endif
                    @if($canCreateShop)
                    <a href="{{ route('shops.create') }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        متجر جديد
                    </a>
                    @endif
                    </div>
                </header>

                @if (session('status'))
                    <div class="panel"
                        style="margin-bottom:18px;border-color:rgba(37,211,102,.35);background:rgba(37,211,102,.08);color:#fff">
                        {{ session('status') }}
                    </div>
                @endif

                <section class="stats-grid" aria-label="إحصائيات المتاجر">
                    <article class="stat-card" style="--card-color: var(--primary)">
                        <div class="stat-label">إجمالي المتاجر</div>
                        <div class="stat-val">{{ $shopsTotal }}</div>
                        <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--green)">
                        <div class="stat-label">متاجر نشطة</div>
                        <div class="stat-val">{{ $activeShops }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--danger)">
                        <div class="stat-label">غير نشطة</div>
                        <div class="stat-val">{{ $inactiveShops }}</div>
                        <i class="ti ti-circle-x stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--accent)">
                        <div class="stat-label">إجمالي المنتجات</div>
                        <div class="stat-val">{{ $productsTotal }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">
                            <i class="ti ti-list-details" aria-hidden="true"></i>
                            المتاجر
                        </h2>

                        <div class="filter-row">
                            <select class="filter-select" id="statusFilter" aria-label="فلترة الحالة">
                                <option value="">كل الحالات</option>
                                <option value="active">نشط</option>
                                <option value="inactive">غير نشط</option>
                            </select>

                            <div class="input-wrap">
                                <i class="ti ti-search" aria-hidden="true"></i>
                                <input class="search-inp" id="shopSearch" type="search"
                                    placeholder="بحث بالاسم، المدينة، الهاتف...">
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="shopsTable">
                            <thead>
                                <tr>
                                    <th>المتجر</th>
                                    <th>المدينة</th>
                                    <th>الهاتف</th>
                                    <th>المنتجات</th>
                                    <th>أوقات العمل</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shopItems as $shop)
                                    @php
                                        $shopId = data_get($shop, 'id', 1);
                                        $shopName = data_get($shop, 'name', '-');
                                        $logo = data_get($shop, 'logo');
                                        $isActive = (bool) data_get(
                                            $shop,
                                            'is_active',
                                            data_get($shop, 'status_class') === 'tag-g',
                                        );
                                        $statusClass = data_get($shop, 'status_class', $isActive ? 'tag-g' : 'tag-r');
                                        $statusLabel = data_get($shop, 'status_label', $isActive ? 'نشط' : 'غير نشط');
                                        $openTime = data_get($shop, 'open_time');
                                        $closeTime = data_get($shop, 'close_time');
                                    @endphp
                                    <tr data-status="{{ $isActive ? 'active' : 'inactive' }}">
                                        <td>
                                            <div class="shop-cell">
                                                <span class="shop-logo">
                                                    @if ($logo)
                                                        <img src="{{ asset($logo) }}" alt="">
                                                    @else
                                                        {{ mb_substr($shopName, 0, 1) }}
                                                    @endif
                                                </span>
                                                <span>
                                                    {{ $shopName }}
                                                    <span
                                                        class="shop-sub">{{ data_get($shop, 'slug', 'shop-' . $shopId) }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ data_get($shop, 'city', '-') }}</td>
                                        <td dir="ltr">{{ data_get($shop, 'phone', '-') }}</td>
                                        <td><span class="tag tag-c">{{ data_get($shop, 'products_count', 0) }}</span>
                                        </td>
                                        <td>{{ $openTime && $closeTime ? $openTime . ' - ' . $closeTime : '-' }}</td>
                                        <td><span class="tag {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                        <td>
    <div class="actions">
        @if(auth()->user()?->isSuperAdmin())
            <a href="{{ route('shops.permissions.edit', $shopId) }}" class="icon-btn"
                title="صلاحيات لوحة المتجر" aria-label="صلاحيات لوحة المتجر">
                <i class="ti ti-shield-lock" aria-hidden="true"></i>
            </a>

            <button type="button" class="icon-btn" data-assign-distributor
                data-shop-name="{{ $shopName }}"
                data-action="{{ route('shops.distributor.assign', $shopId) }}"
                data-distributor-id="{{ data_get($shop, 'distributor_id') }}"
                title="تعيين موزع للمتجر" aria-label="تعيين موزع للمتجر">
                <i class="ti ti-truck-delivery" aria-hidden="true"></i>
            </button>
        @endif

        @if(auth()->user()?->isSuperAdmin() && data_get($shop, 'user_id'))
            <form action="{{ route('shops.enter-dashboard', $shopId) }}" method="POST">
                @csrf
                <button type="submit" class="icon-btn" aria-label="الدخول إلى لوحة تحكم المتجر"
                    title="الدخول إلى لوحة تحكم المتجر">
                    <i class="ti ti-login-2" aria-hidden="true"></i>
                </button>
            </form>
        @endif

        <!-- عرض -->
        <a href="{{ route('shops.show', $shopId) }}" class="icon-btn"
            aria-label="عرض">
            <i class="ti ti-eye" aria-hidden="true"></i>
        </a>

        <!-- تعديل -->
        <a href="{{ route('shops.edit', $shopId) }}" class="icon-btn"
            aria-label="تعديل">
            <i class="ti ti-edit" aria-hidden="true"></i>
        </a>

        <!-- حذف -->
        <form action="{{ route('shops.destroy', $shopId) }}" method="POST"
            onsubmit="return confirm('هل تريد حذف هذا المتجر؟')">
            @csrf
            @method('DELETE')

            <button type="submit" class="icon-btn" aria-label="حذف">
                <i class="ti ti-trash" aria-hidden="true"></i>
            </button>
        </form>

    </div>
</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="ti ti-building-store-off" aria-hidden="true"></i>
                                                لا توجد متاجر لعرضها حاليا
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    @if(auth()->user()?->isSuperAdmin())
        <div class="assign-modal" id="assignDistributorModal" role="dialog" aria-modal="true" aria-labelledby="assignDistributorTitle">
            <form class="assign-card" id="assignDistributorForm" method="POST">
                @csrf
                @method('PATCH')
                <h2 id="assignDistributorTitle">تعيين موزع للمتجر</h2>
                <p>المتجر: <strong id="assignDistributorShopName"></strong></p>
                <label for="assignDistributorSelect" style="display:block;margin-bottom:8px;font-weight:900">الموزع المسؤول عن الطلبات</label>
                <select id="assignDistributorSelect" name="distributor_id">
                    <option value="">بدون موزع / إلغاء الربط</option>
                    @foreach($assignableDistributors as $distributor)
                        <option value="{{ $distributor->id }}">{{ $distributor->name }}{{ $distributor->phone ? ' — ' . $distributor->phone : '' }}</option>
                    @endforeach
                </select>
                <small style="display:block;margin-top:9px;color:rgba(255,255,255,.5)">بعد الحفظ ستُنسب طلبات المتجر إلى الموزع المختار. تغيير الموزع يفك أي مسوّق تابع لموزع مختلف.</small>
                <div class="assign-card-actions">
                    <button type="button" class="assign-cancel" data-close-assign>إلغاء</button>
                    <button type="submit" class="assign-save">حفظ التعيين</button>
                </div>
            </form>
        </div>
    @endif

    @viewScript('admin.shop.shops')
</body>

</html>
