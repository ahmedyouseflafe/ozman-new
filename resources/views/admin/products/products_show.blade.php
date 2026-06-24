<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>{{ $product->name }} - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #00e5ff;
            --accent: #7000ff;
            --green: #25d366;
            --yellow: #ffd60a;
            --danger: #ff3b30;
            --border: rgba(255, 255, 255, .1);
            --text: #fff;
            --muted: rgba(255, 255, 255, .66);
            --dim: rgba(255, 255, 255, .44);
            --panel: rgba(255, 255, 255, .055);
        }

        html, body {
            min-height: 100%;
            background:
                radial-gradient(circle at 12% 12%, rgba(112, 0, 255, .14), transparent 30%),
                radial-gradient(circle at 82% 10%, rgba(0, 229, 255, .14), transparent 34%),
                linear-gradient(180deg, #050505, #080a0d 65%, #050505);
            color: var(--text);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            direction: rtl;
        }

        .main { min-height: 100vh; margin-right: 245px; }
        .content { padding: 28px 34px 46px; max-width: 1240px; margin: 0 auto; }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 22px;
        }

        .eyebrow { color: var(--primary); font-size: 12px; font-weight: 900; margin-bottom: 6px; }
        h1 { font-size: clamp(26px, 3vw, 42px); font-weight: 900; color: var(--primary); text-shadow: 0 0 18px rgba(0, 229, 255, .42); line-height: 1.2; }
        .page-head p { color: var(--muted); font-size: 14px; margin-top: 8px; font-weight: 700; }

        .btn {
            border: 1px solid var(--border);
            min-height: 44px;
            padding: 0 18px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #fff;
            background: rgba(255, 255, 255, .055);
            font-family: inherit;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .btn:hover { transform: translateY(-2px); border-color: var(--primary); box-shadow: 0 0 18px rgba(0, 229, 255, .22); }
        .btn-primary { border: 0; color: #001014; background: linear-gradient(135deg, var(--primary), var(--accent)); box-shadow: 0 0 22px rgba(0, 229, 255, .34); }

        .layout { display: grid; grid-template-columns: minmax(320px, 420px) 1fr; gap: 22px; align-items: start; }

        .panel {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255, 255, 255, .075), rgba(255, 255, 255, .025));
            backdrop-filter: blur(16px);
            border-radius: 26px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .34);
            overflow: hidden;
        }

        .media-panel { padding: 18px; position: sticky; top: 90px; }
        .main-media {
            min-height: 420px;
            border-radius: 24px;
            border: 1px solid var(--border);
            background: radial-gradient(circle at center, rgba(0, 229, 255, .08), rgba(0, 0, 0, .76));
            overflow: hidden;
            display: grid;
            place-items: center;
            color: var(--primary);
            font-size: 70px;
        }

        .main-media img, .main-media video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .video-box { margin-top: 14px; }
        .video-box video { width: 100%; height: 220px; object-fit: cover; border-radius: 20px; border: 1px solid var(--border); background: #000; display: block; }

        .section { padding: 22px; }
        .section + .section { border-top: 1px solid rgba(255, 255, 255, .08); }
        .section-title { display: flex; align-items: center; gap: 10px; color: #fff; font-size: 18px; font-weight: 900; margin-bottom: 16px; }
        .section-title i { color: var(--primary); }

        .badges { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .tag { display: inline-flex; align-items: center; justify-content: center; min-height: 30px; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 900; border: 1px solid currentColor; }
        .tag-g { color: var(--green); background: rgba(37, 211, 102, .1); }
        .tag-r { color: var(--danger); background: rgba(255, 59, 48, .1); }
        .tag-y { color: var(--yellow); background: rgba(255, 214, 10, .1); }
        .tag-c { color: var(--primary); background: rgba(0, 229, 255, .09); }

        .stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; margin-bottom: 22px; }
        .stat {
            min-height: 118px;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: rgba(0, 0, 0, .22);
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat i { color: var(--primary); font-size: 26px; opacity: .72; }
        .stat-label { color: var(--dim); font-size: 12px; font-weight: 900; }
        .stat-value { color: #fff; font-size: 22px; font-weight: 900; overflow-wrap: anywhere; }

        .info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .item { border: 1px solid var(--border); border-radius: 18px; background: rgba(0, 0, 0, .18); padding: 15px; }
        .label { color: var(--dim); font-size: 12px; font-weight: 900; margin-bottom: 6px; }
        .value { font-size: 15px; font-weight: 900; overflow-wrap: anywhere; }

        .description {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: rgba(0, 0, 0, .18);
            padding: 16px;
            color: var(--muted);
            line-height: 1.9;
            font-weight: 700;
        }

        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; }
        .gallery a {
            display: block;
            aspect-ratio: 1;
            border-radius: 18px;
            border: 1px solid var(--border);
            overflow: hidden;
            background: #000;
        }
        .gallery img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .25s ease; }
        .gallery a:hover img { transform: scale(1.06); }

        .campaigns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .campaign { border: 1px solid var(--border); border-radius: 20px; background: rgba(0, 0, 0, .2); padding: 14px; }
        .campaign-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
        .campaign h3 { color: var(--primary); font-size: 14px; font-weight: 900; overflow-wrap: anywhere; }
        .campaign img, .campaign video { width: 100%; height: 190px; object-fit: cover; border-radius: 16px; border: 1px solid var(--border); background: #000; display: block; }
        .campaign-offer { margin-bottom: 12px; color: rgba(255,255,255,.76); font-size: 12px; font-weight: 800; line-height: 1.8; }
        .campaign-offer strong { color: var(--primary); font-size: 15px; }

        .empty-state {
            border: 1px dashed rgba(255, 255, 255, .16);
            border-radius: 18px;
            padding: 24px;
            color: var(--dim);
            text-align: center;
            font-weight: 800;
        }

        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }

        @media(max-width: 1050px) {
            .layout { grid-template-columns: 1fr; }
            .media-panel { position: static; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media(max-width: 900px) {
            .main { margin-right: 0; }
            .content { padding: 20px 16px 34px; }
            .page-head, .actions { flex-direction: column; align-items: stretch; }
            .btn { width: 100%; }
        }

        @media(max-width: 640px) {
            .stats, .info-grid, .campaigns { grid-template-columns: 1fr; }
            .main-media { min-height: 300px; }
        }
    </style>
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
        $canManageProducts = ! $currentUser?->isDistributor()
            && (! $currentUser?->isAgent() || in_array((int) $product->agent_id, $currentUserAgentIds, true));
    @endphp

    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'عرض المنتج'])
            <div class="content">
                <header class="page-head">
                    <div>
                        <div class="eyebrow">تفاصيل المنتج</div>
                        <h1>{{ $product->name }}</h1>
                        <p>{{ $product->shop?->name ?? 'متجر غير محدد' }} / {{ $product->category?->name ?? 'فئة غير محددة' }}</p>
                    </div>
                    <div class="actions" style="margin-top:0">
                        @if($canManageProducts)
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary"><i class="ti ti-edit"></i>تعديل المنتج</a>
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
                            <span class="tag {{ $product->quantity > 0 ? 'tag-c' : 'tag-r' }}">{{ $product->quantity > 0 ? 'متوفر' : 'نفد المخزون' }}</span>
                        </div>
                    </aside>

                    <section class="panel">
                        <div class="section">
                            <div class="section-title"><i class="ti ti-info-circle"></i>معلومات المنتج</div>
                            <div class="info-grid">
                                <div class="item"><div class="label">المتجر</div><div class="value">{{ $product->shop?->name ?? '-' }}</div></div>
                                <div class="item"><div class="label">الفئة</div><div class="value">{{ $product->category?->name ?? '-' }}</div></div>
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
                                <div class="empty-state">لا يوجد وصف لهذا المنتج.</div>
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
                                <div class="empty-state">لا توجد حملات لهذا المنتج.</div>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
