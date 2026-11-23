<!DOCTYPE html>
<html lang="ar" dir="rtl">

@php
    $formShopId = (int) old('shop_id', $selectedShopId);
    $formShop = $shops->firstWhere('id', $formShopId) ?: $selectedShop;
    $isRestaurantForm = $formShop?->catalog_type === 'restaurant';
    $catalogTerms = $formShop?->catalogTerms() ?? config('catalog_terminology.general');
    $itemLabel = $catalogTerms['item_singular'];
    $itemsLabel = $catalogTerms['item_plural'];
    $addItemLabel = $catalogTerms['add_item'];
    $placeLabel = $catalogTerms['place_singular'];
    $categoryLabel = $catalogTerms['category_singular'];
@endphp

<head>
    <title>{{ $addItemLabel }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.products.products_create')
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => $addItemLabel])
            <div class="content">
                <header class="page-head">
                    <div>
                        <h1>{{ $addItemLabel }}</h1>
                        <p>أدخل بيانات {{ $itemLabel }} والسعر والصور وحالة الظهور في {{ $placeLabel }}.</p>
                    </div>
                    <a href="{{ route('products') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع لـ{{ $itemsLabel }}</a>
                </header>

                @if ($errors->any())
                    <div class="alert">
                        <strong>راجع الحقول التالية:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="form-shell" action="{{ route('products.store') }}" method="POST"
                    data-product-autosave="product-create"
                    enctype="multipart/form-data">
                    @csrf
                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-package"></i></div>
                            <div>
                                <h2>بيانات {{ $itemLabel }}</h2>
                                <p>{{ $placeLabel }} و{{ $categoryLabel }} والاسم والوصف.</p>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label"><i class="ti ti-building-store"></i>{{ $placeLabel }}</label>
                                @if($lockShopSelection && $formShop)
                                    <div class="locked-shop-field">
                                        <i class="ti ti-tools-kitchen-2"></i>
                                        <span><small>{{ $catalogTerms['dashboard_label'] }} الحالية</small>{{ $formShop->name }}</span>
                                    </div>
                                    <input type="hidden" id="shop_id" name="shop_id" value="{{ $formShop->id }}" data-catalog-type="{{ $formShop->catalog_type ?: 'general' }}">
                                @else
                                    <select id="shop_id" name="shop_id" required>
                                        <option value="">اختر المتجر</option>
                                        @foreach ($shops as $shop)
                                            <option value="{{ $shop->id }}" data-catalog-type="{{ $shop->catalog_type ?: 'general' }}" @selected(old('shop_id', $selectedShopId) == $shop->id)>
                                                {{ $shop->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="category_id"><i class="ti ti-category"></i>{{ $categoryLabel }}</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">اختر {{ $categoryLabel }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" data-shop-id="{{ $category->shop_id }}"
                                            @selected(old('category_id', $selectedCategoryId) == $category->id)>{{ $category->name }} -
                                            {{ $category->shop?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="agent_id"><i class="ti ti-user-star"></i>الوكيل</label>
                                <select id="agent_id" name="agent_id">
                                    <option value="">منتج رئيسي للمتجر</option>
                                    @foreach ($agents as $agent)
                                        <option value="{{ $agent->id }}" data-shop-id="{{ $agent->shop_id }}"
                                            @selected(old('agent_id') == $agent->id)>{{ $agent->name }} -
                                            {{ $agent->shop?->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="name"><i class="ti ti-tag"></i>اسم {{ $itemLabel }}</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                    data-auto-translate-source
                                    required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="name_en">اسم {{ $itemLabel }} بالإنجليزي</label>
                                <input type="text" id="name_en" name="name_en" value="{{ old('name_en') }}" dir="ltr">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="name_he">اسم {{ $itemLabel }} بالعبري</label>
                                <input type="text" id="name_he" name="name_he" value="{{ old('name_he') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="slug"><i class="ti ti-link"></i>الرابط
                                    المختصر</label>
                                <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                                    placeholder="اختياري">
                            </div>
                            <div class="form-group full">
                                <label class="form-label" for="description"><i
                                        class="ti ti-align-right"></i>الوصف</label>
                                <textarea id="description" name="description" data-auto-translate-source>{{ old('description') }}</textarea>
                            </div>
                            <div class="form-group full">
                                <label class="form-label" for="description_en">الوصف بالإنجليزي</label>
                                <textarea id="description_en" name="description_en" dir="ltr">{{ old('description_en') }}</textarea>
                            </div>
                            <div class="form-group full">
                                <label class="form-label" for="description_he">الوصف بالعبري</label>
                                <textarea id="description_he" name="description_he">{{ old('description_he') }}</textarea>
                            </div>
                        </div>
                    </section>

                    @include('admin.products._catalog_fields')

                    @include('admin.products._simple_pricing')

                    <section class="form-section" id="legacyProductPricingSection">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-coin"></i></div>
                            <div>
                                <h2>الأسعار والمخزون</h2>
                                <p>أسعار منفصلة للعميل والتاجر، مع بيانات المخزون والتعريف.</p>
                            </div>
                        </div>
                        <div class="pricing-sections">
                            <div class="pricing-card customer">
                                <div class="pricing-card-head"><i class="ti ti-user"></i><div><h3>أسعار العميل</h3><p>الأسعار التي تظهر للعميل في المتجر.</p></div></div>
                                <div class="form-grid">
                                    <div class="form-group"><div class="price-label-row"><label class="form-label" for="customer_package_price">سعر العبوة للعميل</label><label class="visibility-check"><input type="hidden" name="show_customer_package_price" value="0"><input type="checkbox" name="show_customer_package_price" value="1" @checked((bool) old('show_customer_package_price', true))>إظهار في الصفحة</label></div><input type="number" step="0.01" min="0" id="customer_package_price" name="customer_package_price" value="{{ old('customer_package_price') }}"></div>
                                    <div class="form-group"><div class="price-label-row"><label class="form-label" for="customer_carton_price">سعر الكرتونة للعميل</label><label class="visibility-check"><input type="hidden" name="show_customer_carton_price" value="0"><input type="checkbox" name="show_customer_carton_price" value="1" @checked((bool) old('show_customer_carton_price', true))>إظهار في الصفحة</label></div><input type="number" step="0.01" min="0" id="customer_carton_price" name="customer_carton_price" value="{{ old('customer_carton_price') }}"></div>
                                    <div class="form-group"><div class="price-label-row"><label class="form-label" for="customer_pallet_price">سعر المشطاح للعميل</label><label class="visibility-check"><input type="hidden" name="show_customer_pallet_price" value="0"><input type="checkbox" name="show_customer_pallet_price" value="1" @checked((bool) old('show_customer_pallet_price', true))>إظهار في الصفحة</label></div><input type="number" step="0.01" min="0" id="customer_pallet_price" name="customer_pallet_price" value="{{ old('customer_pallet_price') }}"></div>
                                </div>
                            </div>
                            <div class="pricing-card merchant">
                                <div class="pricing-card-head"><i class="ti ti-building-store"></i><div><h3>أسعار التاجر</h3><p>أسعار البيع والكميات التجارية.</p></div></div>
                                <div class="form-grid">
                                    <div class="form-group"><div class="price-label-row"><label class="form-label" for="package_price">سعر العبوة</label><label class="visibility-check"><input type="hidden" name="show_package_price" value="0"><input type="checkbox" name="show_package_price" value="1" @checked((bool) old('show_package_price', true))>إظهار في الصفحة</label></div><input type="number" step="0.01" min="0" id="package_price" name="package_price" value="{{ old('package_price') }}"></div>
                                    <div class="form-group"><div class="price-label-row"><label class="form-label" for="carton_price">سعر الكرتونة</label><label class="visibility-check"><input type="hidden" name="show_carton_price" value="0"><input type="checkbox" name="show_carton_price" value="1" @checked((bool) old('show_carton_price', true))>إظهار في الصفحة</label></div><input type="number" step="0.01" min="0" id="carton_price" name="carton_price" value="{{ old('carton_price') }}"></div>
                                    <div class="form-group"><div class="price-label-row"><label class="form-label" for="pallet_price">سعر المشطاح</label><label class="visibility-check"><input type="hidden" name="show_pallet_price" value="0"><input type="checkbox" name="show_pallet_price" value="1" @checked((bool) old('show_pallet_price', true))>إظهار في الصفحة</label></div><input type="number" step="0.01" min="0" id="pallet_price" name="pallet_price" value="{{ old('pallet_price') }}"></div>
                                </div>
                            </div>
                            <div class="pricing-card inventory">
                                <div class="pricing-card-head"><i class="ti ti-box"></i><div><h3>المخزون والتعريف</h3><p>الكمية، التقييم، SKU والباركود.</p></div></div>
                                <div class="form-grid">
                                    <div class="form-group"><label class="form-label" for="quantity">الكمية</label><input type="number" min="0" id="quantity" name="quantity" value="{{ old('quantity', 0) }}"></div>
                                    <div class="form-group"><label class="form-label" for="rating">التقييم</label><input type="number" step="0.1" min="0" max="5" id="rating" name="rating" value="{{ old('rating', 0) }}"></div>
                                    <div class="form-group"><label class="form-label" for="sku">SKU</label><input type="text" id="sku" name="sku" value="{{ old('sku') }}" dir="ltr"></div>
                                    <div class="form-group"><label class="form-label" for="barcode">Barcode</label><input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}" dir="ltr"></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-photo-up"></i></div>
                            <div>
                                <h2>الصور والفيديو</h2>
                                <p>صورة رئيسية، فيديو اختياري، وصور إضافية لـ{{ $itemLabel }}.</p>
                            </div>
                        </div>
                        <div class="upload-grid">
                            <label class="upload-box"><input type="file" name="main_image"
                                    accept="image/*,.gif"><span class="card-icon"><i
                                        class="ti ti-photo"></i></span><span><span class="card-title">الصورة
                                        الرئيسية</span><span class="card-sub">PNG أو JPG أو GIF</span></span></label>
                            <label class="upload-box"><input type="file" name="video" accept="video/*"><span
                                    class="card-icon"><i class="ti ti-video"></i></span><span><span
                                        class="card-title">فيديو {{ $itemLabel }}</span><span class="card-sub">MP4 أو
                                        WebM</span></span></label>
                            <label class="upload-box"><input type="file" name="images[]" accept="image/*,.gif"
                                    multiple><span class="card-icon"><i
                                        class="ti ti-library-photo"></i></span><span><span class="card-title">صور
                                        إضافية</span><span class="card-sub">يمكن اختيار أكثر من صورة أو
                                        GIF</span></span></label>
                        </div>
                    </section>

                    <section class="form-section" data-catalog-campaigns>
                        <div class="section-head">
                            <div class="section-icon"><i class="ti ti-speakerphone"></i></div>
                            <div>
                                <h2>حملات {{ $itemLabel }}</h2>
                                <p>أضف عرض الحملة فعليًا مثل: 3 عبوات بسعر 10، مع صورة أو فيديو اختياري.</p>
                            </div>
                        </div>

                        <div class="campaign-list" id="campaignList">
                            @php
                                $oldCampaigns = old('campaigns', [['title' => '', 'type' => 'image', 'offer_type' => 'bundle_price']]);
                            @endphp

                            @foreach ($oldCampaigns as $index => $campaign)
                                <div class="campaign-card" data-campaign-card>
                                    <div class="campaign-head">
                                        <div class="campaign-title">
                                            <i class="ti ti-ad" aria-hidden="true"></i>
                                            حملة رقم <span data-campaign-number>{{ $loop->iteration }}</span>
                                        </div>
                                        <button type="button" class="campaign-remove"
                                            data-remove-campaign>حذف</button>
                                    </div>

                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">عنوان الحملة</label>
                                            <input type="text" name="campaigns[{{ $index }}][title]"
                                                data-auto-translate-source
                                                value="{{ data_get($campaign, 'title') }}"
                                                placeholder="مثال: عرض الصيف">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">عنوان الحملة بالإنجليزي</label>
                                            <input type="text" name="campaigns[{{ $index }}][title_en]" value="{{ data_get($campaign, 'title_en') }}" dir="ltr">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">عنوان الحملة بالعبري</label>
                                            <input type="text" name="campaigns[{{ $index }}][title_he]" value="{{ data_get($campaign, 'title_he') }}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">نوع الحملة</label>
                                            <select name="campaigns[{{ $index }}][type]">
                                                <option value="image" @selected(data_get($campaign, 'type', 'image') === 'image')>صورة</option>
                                                <option value="video" @selected(data_get($campaign, 'type') === 'video')>فيديو</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">نوع العرض</label>
                                            <select name="campaigns[{{ $index }}][offer_type]">
                                                <option value="bundle_price" @selected(data_get($campaign, 'offer_type', 'bundle_price') === 'bundle_price')>عدد بسعر محدد</option>
                                                <option value="range_price" @selected(data_get($campaign, 'offer_type') === 'range_price')>سعر حسب نطاق الكمية</option>
                                                <option value="custom" @selected(data_get($campaign, 'offer_type') === 'custom')>عرض مخصص</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">تطبيق الحملة على</label>
                                            <select name="campaigns[{{ $index }}][unit_key]">
                                                <option value="" @selected(data_get($campaign, 'unit_key') === null || data_get($campaign, 'unit_key') === '')>السعر الأساسي</option>
                                                <option value="package" @selected(data_get($campaign, 'unit_key') === 'package')>العبوة</option>
                                                <option value="pallet" @selected(data_get($campaign, 'unit_key') === 'pallet')>المشطاح</option>
                                                <option value="carton" @selected(data_get($campaign, 'unit_key') === 'carton')>الكرتونة</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">عدد القطع في العرض</label>
                                            <input type="number" min="1" name="campaigns[{{ $index }}][offer_quantity]"
                                                value="{{ data_get($campaign, 'offer_quantity') }}" placeholder="مثال: 3">
                                        </div>
                                        <div class="form-group"><label class="form-label">الكمية من</label><input type="number" min="1" name="campaigns[{{ $index }}][min_quantity]" value="{{ data_get($campaign, 'min_quantity') }}" placeholder="مثال: 40"></div>
                                        <div class="form-group"><label class="form-label">الكمية إلى</label><input type="number" min="1" name="campaigns[{{ $index }}][max_quantity]" value="{{ data_get($campaign, 'max_quantity') }}" placeholder="مثال: 100"></div>
                                        <div class="form-group">
                                            <label class="form-label">سعر الوحدة في العرض</label>
                                            <input type="number" min="0" step="0.01" name="campaigns[{{ $index }}][offer_price]"
                                                value="{{ data_get($campaign, 'offer_price') }}" placeholder="مثال: 10">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">بداية العرض</label>
                                            <input type="date" name="campaigns[{{ $index }}][starts_at]"
                                                value="{{ data_get($campaign, 'starts_at') }}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">نهاية العرض</label>
                                            <input type="date" name="campaigns[{{ $index }}][ends_at]"
                                                value="{{ data_get($campaign, 'ends_at') }}">
                                        </div>
                                        <div class="form-group full">
                                            <label class="form-label">وصف العرض</label>
                                            <textarea name="campaigns[{{ $index }}][offer_note]" data-auto-translate-source placeholder="مثال: اشتري 3 عبوات بسعر 10 بدل 12">{{ data_get($campaign, 'offer_note') }}</textarea>
                                        </div>
                                        <div class="form-group full">
                                            <label class="form-label">وصف العرض بالإنجليزي</label>
                                            <textarea name="campaigns[{{ $index }}][offer_note_en]" dir="ltr">{{ data_get($campaign, 'offer_note_en') }}</textarea>
                                        </div>
                                        <div class="form-group full">
                                            <label class="form-label">وصف العرض بالعبري</label>
                                            <textarea name="campaigns[{{ $index }}][offer_note_he]">{{ data_get($campaign, 'offer_note_he') }}</textarea>
                                        </div>
                                        <div class="form-group full">
                                            <label class="upload-box">
                                                <input type="file" name="campaigns[{{ $index }}][media]"
                                                    accept="image/*,video/*">
                                                <span class="card-icon"><i class="ti ti-upload"></i></span>
                                                <span>
                                                    <span class="card-title">ملف الحملة</span>
                                                    <span class="card-sub">اختياري: اختر صورة أو فيديو للحملة</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-top:15px">
                            <button type="button" class="btn" id="addCampaign">
                                <i class="ti ti-plus"></i>
                                إضافة حملة
                            </button>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="switch-grid">
                            <div class="switch-card">
                                <div class="card-copy"><span class="card-icon"><i
                                            class="ti ti-star"></i></span><span><span class="card-title">تمييز {{ $itemLabel }}</span><span class="card-sub">إظهار {{ $itemLabel }} ضمن العناصر المميزة.</span></span>
                                </div>
                                <label class="switch" for="is_featured"><input type="checkbox" id="is_featured"
                                        name="is_featured" value="1" @checked(old('is_featured'))><span
                                        class="slider"></span></label>
                            </div>
                            <div class="switch-card">
                                <div class="card-copy"><span class="card-icon"><i
                                            class="ti ti-circle-check"></i></span><span><span class="card-title">تفعيل
                                            {{ $itemLabel }}</span><span class="card-sub">يظهر العنصر النشط للزبائن داخل {{ $placeLabel }}.</span></span>
                                </div>
                                <label class="switch" for="is_active"><input type="checkbox" id="is_active"
                                        name="is_active" value="1" @checked(old('is_active', true))><span
                                        class="slider"></span></label>
                            </div>
                        </div>
                    </section>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i>حفظ
                            {{ $itemLabel }}</button>
                        <a href="{{ route('products') }}" class="btn">رجوع</a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    @viewScript('admin.products.products_create')
    @include('admin.products.autosave')
    @include('admin.includes.auto_translate')
</body>

</html>
