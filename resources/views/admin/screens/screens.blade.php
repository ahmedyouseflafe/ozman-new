<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الشاشات الرئيسية - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.screens.screens')
</head>

<body>
    @php
        $screenItems = collect(
            $screens ?? [
                [
                    'title' => 'الشاشة الرئيسية 1',
                    'type' => 'image',
                    'type_label' => 'صورة',
                    'media' => 'main_screen_1.jpg',
                    'duration' => 10,
                    'is_active' => true,
                ],
                [
                    'title' => 'فيديو ترحيبي',
                    'type' => 'video',
                    'type_label' => 'فيديو',
                    'media' => 'welcome.mp4',
                    'duration' => 45,
                    'is_active' => true,
                ],
                [
                    'title' => 'شاشة العروض',
                    'type' => 'image',
                    'type_label' => 'صورة',
                    'media' => 'offers.jpg',
                    'duration' => 15,
                    'is_active' => true,
                ],
                [
                    'title' => 'شاشة يوتيوب',
                    'type' => 'youtube',
                    'type_label' => 'يوتيوب',
                    'media' => 'youtube.com/watch?v=xxx',
                    'duration' => 30,
                    'is_active' => false,
                ],
            ],
        );

        $screensTotal = $screensCount ?? $screenItems->count();
        $activeScreens =
            $activeScreensCount ??
            $screenItems
                ->filter(
                    fn($screen) => (bool) data_get($screen, 'is_active', data_get($screen, 'status_class') === 'tag-g'),
                )
                ->count();
        $inactiveScreens = $inactiveScreensCount ?? max($screensTotal - $activeScreens, 0);
        $averageDuration =
            $averageScreenDuration ??
            round($screenItems->avg(fn($screen) => (float) data_get($screen, 'duration', 0)) ?: 0);
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'الشاشات الرئيسية'])

            <div class="content">
                <section class="hero-strip" aria-label="شريط الشاشات">
                    <div class="ticker">
                        <span>تحكم بمحتوى واجهة العرض من مكان واحد</span>
                        <span>صور وفيديو ويوتيوب بترتيب واضح وحالة مباشرة</span>
                        <span>شاشات Ozman جاهزة للعرض داخل المتجر</span>
                        <span>تحكم بمحتوى واجهة العرض من مكان واحد</span>
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">الإعلانات</div>
                        <h1>الشاشات الرئيسية</h1>
                        <p>إدارة محتوى شاشات العرض ونوع الوسائط ومدة الظهور.</p>
                    </div>
                    <a href="{{ route('screens.create', ['shop_id' => $selectedShopId ?? '']) }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        شاشة جديدة
                    </a>
                </header>

                {{-- Only owners of several shops need to pick one. --}}
                @if($shops->count() > 1)
                <form method="GET" action="{{ route('screens') }}" style="margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <label for="screenShopFilter" style="font-weight:800">شاشات المحل</label>
                    <select name="shop_id" id="screenShopFilter" class="filter-select" onchange="this.form.submit()">
                        <option value="" @selected(! $selectedShopId)>كل الشاشات المتاحة لحسابي</option>
                        @foreach($shops as $screenShop)
                            <option value="{{ $screenShop->id }}" @selected($selectedShopId === $screenShop->id)>{{ $screenShop->name }}</option>
                        @endforeach
                    </select>
                </form>
                @endif

                @if (session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <section class="stats-grid" aria-label="إحصائيات الشاشات">
                    <article class="stat-card" style="--card-color: var(--primary)">
                        <div class="stat-label">إجمالي الشاشات</div>
                        <div class="stat-val">{{ $screensTotal }}</div>
                        <i class="ti ti-device-tv stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--green)">
                        <div class="stat-label">نشطة</div>
                        <div class="stat-val">{{ $activeScreens }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--danger)">
                        <div class="stat-label">معطلة</div>
                        <div class="stat-val">{{ $inactiveScreens }}</div>
                        <i class="ti ti-circle-x stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--accent)">
                        <div class="stat-label">متوسط المدة</div>
                        <div class="stat-val">{{ $averageDuration ? $averageDuration.' ث' : '—' }}</div>
                        <i class="ti ti-clock stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">
                            <i class="ti ti-list-details" aria-hidden="true"></i>
                            قائمة الشاشات
                        </h2>

                        <div class="filter-row">
                            <select class="filter-select" id="typeFilter" aria-label="فلترة النوع">
                                <option value="">كل الأنواع</option>
                                <option value="image">صور</option>
                                <option value="video">فيديو</option>
                                <option value="youtube">يوتيوب</option>
                            </select>

                            <div class="input-wrap">
                                <i class="ti ti-search" aria-hidden="true"></i>
                                <input class="search-inp" id="scrSearch" type="search"
                                    placeholder="بحث بالعنوان أو الملف...">
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table id="scrTable">
                            <thead>
                                <tr>
                                    <th>الشاشة</th>
                                    <th>النوع</th>
                                    <th>مكان العرض</th>
                                    <th>الملف / الرابط</th>
                                    <th>المدة</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($screenItems as $screen)
                                    @php
                                        $type = data_get($screen, 'type', 'image');
                                        $typeLabels = ['image' => 'صورة', 'video' => 'فيديو', 'youtube' => 'يوتيوب'];
                                        $typeIcons = [
                                            'image' => 'ti-photo',
                                            'video' => 'ti-video',
                                            'youtube' => 'ti-brand-youtube',
                                        ];
                                        $typeClasses = ['image' => 'tag-c', 'video' => 'tag-p', 'youtube' => 'tag-r'];
                                        $placement = data_get($screen, 'placement', 'top') ?: 'top';
                                        $placementLabels = ['top' => 'الشاشة العلوية', 'bottom' => 'الشاشة السفلية'];
                                        $media = data_get($screen, 'media', data_get($screen, 'file', '-'));
                                        $isActive = (bool) data_get(
                                            $screen,
                                            'is_active',
                                            data_get($screen, 'status_class') === 'tag-g',
                                        );
                                    @endphp
                                    <tr data-type="{{ $type }}">
                                        <td>
                                            <div class="screen-cell">
                                                <span class="screen-thumb">
                                                    @if ($type === 'image' && $media && !str_contains($media, 'http') && str_contains($media, '.'))
                                                        <img src="{{ asset($media) }}" alt="">
                                                    @else
                                                        <i class="ti {{ $typeIcons[$type] ?? 'ti-device-tv' }}"
                                                            aria-hidden="true"></i>
                                                    @endif
                                                </span>
                                                <span>
                                                    {{ data_get($screen, 'title', '-') }}
                                                    <span class="screen-sub">{{ $screen->shop?->name ?? ($screen->needs_shop_assignment ? 'بانتظار تحديد المحل' : 'واجهة أوزمان العامة') }}</span>
                                                    <span class="screen-sub">{{ $media }}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="tag {{ $typeClasses[$type] ?? 'tag-c' }}">
                                                {{ data_get($screen, 'type_label', $typeLabels[$type] ?? $type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="tag {{ $placement === 'bottom' ? 'tag-p' : 'tag-c' }}">
                                                {{ data_get($screen, 'placement_label', $placementLabels[$placement] ?? $placement) }}
                                            </span>
                                        </td>
                                        <td dir="ltr">{{ $media }}</td>
                                        <td>{{ data_get($screen, 'duration', '-') }} ث</td>
                                        <td><span
                                                class="tag {{ $isActive ? 'tag-g' : 'tag-r' }}">{{ $isActive ? 'نشط' : 'معطل' }}</span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="{{ route('screens.show', $screen) }}" class="icon-btn"
                                                    aria-label="معاينة">
                                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                                </a>
                                                <a href="{{ route('screens.edit', $screen) }}" class="icon-btn"
                                                    aria-label="تعديل">
                                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                                </a>
                                                <form action="{{ route('screens.destroy', $screen) }}" method="POST"
                                                    onsubmit="return confirm('هل تريد حذف هذه الشاشة؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="icon-btn" aria-label="حذف">
                                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="icon-btn" aria-label="معاينة">
                                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="icon-btn" aria-label="تعديل">
                                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="icon-btn" aria-label="حذف">
                                                    <i class="ti ti-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="ti ti-device-tv-off" aria-hidden="true"></i>
                                                لا توجد شاشات لعرضها حاليا
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
        const scrSearch = document.getElementById('scrSearch');
        const typeFilter = document.getElementById('typeFilter');
        const screenRows = document.querySelectorAll('#scrTable tbody tr');

        function filterScreens() {
            const query = (scrSearch?.value || '').trim().toLowerCase();
            const type = typeFilter?.value || '';

            screenRows.forEach((row) => {
                const matchesQuery = row.textContent.toLowerCase().includes(query);
                const matchesType = !type || row.dataset.type === type;
                row.style.display = matchesQuery && matchesType ? '' : 'none';
            });
        }

        scrSearch?.addEventListener('input', filterScreens);
        typeFilter?.addEventListener('change', filterScreens);
    </script>
</body>

</html>
