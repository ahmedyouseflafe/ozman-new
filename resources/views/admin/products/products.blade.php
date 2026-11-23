<!DOCTYPE html>
<html lang="ar" dir="rtl">

@php
    $isRestaurantContext = ($currentShop ?? null)?->catalog_type === 'restaurant';
    $catalogTerms = ($currentShop ?? null)?->catalogTerms() ?? config('catalog_terminology.general');
    $itemLabel = $catalogTerms['item_singular'];
    $itemsLabel = $catalogTerms['item_plural'];
    $addItemLabel = $catalogTerms['add_item'];
    $placeLabel = $catalogTerms['place_singular'];
@endphp

<head>
    <title>إدارة {{ $itemsLabel }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.products.products')
</head>

<body>
    @php
        $productItems = collect($products ?? [
            [
                'name' => 'Cola الأصلية',
                'sku' => 'COLA-001',
                'category_name' => 'مشروبات',
                'shop_name' => 'Ozman الرئيسي',
                'price' => 15,
                'discount_price' => 12,
                'quantity' => 240,
                'rating' => 4.8,
                'is_featured' => true,
                'is_active' => true,
                'icon' => 'ti-bottle',
            ],
            [
                'name' => 'كريم العناية بالوجه',
                'sku' => 'CARE-112',
                'category_name' => 'العناية بالبشرة',
                'shop_name' => 'متجر الجمال الفاخر',
                'price' => 45,
                'discount_price' => null,
                'quantity' => 80,
                'rating' => 4.5,
                'is_featured' => false,
                'is_active' => true,
                'icon' => 'ti-droplet',
            ],
            [
                'name' => 'شامبو Cliven',
                'sku' => 'CLV-204',
                'category_name' => 'العناية بالشعر',
                'shop_name' => 'Healthy Shop',
                'price' => 28,
                'discount_price' => 22,
                'quantity' => 0,
                'rating' => 4.2,
                'is_featured' => true,
                'is_active' => false,
                'icon' => 'ti-spray',
            ],
            [
                'name' => 'ماسكرا للرجال',
                'sku' => 'MEN-018',
                'category_name' => 'العناية للرجال',
                'shop_name' => 'Ozman الرئيسي',
                'price' => 38,
                'discount_price' => null,
                'quantity' => 55,
                'rating' => 4.0,
                'is_featured' => false,
                'is_active' => true,
                'icon' => 'ti-gender-male',
            ],
        ]);

        $productsTotal = $productsCount ?? $productItems->count();
        $featuredTotal = $featuredProductsCount ?? $productItems->filter(fn($product) => (bool) data_get($product, 'is_featured'))->count();
        $outOfStockTotal = $outOfStockProductsCount ?? $productItems->filter(fn($product) => (int) data_get($product, 'quantity', 0) <= 0)->count();
        $averagePrice = $averageProductPrice ?? round($productItems->avg(fn($product) => (float) data_get($product, 'price', 0)) ?: 0);
        $categoryOptions = collect($categories ?? []);
        $categoriesByShop = $categoryOptions->groupBy('shop_id');
        $currentUser = auth()->user();
        // $currentUserAgentIds comes from PageDataComposers::agentIds.
        $canCreateProducts = $currentUser?->canAccessRouteName('products.create');
        $canEditProducts = $currentUser?->canAccessRouteName('products.edit');
        $canDeleteProducts = $currentUser?->canAccessRouteName('products.destroy');
        $canManageProduct = fn($product) => ($canEditProducts || $canDeleteProducts)
            && (! $currentUser?->isAgent() || in_array((int) data_get($product, 'agent_id'), $currentUserAgentIds, true));
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'إدارة ' . $itemsLabel])

            <div class="content">
                <section class="hero-strip" aria-label="شريط {{ $itemsLabel }}">
                    <div class="ticker">
                        @if($isRestaurantContext)
                            <span>إدارة وجبات {{ $currentShop->name }} من مكان واحد</span>
                            <span>تحكم بالفئات والأسعار والصور بسهولة</span>
                            <span>الوجبات النشطة تظهر في منيو المطعم مباشرة</span>
                            <span>لوحة وجبات مطعم {{ $currentShop->name }}</span>
                        @else
                            <span>إدارة منتجات Ozman بشكل حديث وسريع</span>
                            <span>تحكم بالفئات والأسعار والمخزون من مكان واحد</span>
                            <span>منتجات مميزة تظهر بواجهة المتجر مباشرة</span>
                            <span>إدارة منتجات Ozman بشكل حديث وسريع</span>
                        @endif
                    </div>
                </section>

                <header class="page-head">
                    <div>
                        <div class="page-kicker">{{ $currentShop ? ($catalogTerms['dashboard_label'].' · '.$currentShop->name) : $placeLabel }}</div>
                        <h1>إدارة {{ $itemsLabel }}</h1>
                        <p>{{ $productsTotal }} من {{ $itemsLabel }}، مع الأسعار وحالة الظهور للزبائن.</p>
                    </div>
                    @if($canCreateProducts)
                        <a href="{{ route('products.create', $currentShop ? ['shop_id' => $currentShop->id] : []) }}" class="btn-primary">
                            <i class="ti ti-plus" aria-hidden="true"></i>
                            {{ $addItemLabel }}
                        </a>
                    @endif
                </header>

                @if(session('status'))
                    <div class="status-alert">{{ session('status') }}</div>
                @endif

                <section class="stats-grid {{ $isRestaurantContext ? 'restaurant-stats-grid' : '' }}" aria-label="إحصائيات {{ $itemsLabel }}">
                    <article class="stat-card" style="--card-color: var(--primary)">
                        <div class="stat-label">إجمالي {{ $itemsLabel }}</div>
                        <div class="stat-val">{{ $productsTotal }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--card-color: var(--yellow)">
                        <div class="stat-label">{{ $itemsLabel }} المميزة</div>
                        <div class="stat-val">{{ $featuredTotal }}</div>
                        <i class="ti ti-star stat-icon" aria-hidden="true"></i>
                    </article>

                    @if(! $isRestaurantContext && ($tracksStock ?? false))
                        <article class="stat-card" style="--card-color: var(--danger)">
                            <div class="stat-label">نفدت الكمية</div>
                            <div class="stat-val">{{ $outOfStockTotal }}</div>
                            <i class="ti ti-alert-triangle stat-icon" aria-hidden="true"></i>
                        </article>
                    @elseif(! $isRestaurantContext)
                        <article class="stat-card" style="--card-color: var(--danger)">
                            <div class="stat-label">مخفية عن الزبائن</div>
                            <div class="stat-val">{{ $hiddenProductsCount ?? 0 }}</div>
                            <i class="ti ti-eye-off stat-icon" aria-hidden="true"></i>
                        </article>
                    @endif

                    <article class="stat-card" style="--card-color: var(--accent)">
                        <div class="stat-label">متوسط السعر</div>
                        <div class="stat-val">{{ $averagePrice }}₪</div>
                        <i class="ti ti-coin stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <h2 class="panel-title">
                            <i class="ti ti-list-details" aria-hidden="true"></i>
                            قائمة {{ $itemsLabel }}
                        </h2>

                        <div class="filter-row">
                            <select class="filter-select" id="catFilter" aria-label="فلترة الفئة">
                                <option value="">كل الفئات</option>
                                @foreach($categoryOptions as $category)
                                    <option value="{{ data_get($category, 'id', $category) }}">{{ data_get($category, 'name', $category) }}</option>
                                @endforeach
                            </select>

                            <div class="input-wrap">
                                <i class="ti ti-search" aria-hidden="true"></i>
                                <input class="search-inp" id="prodSearch" type="search" placeholder="بحث بالاسم، المتجر، الفئة...">
                            </div>
                        </div>
                    </div>

                        <p data-product-feedback class="meal-quick-feedback" role="status"></p>
                        <div class="meal-grid" id="productCards">
                            @forelse($productItems as $product)
                                @php
                                    $categoryName = data_get($product, 'category.name', data_get($product, 'category_name', '-'));
                                    $image = data_get($product, 'main_image');
                                    $isActive = (bool) data_get($product, 'is_active', true);
                                    $canQuickManageMeal = $canManageProduct($product) && $canEditProducts;
                                @endphp
                                <article class="meal-card {{ $isActive ? '' : 'is-inactive' }}" data-cat="{{ $product->category_id }}" data-name="{{ $product->name }}" data-shop="{{ $product->shop?->name }}" data-category-name="{{ $categoryName }}">
                                    @if($canQuickManageMeal)
                                        <form class="meal-card-form js-meal-quick-form" action="{{ route('products.quick-settings', $product) }}" method="post">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $isActive ? 1 : 0 }}">
                                            <div class="meal-media">
                                                @if($image)
                                                    <img src="{{ asset($image) }}" alt="{{ data_get($product, 'name', '') }}">
                                                @else
                                                    <span class="meal-placeholder"><i class="ti ti-package"></i></span>
                                                @endif
                                                <button type="button" class="meal-power {{ $isActive ? 'is-active' : '' }}" data-meal-toggle aria-label="{{ $isActive ? 'إيقاف المنتج' : 'تشغيل المنتج' }}">
                                                    <i class="ti {{ $isActive ? 'ti-power' : 'ti-power' }}"></i>
                                                    <span data-meal-power-copy>{{ $isActive ? 'متاحة' : 'موقوفة' }}</span>
                                                </button>
                                            </div>
                                            <div class="meal-card-body">
                                                <div class="meal-card-head">
                                                    <div>
                                                        <div class="meal-name">{{ data_get($product, 'name', '-') }}</div>
                                                        <span class="meal-category">{{ $categoryName }}</span>
                                                        @unless($currentShop)<span class="meal-category">{{ $product->shop?->name }}</span>@endunless
                                                    </div>
                                                    <div class="product-card-actions">
                                                    <a href="{{ route('products.edit', $product) }}" class="icon-btn meal-detail-link" aria-label="تعديل كامل">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <button type="button" class="icon-btn" data-move-category aria-label="نقل القسم" title="نقل القسم" aria-expanded="false" aria-controls="move-category-{{ $product->id }}"><i class="ti ti-folder-symlink"></i></button>
                                                    </div>
                                                </div>
                                                <div class="product-category-editor" id="move-category-{{ $product->id }}" data-category-editor data-url="{{ route('products.move-category', $product) }}" hidden>
                                                    <label for="category-select-{{ $product->id }}">نقل إلى قسم</label>
                                                    <select id="category-select-{{ $product->id }}" class="filter-select" data-category-select>
                                                        @foreach($categoriesByShop->get($product->shop_id, collect()) as $destination)
                                                            <option value="{{ $destination->id }}" @selected($destination->id == $product->category_id)>{{ $destination->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="product-card-actions">
                                                        <button type="button" class="btn btn-primary" data-category-save>نقل القسم</button>
                                                        <button type="button" class="btn" data-category-cancel>إلغاء</button>
                                                    </div>
                                                    <span data-category-feedback role="status" class="meal-quick-feedback"></span>
                                                </div>
                                                <div class="meal-price-row">
                                                    <input class="meal-price-input" type="number" name="price" min="0" max="99999999.99" step="0.01" value="{{ data_get($product, 'price', 0) }}" aria-label="سعر المنتج">
                                                    <button class="meal-save" type="submit" aria-label="حفظ السعر"><i class="ti ti-check"></i></button>
                                                </div>
                                                <span class="meal-quick-feedback" aria-live="polite"></span>
                                            </div>
                                        </form>
                                    @else
                                        <div class="meal-media">
                                            @if($image)
                                                <img src="{{ asset($image) }}" alt="{{ data_get($product, 'name', '') }}">
                                            @else
                                                <span class="meal-placeholder"><i class="ti ti-package"></i></span>
                                            @endif
                                            <span class="meal-power {{ $isActive ? 'is-active' : '' }}"><i class="ti ti-power"></i>{{ $isActive ? 'متاحة' : 'موقوفة' }}</span>
                                        </div>
                                        <div class="meal-card-body">
                                            <div class="meal-name">{{ data_get($product, 'name', '-') }}</div>
                                            <span class="meal-category">{{ $categoryName }}</span>
                                            @unless($currentShop)<span class="meal-category">{{ $product->shop?->name }}</span>@endunless
                                            <div class="price" style="margin-top:12px">{{ data_get($product, 'price', 0) }}₪</div>
                                        </div>
                                    @endif
                                    <div class="product-card-footer">
                                        @unless($product->shop?->catalog_type === 'restaurant')
                                            <details><summary>تفاصيل الأسعار</summary>
                                                <dl class="product-card-details">
                                                    @if((int) $product->quantity > 0)<dt>الكمية</dt><dd>{{ $product->quantity }}</dd>@endif
                                                    @foreach(['discount_price' => 'سعر الخصم', 'merchant_price' => 'سعر التاجر', 'package_price' => 'سعر العبوة', 'pallet_price' => 'سعر المشطاح', 'carton_price' => 'سعر الكرتونة'] as $field => $label)
                                                        @if($product->$field !== null)<dt>{{ $label }}</dt><dd>{{ $product->$field }} ₪</dd>@endif
                                                    @endforeach
                                                    <dt>التقييم</dt><dd>{{ $product->rating ?? '-' }}</dd>
                                                    <dt>مميز</dt><dd>{{ $product->is_featured ? 'نعم' : 'لا' }}</dd>
                                                </dl>
                                            </details>
                                        @endunless
                                        <div class="product-card-actions">
                                            <a href="{{ route('products.show', $product) }}" class="icon-btn" aria-label="عرض المنتج" title="عرض المنتج"><i class="ti ti-eye"></i></a>
                                            @if($canManageProduct($product) && $canDeleteProducts)
                                                <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذا المنتج؟')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="icon-btn" aria-label="حذف المنتج" title="حذف المنتج"><i class="ti ti-trash"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="empty-state">
                                    <i class="ti ti-tools-kitchen-2" aria-hidden="true"></i>
                                    لا توجد {{ $itemsLabel }} لعرضها حاليًا
                                </div>
                            @endforelse
                        </div>
                </section>
            </div>
        </main>
    </div>

    @viewScript('admin.products.products')
</body>

</html>
