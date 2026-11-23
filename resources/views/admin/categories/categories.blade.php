@php
    $categoriesList = collect($categories ?? []);
    $categoriesCount = $categoriesCount ?? $categoriesList->count();
    $productsInCategoriesCount = $productsInCategoriesCount ?? $categoriesList->sum(fn($category) => (int) data_get($category, 'products_count', 0));
    $activeCategoriesCount = $activeCategoriesCount ?? $categoriesList->filter(fn($category) => data_get($category, 'status_class') === 'tag-g' || data_get($category, 'status_label') === 'نشط' || data_get($category, 'is_active') === true)->count();
    $emptyCategoriesCount = $emptyCategoriesCount ?? $categoriesList->filter(fn($category) => (int) data_get($category, 'products_count', 0) === 0)->count();
    $currentShop = $currentShop ?? null;
    $isRestaurantCategories = $currentShop?->catalog_type === 'restaurant';
    $catalogTerms = $currentShop?->catalogTerms() ?? config('catalog_terminology.general');
    $categoryName = $catalogTerms['category_singular'];
    $categoriesPageTitle = $catalogTerms['category_plural'];
    $itemLabel = $catalogTerms['item_singular'];
    $itemsLabel = $catalogTerms['item_plural'];
    $addCategoryLabel = $catalogTerms['add_category'];
    $placeLabel = $catalogTerms['place_singular'];
@endphp

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ $categoriesPageTitle }} — Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.categories.categories')
</head>

<body>
    @php($canCreateCategories = auth()->user()?->canAccessRouteName('categories.create'))
    <div class="shell">
        @include('admin.includes.sidebar')

        <div class="main">
            @include('admin.includes.header', ['title' => $categoriesPageTitle])

            <div class="content">
                <div class="hero-panel">
                    <div class="display-screen">
                        <div class="story-slider">
                            <span class="welcome-msg">نظّم {{ $categoriesPageTitle }} في {{ $placeLabel }} بشكل واضح وسريع</span>
                            <span class="welcome-msg">{{ $categoriesCount }} من {{ $categoriesPageTitle }} تحتوي على {{ $productsInCategoriesCount }} من {{ $itemsLabel }}</span>
                            <span class="welcome-msg">قسّم {{ $itemsLabel }} ليسهل على الزبون تصفّحها</span>
                        </div>
                    </div>
                    <div class="hero-orb">
                        <i class="ti ti-category" aria-hidden="true"></i>
                        <span>{{ $categoriesPageTitle }}</span>
                    </div>
                </div>

                <div class="page-header-row">
                    <div>
                        <h1>{{ $categoriesPageTitle }}</h1>
                        <p>رتّب {{ $itemsLabel }} في أقسام عشان يلاقي الزبون اللي بده بسرعة.</p>
                    </div>
                    @if($canCreateCategories)
                    <a href="{{ route('categories.create', $currentShop ? ['shop_id' => $currentShop->id] : []) }}" class="btn-primary">
                        <i class="ti ti-plus" aria-hidden="true"></i>
                        {{ $addCategoryLabel }}
                    </a>
                    @endif
                </div>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <div class="stats-grid">
                    <div class="stat-card" style="--accent: var(--primary-color)">
                        <div class="stat-label">كل الأقسام</div>
                        <div class="stat-val">{{ $categoriesCount }}</div>
                        <i class="ti ti-category stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--green)">
                        <div class="stat-label">ظاهرة للزبائن</div>
                        <div class="stat-val">{{ $activeCategoriesCount }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--accent-color)">
                        <div class="stat-label">{{ $isRestaurantCategories ? 'الوجبات' : $itemsLabel }}</div>
                        <div class="stat-val">{{ $productsInCategoriesCount }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </div>
                    <div class="stat-card" style="--accent: var(--yellow)">
                        <div class="stat-label">أقسام فاضية</div>
                        <div class="stat-val">{{ $emptyCategoriesCount }}</div>
                        <i class="ti ti-folder-off stat-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="toolbar-card">
                    <h3><i class="ti ti-layout-grid" aria-hidden="true"></i> قائمة {{ $categoriesPageTitle }}</h3>
                    <div class="input-wrap">
                        <i class="ti ti-search search-icon" aria-hidden="true"></i>
                        <input class="search-inp" id="categorySearch" placeholder="بحث في {{ $categoriesPageTitle }}...">
                    </div>
                </div>

                <div class="categories-grid" id="categoriesGrid">
                    <?php if ($categoriesList->isNotEmpty()): ?>
                    <?php foreach ($categoriesList as $index => $category): ?>
                        <?php
                            $accents = ['#00e5ff', '#00ffd5', '#7000ff', '#25d366', '#f1c40f', '#ff3b30'];
                            $icons = ['ti-category', 'ti-bottle', 'ti-droplet', 'ti-spray', 'ti-heart', 'ti-tag'];
                            $accent = $accents[$index % count($accents)];
                            $icon = data_get($category, 'icon', $icons[$index % count($icons)]);
                        ?>
                        <div class="category-card" style="--card-accent: {{ $accent }}">
                            <div class="category-icon-wrap">
                                <i class="ti {{ $icon }}" aria-hidden="true"></i>
                            </div>
                            <div class="category-name">{{ data_get($category, 'name', '-') }}</div>
                            <div class="category-count">{{ data_get($category, 'shop.name', '-') }}</div>
                            <div class="category-count">{{ data_get($category, 'products_count', 0) }} {{ $itemLabel }}</div>
                            <div class="category-actions">
                                <a href="{{ route('categories.show', $category) }}" class="action-btn" aria-label="عرض">
                                    <i class="ti ti-eye" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('categories.edit', $category) }}" class="action-btn" aria-label="تعديل">
                                    <i class="ti ti-edit" aria-hidden="true"></i>
                                </a>
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('هل تريد حذف: {{ $category->name }}؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn" aria-label="حذف">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="ti ti-category-off" aria-hidden="true"></i>
                            لا توجد {{ $categoriesPageTitle }} لعرضها
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('categorySearch').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#categoriesGrid .category-card').forEach(card => {
                card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    </script>
</body>

</html>
