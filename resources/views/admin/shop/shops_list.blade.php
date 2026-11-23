@php
    $catalogTerms = $shop->catalogTerms();
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    @include('admin.partials.merchant_app_manifest', ['shop' => $shop])
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shop->name }} - Ozman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.shop.shops_list')
</head>

<body>
    @php
        $currentUser = auth()->user();
        $canCreateCategories = $currentUser?->canAccessRouteName('categories.create');
        $canEditCategories = $currentUser?->canAccessRouteName('categories.edit');
        $canDeleteCategories = $currentUser?->canAccessRouteName('categories.destroy');
        $canCreateAgents = $currentUser?->canAccessRouteName('agents.create');
        $canCreateDistributors = $currentUser?->canAccessRouteName('distributors.create');
        $canCreateAds = $currentUser?->canAccessRouteName('ads.create');
    @endphp
    <div class="shell">
        @include('admin.includes.sidebar')

        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض المتجر'])

            <div class="content">
                <section class="hero">
                    @if($shop->banner)
                        <img src="{{ asset($shop->banner) }}" class="hero-media" alt="{{ $shop->name }}">
                    @else
                        <div class="hero-fallback"><i class="ti ti-building-store" aria-hidden="true"></i></div>
                    @endif

                    <div class="hero-content">
                        <div class="identity">
                            @if($shop->logo)
                                <img src="{{ asset($shop->logo) }}" class="logo" alt="{{ $shop->name }}">
                            @else
                                <div class="logo"><i class="ti ti-building-store" aria-hidden="true"></i></div>
                            @endif

                            <div>
                                <div class="kicker">ملف المتجر</div>
                                <h1>{{ $shop->name }}</h1>
                                <div class="slug">{{ $shop->slug }}</div>
                                <div class="public-link-box">
                                    <input type="text" id="publicShopUrl" value="{{ $publicShopUrl }}" readonly>
                                    <button type="button" class="copy-link-btn" id="copyPublicShopUrl" data-copy-target="publicShopUrl">
                                        <i class="ti ti-copy" aria-hidden="true"></i>
                                        نسخ الرابط
                                    </button>
                                </div>
                                <div style="margin-top:12px">
                                    <span class="tag {{ $shop->is_active ? 'tag-g' : 'tag-r' }}">
                                        <i class="ti {{ $shop->is_active ? 'ti-circle-check' : 'ti-circle-x' }}" aria-hidden="true"></i>
                                        {{ $shop->is_active ? 'نشط' : 'غير نشط' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="actions">
                            @if($shop->catalog_type === 'restaurant')
                            <a href="{{ route('restaurant.dashboard', $shop) }}" class="btn btn-primary">
                                <i class="ti ti-tools-kitchen-2" aria-hidden="true"></i>
                                إدارة المطعم والطاولات
                            </a>
                            @endif
                            @if($shop->catalog_type === 'real_estate')
                            <a href="{{ route('real-estate.dashboard', $shop) }}" class="btn btn-primary">
                                <i class="ti ti-building-community" aria-hidden="true"></i>
                                إدارة خدمات البناء المتنقل
                            </a>
                            @endif
                            <a href="{{ $publicShopUrl }}" target="_blank" rel="noopener" class="btn btn-primary">
                                <i class="ti ti-external-link" aria-hidden="true"></i>
                                فتح رابط المتجر
                            </a>
                            @if($canCreateCategories)
                            <a href="{{ route('categories.create', ['shop_id' => $shop->id]) }}" class="btn btn-primary">
                                <i class="ti ti-category-plus" aria-hidden="true"></i>
                                {{ $catalogTerms['add_category'] }}
                            </a>
                            @endif
                            @if($canCreateAgents)
                            <a href="{{ route('agents.create', ['shop_id' => $shop->id]) }}" class="btn">
                                <i class="ti ti-user-star" aria-hidden="true"></i>
                                إضافة وكيل
                            </a>
                            @endif
                            @if($canCreateDistributors)
                            <a href="{{ route('distributors.create', ['shop_id' => $shop->id]) }}" class="btn">
                                <i class="ti ti-truck-delivery" aria-hidden="true"></i>
                                إضافة موزع
                            </a>
                            @endif
                            @if($canCreateAds)
                            <a href="{{ route('ads.create', ['shop_id' => $shop->id]) }}" class="btn">
                                <i class="ti ti-speakerphone" aria-hidden="true"></i>
                                إضافة إعلان
                            </a>
                            @endif
                            <a href="{{ route('shops.edit', $shop) }}" class="btn">
                                <i class="ti ti-edit" aria-hidden="true"></i>
                                تعديل المتجر
                            </a>
                            <a href="{{ route('shops') }}" class="btn">
                                <i class="ti ti-arrow-right" aria-hidden="true"></i>
                                رجوع
                            </a>
                        </div>
                    </div>
                </section>

                <section class="stats-grid" aria-label="إحصائيات المتجر">
                    <article class="stat-card" style="--stat-color: var(--primary)">
                        <div class="stat-label">{{ $catalogTerms['item_plural'] }}</div>
                        <div class="stat-value">{{ $shop->products_count ?? 0 }}</div>
                        <i class="ti ti-package stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--stat-color: var(--accent)">
                        <div class="stat-label">{{ $catalogTerms['category_plural'] }}</div>
                        <div class="stat-value">{{ $shop->categories_count ?? 0 }}</div>
                        <i class="ti ti-category stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--stat-color: var(--green)">
                        <div class="stat-label">حالة المتجر</div>
                        <div class="stat-value" style="font-size:24px">{{ $shop->is_active ? 'نشط' : 'متوقف' }}</div>
                        <i class="ti ti-circle-check stat-icon" aria-hidden="true"></i>
                    </article>

                    <article class="stat-card" style="--stat-color: var(--yellow)">
                        <div class="stat-label">الدوام</div>
                        <div class="stat-value" style="font-size:22px">{{ $shop->open_time ?? '-' }}</div>
                        <i class="ti ti-clock stat-icon" aria-hidden="true"></i>
                    </article>
                </section>

                <section class="shop-qr-card" aria-labelledby="shopQrTitle">
                    <a href="{{ $publicShopUrl }}" target="_blank" rel="noopener">
                        <img src="{{ $shopQrCodeDataUri }}"
                            class="shop-qr-image"
                            alt="QR Code لمتجر {{ $shop->name }}">
                    </a>
                    <div class="shop-qr-content">
                        <h2 class="shop-qr-title" id="shopQrTitle">مشاركة متجر {{ $shop->name }}</h2>
                        <p class="shop-qr-hint">امسح الكود بكاميرا الهاتف لفتح صفحة المتجر مباشرة، أو نزّله للطباعة والمشاركة.</p>
                        <div class="shop-qr-url">{{ $publicShopUrl }}</div>
                        <div class="shop-qr-actions">
                            <button type="button"
                                class="shop-qr-action secondary copy-link-btn"
                                data-copy-target="publicShopUrl">
                                <i class="ti ti-copy" aria-hidden="true"></i>
                                نسخ الرابط
                            </button>
                            <a href="{{ $publicShopUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="shop-qr-action secondary">
                                <i class="ti ti-external-link" aria-hidden="true"></i>
                                فتح المتجر
                            </a>
                            <a href="{{ $shopQrCodeDataUri }}"
                                download="{{ $shop->slug }}-qr.svg"
                                class="shop-qr-action">
                                <i class="ti ti-download" aria-hidden="true"></i>
                                تنزيل QR
                            </a>
                        </div>
                    </div>
                </section>

                <div class="layout">
                    <div>
                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-address-book" aria-hidden="true"></i>
                                    معلومات التواصل
                                </h2>
                            </div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="label">الهاتف</div>
                                    <div class="value">{{ $shop->phone ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">واتساب</div>
                                    <div class="value">{{ $shop->whatsapp ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">البريد الإلكتروني</div>
                                    <div class="value">{{ $shop->email ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">تاريخ الإضافة</div>
                                    <div class="value">{{ optional($shop->created_at)->format('Y-m-d H:i') ?? '-' }}</div>
                                </div>
                            </div>
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-credit-card-pay" aria-hidden="true"></i>
                                    معلومات الدفع
                                </h2>
                            </div>

                            @if($shop->payment_method || $shop->payment_provider || $shop->payment_account_holder || $shop->payment_account_number || $shop->payment_iban || $shop->payment_wallet_number || $shop->payment_notes)
                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="label">طريقة الدفع</div>
                                        <div class="value">{{ $paymentMethodLabels[$shop->payment_method] ?? $shop->payment_method ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">البنك أو مزود الدفع</div>
                                        <div class="value">{{ $shop->payment_provider ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">اسم صاحب الحساب</div>
                                        <div class="value">{{ $shop->payment_account_holder ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">رقم الحساب</div>
                                        <div class="value" dir="ltr">{{ $shop->payment_account_number ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">IBAN</div>
                                        <div class="value" dir="ltr">{{ $shop->payment_iban ?? '-' }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="label">رقم المحفظة</div>
                                        <div class="value" dir="ltr">{{ $shop->payment_wallet_number ?? '-' }}</div>
                                    </div>
                                    <div class="info-item" style="grid-column:1 / -1">
                                        <div class="label">ملاحظات الدفع</div>
                                        <div class="value">{{ $shop->payment_notes ?? '-' }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="empty">لم يتم حفظ معلومات دفع لهذا المتجر بعد.</div>
                            @endif
                        </section>

                        {{-- Agents and distributors only exist for krembo; other shops never saw a use for these panels. --}}
                        @if($shop->catalog_type === 'krembo' || $shop->agents->isNotEmpty() || $shop->distributors->isNotEmpty())
                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-user-star" aria-hidden="true"></i>
                                    وكلاء المتجر
                                </h2>
                                @if($canCreateAgents)
                                <a href="{{ route('agents.create', ['shop_id' => $shop->id]) }}" class="btn">
                                    <i class="ti ti-plus" aria-hidden="true"></i>
                                    إضافة
                                </a>
                                @endif
                            </div>

                            @if($shop->agents->isNotEmpty())
                                <div class="relation-grid">
                                    @foreach($shop->agents as $agent)
                                        <a href="{{ route('agents.show', $agent) }}" class="relation-card">
                                            <i class="ti ti-user-star" aria-hidden="true"></i>
                                            <span class="relation-copy">
                                                <span class="relation-name">{{ $agent->name }}</span>
                                                <span class="relation-sub">{{ $agent->phone ?? $agent->whatsapp ?? 'لا يوجد رقم تواصل' }}</span>
                                            </span>
                                            <span class="tag {{ $agent->is_active ? 'tag-g' : 'tag-r' }}">
                                                {{ $agent->is_active ? 'نشط' : 'متوقف' }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا يوجد وكلاء لهذا المتجر بعد.</div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-truck-delivery" aria-hidden="true"></i>
                                    موزعو المتجر
                                </h2>
                                @if($canCreateDistributors)
                                <a href="{{ route('distributors.create', ['shop_id' => $shop->id]) }}" class="btn">
                                    <i class="ti ti-plus" aria-hidden="true"></i>
                                    إضافة
                                </a>
                                @endif
                            </div>

                            @if($shop->distributors->isNotEmpty())
                                <div class="relation-grid">
                                    @foreach($shop->distributors as $distributor)
                                        <a href="{{ route('distributors.show', $distributor) }}" class="relation-card">
                                            <i class="ti ti-truck-delivery" aria-hidden="true"></i>
                                            <span class="relation-copy">
                                                <span class="relation-name">{{ $distributor->name }}</span>
                                                <span class="relation-sub">{{ $distributor->phone ?? $distributor->whatsapp ?? 'لا يوجد رقم تواصل' }}</span>
                                            </span>
                                            <span class="tag {{ $distributor->is_active ? 'tag-g' : 'tag-r' }}">
                                                {{ $distributor->is_active ? 'نشط' : 'متوقف' }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا يوجد موزعون لهذا المتجر بعد.</div>
                            @endif
                        </section>
                        @endif

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-speakerphone" aria-hidden="true"></i>
                                    إعلانات المتجر
                                </h2>
                                @if($canCreateAds)
                                <a href="{{ route('ads.create', ['shop_id' => $shop->id]) }}" class="btn">
                                    <i class="ti ti-plus" aria-hidden="true"></i>
                                    إضافة
                                </a>
                                @endif
                            </div>

                            @if($shop->advertisements->isNotEmpty())
                                <div class="relation-grid">
                                    @foreach($shop->advertisements as $ad)
                                        <a href="{{ route('ads.show', $ad) }}" class="relation-card">
                                            <i class="ti ti-speakerphone" aria-hidden="true"></i>
                                            <span class="relation-copy">
                                                <span class="relation-name">{{ $ad->title }}</span>
                                                <span class="relation-sub">{{ $ad->type }} - {{ $ad->duration }} ثانية</span>
                                            </span>
                                            <span class="tag {{ $ad->is_active ? 'tag-g' : 'tag-r' }}">
                                                {{ $ad->is_active ? 'نشط' : 'متوقف' }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا توجد إعلانات لهذا المتجر بعد.</div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-map-pin" aria-hidden="true"></i>
                                    الموقع والدوام
                                </h2>
                            </div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="label">المدينة</div>
                                    <div class="value">{{ $shop->city ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">الدولة</div>
                                    <div class="value">{{ $shop->country ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">العنوان</div>
                                    <div class="value">{{ $shop->address ?? '-' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">ساعات العمل</div>
                                    <div class="value">{{ $shop->open_time ?? '-' }} - {{ $shop->close_time ?? '-' }}</div>
                                </div>
                            </div>

                            <div style="height:12px"></div>

                            @if($shop->latitude && $shop->longitude)
                                <a class="map-box" href="https://www.google.com/maps?q={{ $shop->latitude }},{{ $shop->longitude }}" target="_blank" rel="noopener">
                                    <span>
                                        <i class="ti ti-map-search" aria-hidden="true"></i>
                                        {{ $shop->latitude }}, {{ $shop->longitude }}
                                        <br>
                                        فتح الموقع على الخريطة
                                    </span>
                                </a>
                            @else
                                <div class="map-box">
                                    <span>
                                        <i class="ti ti-map-off" aria-hidden="true"></i>
                                        لم يتم تحديد موقع المتجر بعد
                                    </span>
                                </div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-align-right" aria-hidden="true"></i>
                                    وصف المتجر
                                </h2>
                            </div>

                            <div class="text-block">{{ $shop->description ?: 'لا يوجد وصف لهذا المتجر.' }}</div>
                        </section>
                    </div>

                    <aside>
                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-category" aria-hidden="true"></i>
                                    أقسام المتجر
                                </h2>
                                @if($canCreateCategories)
                                <a href="{{ route('categories.create', ['shop_id' => $shop->id]) }}" class="btn">
                                    <i class="ti ti-plus" aria-hidden="true"></i>
                                    إضافة
                                </a>
                                @endif
                            </div>

                            @if($shop->categories->isNotEmpty())
                                <div class="category-grid">
                                    @foreach($shop->categories as $category)
                                        <div class="category-item">
                                            <a href="{{ route('categories.show', $category) }}" class="category-card">
                                                <i class="ti ti-category" aria-hidden="true"></i>
                                                <span class="category-copy">
                                                    <span class="category-name">{{ $category->name }}</span>
                                                    <span class="category-sub">{{ $category->products_count }} منتج</span>
                                                </span>
                                                <span class="tag {{ $category->is_active ? 'tag-g' : 'tag-r' }}">
                                                    {{ $category->is_active ? 'نشط' : 'متوقف' }}
                                                </span>
                                            </a>
                                            @if($canEditCategories || $canDeleteCategories)
                                                <div class="category-actions">
                                                    @if($canEditCategories)
                                                        <a href="{{ route('categories.edit', $category) }}" class="mini-action">
                                                            <i class="ti ti-edit" aria-hidden="true"></i>
                                                            تعديل
                                                        </a>
                                                    @endif
                                                    @if($canDeleteCategories)
                                                        <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذه الفئة؟')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="mini-action danger">
                                                                <i class="ti ti-trash" aria-hidden="true"></i>
                                                                حذف
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا توجد أقسام لهذا المتجر بعد.</div>
                            @endif
                        </section>

                        <section class="panel">
                            <div class="panel-head">
                                <h2 class="panel-title">
                                    <i class="ti ti-share" aria-hidden="true"></i>
                                    روابط التواصل
                                </h2>
                            </div>

                            @if($socialLinks->isNotEmpty())
                                <div class="social-grid">
                                    @foreach($socialLinks as $link)
                                        <a class="social-link" href="{{ $link['url'] }}" target="_blank" rel="noopener">
                                            <i class="ti {{ $link['icon'] }}" aria-hidden="true"></i>
                                            <span>{{ $link['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty">لا توجد روابط تواصل محفوظة.</div>
                            @endif
                        </section>
                    </aside>
                </div>
            </div>
        </main>
    </div>
    @viewScript('admin.shop.shops_list')
</body>

</html>
