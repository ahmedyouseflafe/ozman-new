<!DOCTYPE html>
@php
    $isRestaurantCategory = $category->shop?->catalog_type === 'restaurant';
    $catalogTerms = $category->shop?->catalogTerms() ?? config('catalog_terminology.general');
    $categoryName = $catalogTerms['category_singular'];
    $categoriesName = $catalogTerms['category_plural'];
    $itemLabel = $catalogTerms['item_singular'];
    $itemsLabel = $catalogTerms['item_plural'];
    $placeLabel = $catalogTerms['place_singular'];
@endphp
<html lang="ar" dir="rtl">

<head>
    <title>{{ $category->name }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.categories.categories_show')
</head>

<body>
    @php
        $currentUser = auth()->user();
        $canCreateProducts = $currentUser?->canAccessRouteName('products.create');
        $canEditCategories = $currentUser?->canAccessRouteName('categories.edit');
    @endphp
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض '.$categoryName])

            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $category->name }}</h1>
                        <p>تفاصيل {{ $categoryName }} وعدد {{ $itemsLabel }} المرتبطة به.</p>
                    </div>
                    <a href="{{ route('categories', ['shop_id' => $category->shop_id]) }}" class="btn">
                        <i class="ti ti-arrow-right" aria-hidden="true"></i>
                        رجوع إلى {{ $categoriesName }}
                    </a>
                </header>

                <section class="panel">
                    <div class="hero">
                        <div class="image">
                            @if($category->image)
                                <img src="{{ asset($category->image) }}" alt="{{ $category->name }}">
                            @else
                                <i class="ti ti-category" aria-hidden="true"></i>
                            @endif
                        </div>
                        <div>
                            <span class="badge {{ $category->is_active ? '' : 'off' }}">
                                <i class="ti {{ $category->is_active ? 'ti-circle-check' : 'ti-circle-x' }}" aria-hidden="true"></i>
                                {{ $category->is_active ? 'نشط' : 'غير نشط' }}
                            </span>
                            <h1 style="margin-top:12px">{{ $category->name }}</h1>
                        </div>
                    </div>

                    <div class="grid">
                        <div class="item">
                            <div class="label">{{ $placeLabel }}</div>
                            <div class="value">{{ $category->shop?->name ?? '-' }}</div>
                        </div>
                        <div class="item">
                            <div class="label">الرابط المختصر</div>
                            <div class="value">{{ $category->slug }}</div>
                        </div>
                        <div class="item">
                            <div class="label">عدد {{ $itemsLabel }}</div>
                            <div class="value">{{ $category->products_count }} {{ $itemLabel }}</div>
                        </div>
                        <div class="item">
                            <div class="label">تاريخ الإضافة</div>
                            <div class="value">{{ optional($category->created_at)->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>

                    <div class="actions">
                        @if($canCreateProducts)
                            <a href="{{ route('products.create', ['shop_id' => $category->shop_id, 'category_id' => $category->id]) }}" class="btn btn-primary">
                                <i class="ti ti-package-plus" aria-hidden="true"></i>
                                {{ $catalogTerms['add_item'] }}
                            </a>
                        @endif
                        @if($canEditCategories)
                            <a href="{{ route('categories.edit', $category) }}" class="btn btn-primary">
                                <i class="ti ti-edit" aria-hidden="true"></i>
                                تعديل {{ $categoryName }}
                            </a>
                        @endif
                        <a href="{{ route('categories', ['shop_id' => $category->shop_id]) }}" class="btn">رجوع</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>

</html>
