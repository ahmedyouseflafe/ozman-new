<!DOCTYPE html>
<html lang="ar" dir="rtl">

@php
    $catalogTerms = $product->shop?->catalogTerms() ?? config('catalog_terminology.general');
    $itemLabel = $catalogTerms['item_singular'];
    $categoryLabel = $catalogTerms['category_singular'];
@endphp

<head>
    <title>{{ $product->name }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.products.products_show')
</head>

<body>
    @php
        $currentUser = auth()->user();
        $currentUserAgentIds = $currentUser?->isAgent()
            ? \App\Models\Agent::query()
                ->where(function ($query) use ($currentUser) {
                    $query->where('user_id', $currentUser->id);

                    if ($currentUser->email) {
                        $query->orWhere('email', $currentUser->email);
                    }
                })
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all()
            : [];
        $canManageProducts = $currentUser?->canAccessRouteName('products.edit')
            && (! $currentUser?->isAgent() || in_array((int) $product->agent_id, $currentUserAgentIds, true));
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض ' . $itemLabel])
            <div class="content">
                <header class="page-head">
                    <div>
                        <div class="eyebrow">تفاصيل {{ $itemLabel }}</div>
                        <h1>{{ $product->name }}</h1>
                        <p>{{ $product->shop?->name ?? 'متجر غير محدد' }} / {{ $product->category?->name ?? ($categoryLabel.' غير محدد') }}</p>
                    </div>
                    <div class="actions" style="margin-top:0">
                        @if($canManageProducts)
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary"><i class="ti ti-edit"></i>تعديل {{ $itemLabel }}</a>
                        @endif
                        <a href="{{ route('products') }}" class="btn"><i class="ti ti-arrow-right"></i>رجوع للمنتجات</a>
                    </div>
                </header>

                <div class="stats">
                    <div class="stat">
                        <i class="ti ti-cash"></i>
                        <div>
                            <div class="stat-label">السعر</div>
                            <div class="stat-value">{{ number_format((float) $product->price, 2) }} شيكل</div>
                        </div>
                    </div>
                    <div class="stat">
                        <i class="ti ti-discount-2"></i>
                        <div>
                            <div class="stat-label">سعر الخصم</div>
                            <div class="stat-value">{{ $product->discount_price ? number_format((float) $product->discount_price, 2) . ' شيكل' : '-' }}</div>
                        </div>
                    </div>
                    <div class="stat">
                        <i class="ti ti-building-store"></i>
                        <div>
                            <div class="stat-label">سعر التاجر</div>
                            <div class="stat-value">{{ $product->merchant_price ? number_format((float) $product->merchant_price, 2) . ' شيكل' : '-' }}</div>
                        </div>
                    </div>
                    <div class="stat">
                        <i class="ti ti-package"></i>
                        <div>
                            <div class="stat-label">سعر العبوة</div>
                            <div class="stat-value">{{ $product->package_price ? number_format((float) $product->package_price, 2) . ' شيكل' : '-' }}</div>
                        </div>
                    </div>
                    <div class="stat">
                        <i class="ti ti-stack-3"></i>
                        <div>
                            <div class="stat-label">سعر المشطاح</div>
                            <div class="stat-value">{{ $product->pallet_price ? number_format((float) $product->pallet_price, 2) . ' شيكل' : '-' }}</div>
                        </div>
                    </div>
                    <div class="stat">
                        <i class="ti ti-box"></i>
                        <div>
                            <div class="stat-label">سعر الكرتونة</div>
                            <div class="stat-value">{{ $product->carton_price ? number_format((float) $product->carton_price, 2) . ' شيكل' : '-' }}</div>
                        </div>
                    </div>
                    <div class="stat">
                        <i class="ti ti-stack-2"></i>
                        <div>
                            <div class="stat-label">الكمية</div>
                            <div class="stat-value">{{ $product->quantity }}</div>
                        </div>
                    </div>
                    <div class="stat">
                        <i class="ti ti-star"></i>
                        <div>
                            <div class="stat-label">التقييم</div>
                            <div class="stat-value">{{ $product->rating ?: 0 }}</div>
                        </div>
                    </div>
                </div>

                @php
                    $catalogDefinition = $product->shop?->catalogDefinition() ?? [];
                    $catalogFields = $catalogDefinition['fields'] ?? [];
                @endphp
                @if(count($product->catalog_attributes ?? []) || $product->variants->isNotEmpty())
                    <section class="panel" style="padding:22px;margin-bottom:22px">
                        <div class="panel-head">
                            <h2 style="margin:0;color:#00e5ff">
                                <i class="ti {{ $catalogDefinition['icon'] ?? 'ti-adjustments' }}"></i>
                                تفاصيل {{ $catalogDefinition['label'] ?? $itemLabel }}
                            </h2>
                        </div>
                        @if(count($product->catalog_attributes ?? []))
                            <div class="info-grid">
                                @foreach($product->catalog_attributes as $key => $value)
                                    <div class="info-item">
                                        <div class="info-label">{{ data_get($catalogFields, $key . '.label', $key) }}</div>
                                        <div class="info-value">
                                            {{ is_array($value) ? implode('، ', $value) : (is_bool($value) ? ($value ? 'نعم' : 'لا') : $value) }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($product->variants->isNotEmpty())
                            <div style="overflow-x:auto;margin-top:18px">
                                <table style="width:100%;border-collapse:collapse">
                                    <thead><tr><th style="padding:10px;text-align:right;color:#00e5ff">المقاس</th><th style="padding:10px;text-align:right;color:#00e5ff">اللون</th><th style="padding:10px;text-align:right;color:#00e5ff">SKU</th><th style="padding:10px;text-align:right;color:#00e5ff">السعر الخاص</th><th style="padding:10px;text-align:right;color:#00e5ff">الكمية</th></tr></thead>
                                    <tbody>
                                        @foreach($product->variants as $variant)
                                            <tr style="border-top:1px solid rgba(255,255,255,.08)">
                                                <td style="padding:10px">{{ $variant->size ?: '-' }}</td>
                                                <td style="padding:10px">{{ $variant->color ?: '-' }}</td>
                                                <td style="padding:10px" dir="ltr">{{ $variant->sku ?: '-' }}</td>
                                                <td style="padding:10px">{{ $variant->price !== null ? number_format((float) $variant->price, 2) : '-' }}</td>
                                                <td style="padding:10px">{{ $variant->quantity }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                @endif

                <div class="layout">
                    <aside class="panel media-panel">
                        <div class="main-media">
                            @if($product->main_image)
                                <img src="{{ asset($product->main_image) }}" alt="{{ $product->name }}">
                            @else
                                <i class="ti ti-package"></i>
                            @endif
                        </div>

                        @if($product->video)
                            <div class="video-box">
                                <video src="{{ asset($product->video) }}" controls></video>
                            </div>
                        @endif

                        <div class="badges">
                            <span class="tag {{ $product->is_active ? 'tag-g' : 'tag-r' }}">{{ $product->is_active ? 'نشط' : 'غير نشط' }}</span>
                            <span class="tag {{ $product->is_featured ? 'tag-y' : 'tag-r' }}">{{ $product->is_featured ? 'مميز' : 'غير مميز' }}</span>
                            @if($product->quantity > 0)<span class="tag tag-c">الكمية: {{ $product->quantity }}</span>@endif
                        </div>
                    </aside>

                    <section class="panel">
                        <div class="section">
                            <div class="section-title"><i class="ti ti-info-circle"></i>معلومات {{ $itemLabel }}</div>
                            <div class="info-grid">
                                <div class="item"><div class="label">المتجر</div><div class="value">{{ $product->shop?->name ?? '-' }}</div></div>
                                <div class="item"><div class="label">{{ $categoryLabel }}</div><div class="value">{{ $product->category?->name ?? '-' }}</div></div>
                                <div class="item"><div class="label">الرابط المختصر</div><div class="value">{{ $product->slug }}</div></div>
                                <div class="item"><div class="label">SKU</div><div class="value">{{ $product->sku ?? '-' }}</div></div>
                                <div class="item"><div class="label">Barcode</div><div class="value">{{ $product->barcode ?? '-' }}</div></div>
                                <div class="item"><div class="label">تاريخ الإضافة</div><div class="value">{{ optional($product->created_at)->format('Y-m-d H:i') ?? '-' }}</div></div>
                            </div>
                        </div>

                        <div class="section">
                            <div class="section-title"><i class="ti ti-align-right"></i>الوصف</div>
                            @if($product->description)
                                <div class="description">{{ $product->description }}</div>
                            @else
                                <div class="empty-state">لا يوجد وصف لهذا العنصر.</div>
                            @endif
                        </div>

                        <div class="section">
                            <div class="section-title"><i class="ti ti-photo"></i>الصور الإضافية</div>
                            @if($product->images->isNotEmpty())
                                <div class="gallery">
                                    @foreach($product->images as $image)
                                        <a href="{{ asset($image->image) }}" target="_blank" aria-label="عرض الصورة">
                                            <img src="{{ asset($image->image) }}" alt="{{ $product->name }}">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">لا توجد صور إضافية.</div>
                            @endif
                        </div>

                        <div class="section">
                            <div class="section-title"><i class="ti ti-ad"></i>الحملات والوسائط</div>
                            @if($product->campaigns->isNotEmpty())
                                <div class="campaigns">
                                    @foreach($product->campaigns as $campaign)
                                        <div class="campaign">
                                            <div class="campaign-head">
                                                <h3>{{ $campaign->title }}</h3>
                                                <span class="tag tag-c">{{ $campaign->media ? ($campaign->type === 'image' ? 'صورة' : 'فيديو') : 'عرض' }}</span>
                                            </div>
                                            @if($campaign->offer_quantity || $campaign->offer_price || $campaign->offer_note || $campaign->starts_at || $campaign->ends_at)
                                                <div class="campaign-offer">
                                                    @if($campaign->offer_quantity && $campaign->offer_price)
                                                        <div><strong>{{ $campaign->offer_quantity }} بسعر {{ number_format((float) $campaign->offer_price, 2) }}</strong></div>
                                                    @endif
                                                    @if($campaign->offer_note)
                                                        <div>{{ $campaign->offer_note }}</div>
                                                    @endif
                                                    @if($campaign->starts_at || $campaign->ends_at)
                                                        <div>الفترة: {{ $campaign->starts_at?->format('Y-m-d') ?? 'بدون بداية' }} - {{ $campaign->ends_at?->format('Y-m-d') ?? 'بدون نهاية' }}</div>
                                                    @endif
                                                </div>
                                            @endif
                                            @if($campaign->media)
                                                @if($campaign->type === 'image')
                                                    <img src="{{ asset($campaign->media) }}" alt="{{ $campaign->title }}">
                                                @else
                                                    <video src="{{ asset($campaign->media) }}" controls></video>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">لا توجد حملات لهذا العنصر.</div>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
