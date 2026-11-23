<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الموزعون - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.distributors.distributors')
</head>

<body>
    @php
        $distributorItems = collect($distributors ?? [
            [
                'name' => 'شركة التوزيع المركزية',
                'phone' => '059-500-6000',
                'whatsapp' => '059-500-6000',
                'city' => 'نابلس',
                'address' => 'المنطقة الصناعية',
                'shop_name' => 'Ozman الرئيسي',
                'status_class' => 'tag-g',
                'status_label' => 'نشط',
            ],
            [
                'name' => 'موزع الجنوب',
                'phone' => '059-600-7000',
                'whatsapp' => '059-600-7000',
                'city' => 'الخليل',
                'address' => 'حي الراشد',
                'shop_name' => 'متجر الجمال الفاخر',
                'status_class' => 'tag-g',
                'status_label' => 'نشط',
            ],
            [
                'name' => 'موزع الشمال',
                'phone' => '059-700-8000',
                'whatsapp' => '059-700-8000',
                'city' => 'جنين',
                'address' => 'شارع القدس',
                'shop_name' => 'صيدلية الشفاء',
                'status_class' => 'tag-r',
                'status_label' => 'غير نشط',
            ],
        ]);

        $totalDistributors = $distributorsCount ?? $distributorItems->count();
        $activeDistributors = $activeDistributorsCount ?? $distributorItems->filter(fn($item) => data_get($item, 'status_class') === 'tag-g')->count();
        $inactiveDistributors = $inactiveDistributorsCount ?? $distributorItems->filter(fn($item) => data_get($item, 'status_class') === 'tag-r')->count();
        $linkedShops = $linkedShopsCount ?? $distributorItems->filter(fn($item) => filled(data_get($item, 'shop_name')))->count();
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'الموزعون'])

            <div class="content">
                <section class="hero-strip" aria-label="شريط تنبيهات الموزعين">
                    <div class="ticker">
                        <span>إدارة شبكة التوزيع من مكان واحد</span>
                        <span>متابعة المدن والمتاجر المرتبطة والحالة مباشرة</span>
                        <span>Ozman موزعون جاهزون لخدمة المتاجر</span>
                        <span>إدارة شبكة التوزيع من مكان واحد</span>
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">لوحة التحكم</div>
                        <h1>الموزعون</h1>
                        <p>إدارة موزعي المتاجر وأرقام التواصل والربط مع الفروع.</p>
                    </div>
                    <a href="{{ route('distributors.create') }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        موزع جديد
                    </a>
                </header>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <section class="stats-grid" aria-label="إحصائيات الموزعين">
                    <article class="stat-card" style="--card-color: var(--primary)">
                        <div class="stat-label">إجمالي الموزعين</div>
                        <div class="stat-val">{{ $totalDistributors }}</div>
                        <i class="ti ti-truck-delivery stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--green)">
                        <div class="stat-label">نشط</div>
                        <div class="stat-val">{{ $activeDistributors }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--danger)">
                        <div class="stat-label">غير نشط</div>
                        <div class="stat-val">{{ $inactiveDistributors }}</div>
                        <i class="ti ti-circle-x stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--accent)">
                        <div class="stat-label">المتاجر المرتبطة</div>
                        <div class="stat-val">{{ $linkedShops }}</div>
                        <i class="ti ti-building-store stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">
                            <i class="ti ti-list-details" aria-hidden="true"></i>
                            قائمة الموزعين
                        </h2>

                        <div class="input-wrap">
                            <i class="ti ti-search" aria-hidden="true"></i>
                            <input class="search-inp" id="distSearch" type="search" placeholder="بحث بالاسم، المدينة، المتجر...">
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="distTable">
                            <thead>
                                <tr>
                                    <th>الموزع</th>
                                    <th>الهاتف</th>
                                    <th>واتساب</th>
                                    <th>المدينة</th>
                                    <th>العنوان</th>
                                    <th>المتجر المرتبط</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($distributorItems as $distributor)
                                    <tr>
                                        <td>
                                            <div class="dist-name">
                                                <span class="dist-avatar">{{ mb_substr(data_get($distributor, 'name', '-'), 0, 1) }}</span>
                                                <span>{{ data_get($distributor, 'name', '-') }}</span>
                                            </div>
                                        </td>
                                        <td dir="ltr">{{ data_get($distributor, 'phone', '-') }}</td>
                                        <td dir="ltr">{{ data_get($distributor, 'whatsapp', data_get($distributor, 'phone', '-')) }}</td>
                                        <td>{{ data_get($distributor, 'shop.city', data_get($distributor, 'city', '-')) }}</td>
                                        <td>{{ data_get($distributor, 'address', '-') }}</td>
                                        <td>{{ data_get($distributor, 'shop.name', data_get($distributor, 'shop_name', '-')) }}</td>
                                        <td>
                                            <span class="tag {{ data_get($distributor, 'status_class', 'tag-g') }}">
                                                {{ data_get($distributor, 'status_label', '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                @if(data_get($distributor, 'id'))
                                                    <a href="{{ route('distributors.show', $distributor) }}" class="icon-btn" aria-label="عرض">
                                                        <i class="ti ti-eye" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="{{ route('distributors.edit', $distributor) }}" class="icon-btn" aria-label="تعديل">
                                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="{{ route('distributors.permissions.edit', $distributor) }}" class="icon-btn"
                                                        aria-label="صلاحيات"
                                                        title="{{ data_get($distributor, 'user_id') ? 'صلاحيات الموزع' : 'أنشئ حساب دخول للموزع أولاً ثم حدد صلاحياته' }}">
                                                        <i class="ti ti-lock{{ data_get($distributor, 'user_id') ? '' : '-open' }}" aria-hidden="true"></i>
                                                    </a>
                                                    <form action="{{ route('distributors.destroy', $distributor) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذا الموزع؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="icon-btn" aria-label="حذف">
                                                            <i class="ti ti-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="icon-btn" aria-label="تعديل">
                                                        <i class="ti ti-edit" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="icon-btn" aria-label="حذف">
                                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="ti ti-truck-off" aria-hidden="true"></i>
                                                لا يوجد موزعون لعرضهم حاليا
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

    <script>
        const distSearch = document.getElementById('distSearch');
        const distRows = document.querySelectorAll('#distTable tbody tr');

        if (distSearch) {
            distSearch.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();

                distRows.forEach((row) => {
                    row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
                });
            });
        }
    </script>
</body>

</html>
