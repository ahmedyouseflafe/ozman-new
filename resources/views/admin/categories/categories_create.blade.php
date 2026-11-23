<!DOCTYPE html>
@php
    $formShop = $selectedShop ?? $shops->firstWhere('id', (int) old('shop_id', $selectedShopId));
    $isRestaurantForm = $formShop?->catalog_type === 'restaurant';
    $catalogTerms = $formShop?->catalogTerms() ?? config('catalog_terminology.general');
    $categoryName = $catalogTerms['category_singular'];
    $categoriesName = $catalogTerms['category_plural'];
    $itemsLabel = $catalogTerms['item_plural'];
    $addCategoryLabel = $catalogTerms['add_category'];
    $placeLabel = $catalogTerms['place_singular'];
@endphp
<html lang="ar" dir="rtl">

<head>
    <title>{{ $addCategoryLabel }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.categories.categories_create')
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => $addCategoryLabel])

            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $addCategoryLabel }}</h1>
                        <p>أنشئ {{ $categoryName }} لتنظيم {{ $itemsLabel }} داخل {{ $placeLabel }} وحدد اسمه وصورته وحالة ظهوره.</p>
                    </div>
                    <a href="{{ route('categories') }}" class="btn">
                        <i class="ti ti-arrow-right" aria-hidden="true"></i>
                        رجوع إلى {{ $categoriesName }}
                    </a>
                </header>

                @if($errors->any())
                    <div class="alert">
                        <strong>راجع الحقول التالية:</strong>
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="form-shell" action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-category-plus" aria-hidden="true"></i></div>
                            <div>
                                <h2>بيانات {{ $categoryName }}</h2>
                                <p>الحقول الأساسية الخاصة بتنظيم {{ $itemsLabel }}.</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                @if($lockShopSelection && $formShop)
                                    <label class="form-label" for="shop_id"><i class="ti ti-building-store" aria-hidden="true"></i>{{ $placeLabel }} الحالي</label>
                                    <div class="locked-shop-field">
                                        <i class="ti {{ $isRestaurantForm ? 'ti-tools-kitchen-2' : 'ti-building-store' }}" aria-hidden="true"></i>
                                        <div>
                                            <strong>{{ $formShop->name }}</strong>
                                            <span>{{ $isRestaurantForm ? 'سيُضاف القسم مباشرة إلى منيو هذا المطعم' : 'سيتم الحفظ مباشرة داخل هذا المتجر' }}</span>
                                        </div>
                                    </div>
                                    <input type="hidden" id="shop_id" name="shop_id" value="{{ $formShop->id }}" data-catalog-type="{{ $formShop->catalog_type ?: 'general' }}">
                                @else
                                    <label class="form-label" for="shop_id"><i class="ti ti-building-store" aria-hidden="true"></i>المتجر</label>
                                    <select id="shop_id" name="shop_id" required>
                                        <option value="">اختر المتجر</option>
                                        @foreach($shops as $shop)
                                            <option value="{{ $shop->id }}" data-catalog-type="{{ $shop->catalog_type ?: 'general' }}" @selected(old('shop_id', $selectedShopId) == $shop->id)>{{ $shop->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            @include('admin.categories._catalog_guidance')

                            <div class="form-group">
                                <label class="form-label" for="name"><i class="ti ti-tag" aria-hidden="true"></i>اسم {{ $categoryName }}</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" data-auto-translate-source placeholder="مثال: مشروبات" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="name_en">اسم {{ $categoryName }} بالإنجليزي</label>
                                <input type="text" id="name_en" name="name_en" value="{{ old('name_en') }}" dir="ltr">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="name_he">اسم {{ $categoryName }} بالعبري</label>
                                <input type="text" id="name_he" name="name_he" value="{{ old('name_he') }}">
                            </div>

                            <div class="form-group full">
                                <label class="form-label" for="slug"><i class="ti ti-link" aria-hidden="true"></i>الرابط المختصر</label>
                                <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="اتركه فارغاً ليتم توليده تلقائياً">
                            </div>

                            <div class="form-group full">
                                <label class="upload-box">
                                    <input type="file" name="image" accept="image/*">
                                    <span class="card-icon"><i class="ti ti-photo-up" aria-hidden="true"></i></span>
                                    <span>
                                        <span class="card-title">صورة {{ $categoryName }}</span>
                                        <span class="card-sub">صورة حتى 20 MB، اختياري</span>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group full">
                                <label class="upload-box">
                                    <input type="file" name="background_video" accept="video/mp4,video/webm">
                                    <span class="card-icon"><i class="ti ti-video-plus" aria-hidden="true"></i></span>
                                    <span>
                                        <span class="card-title">فيديو خلفية {{ $categoryName }}</span>
                                        <span class="card-sub">MP4 أو WebM حتى 20MB — اختياري ويظهر متحركًا خلف {{ $itemsLabel }} داخل {{ $categoryName }}.</span>
                                    </span>
                                </label>
                            </div>

                            <div class="form-group full">
                                <div class="switch-card">
                                    <div class="card-copy">
                                        <span class="card-icon"><i class="ti ti-circle-check" aria-hidden="true"></i></span>
                                        <span>
                                            <span class="card-title">تفعيل {{ $categoryName }}</span>
                                            <span class="card-sub">يظهر {{ $categoryName }} النشط للزبائن داخل {{ $placeLabel }}.</span>
                                        </span>
                                    </div>
                                    <label class="switch" for="is_active">
                                        <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy" aria-hidden="true"></i>
                            حفظ {{ $categoryName }}
                        </button>
                        <a href="{{ route('categories') }}" class="btn">رجوع</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    @include('admin.includes.auto_translate')
</body>

</html>
