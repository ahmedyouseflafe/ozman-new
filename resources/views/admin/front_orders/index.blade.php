<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>طلبات الواجهة - Ozman</title>
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
            --red: #ff4d5e;
            --bg: #050505;
            --border: rgba(255, 255, 255, .1);
            --text: #fff;
            --muted: rgba(255, 255, 255, .66);
            --dim: rgba(255, 255, 255, .44);
        }
        html, body {
            min-height: 100%;
            background:
                radial-gradient(circle at 12% 16%, rgba(112, 0, 255, .14), transparent 30%),
                radial-gradient(circle at 80% 8%, rgba(0, 229, 255, .16), transparent 34%),
                linear-gradient(180deg, #030303 0%, #050505 54%, #090313 100%);
            color: var(--text);
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            direction: rtl;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, .026) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .026) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, black, transparent 82%);
        }
        .main { min-height: 100vh; margin-right: 245px; position: relative; z-index: 1; }
        .content { padding: 28px 34px 46px; }
        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 22px;
        }
        .page-kicker {
            color: var(--primary);
            font-size: 13px;
            font-weight: 900;
            text-shadow: 0 0 12px rgba(0, 229, 255, .5);
            margin-bottom: 6px;
        }
        h1 {
            font-size: 34px;
            line-height: 1.1;
            font-weight: 900;
            color: var(--primary);
            text-shadow: 0 0 20px rgba(0, 229, 255, .42);
        }
        .page-head p {
            margin-top: 8px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }
        .stat-card, .panel, .profile-panel {
            border: 1px solid var(--border);
            background: linear-gradient(145deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025));
            backdrop-filter: blur(16px);
            border-radius: 24px;
            box-shadow: 0 18px 48px rgba(0, 0, 0, .34);
        }
        .profile-panel {
            display: grid;
            grid-template-columns: 104px minmax(0, 1fr);
            gap: 18px;
            align-items: center;
            padding: 20px;
            margin-bottom: 22px;
        }
        .profile-photo {
            width: 104px;
            height: 104px;
            border-radius: 22px;
            object-fit: cover;
            border: 2px solid rgba(0, 229, 255, .72);
            box-shadow: 0 0 24px rgba(0, 229, 255, .38);
            background: rgba(0, 0, 0, .32);
        }
        .profile-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .profile-title {
            color: var(--primary);
            font-size: 26px;
            font-weight: 900;
            line-height: 1.2;
            text-shadow: 0 0 18px rgba(0, 229, 255, .38);
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .profile-detail {
            min-height: 68px;
            padding: 10px 12px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 16px;
            background: rgba(0, 0, 0, .18);
        }
        .profile-detail span {
            display: block;
            color: var(--dim);
            font-size: 11px;
            font-weight: 900;
            margin-bottom: 5px;
        }
        .profile-detail strong {
            display: block;
            color: #fff;
            font-size: 13px;
            font-weight: 900;
            overflow-wrap: anywhere;
        }
        .stat-card {
            min-height: 118px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .stat-label { color: rgba(255, 255, 255, .72); font-size: 13px; font-weight: 900; }
        .stat-val {
            margin-top: 14px;
            color: var(--card-color, var(--primary));
            font-size: 34px;
            line-height: 1;
            font-weight: 900;
            text-shadow: 0 0 18px rgba(0, 229, 255, .45);
        }
        .stat-icon {
            position: absolute;
            left: 18px;
            bottom: 16px;
            font-size: 42px;
            color: var(--card-color, var(--primary));
            opacity: .18;
        }
        .panel { padding: 24px; }
        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 18px;
        }
        .panel-title {
            color: #fff;
            font-size: 19px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .panel-title i { color: var(--primary); filter: drop-shadow(0 0 10px rgba(0, 229, 255, .55)); }
        .filter-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .search-inp, .filter-select, .filter-btn {
            height: 44px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .055);
            border-radius: 999px;
            color: #fff;
            outline: none;
            font-family: inherit;
            font-size: 13px;
            font-weight: 800;
        }
        .search-inp { width: min(290px, 100%); padding: 0 16px; }
        .filter-select { min-width: 150px; padding: 0 16px; cursor: pointer; }
        .filter-select option { color: #111; background: #fff; }
        .filter-btn {
            border-color: rgba(0, 229, 255, .4);
            background: rgba(0, 229, 255, .12);
            color: var(--primary);
            padding: 0 18px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .qr-scan-btn {
            border-color: rgba(37, 211, 102, .42);
            background: rgba(37, 211, 102, .12);
            color: var(--green);
        }
        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 22px;
            background: rgba(0, 0, 0, .22);
        }
        table { width: 100%; min-width: 1360px; border-collapse: collapse; font-size: 13px; }
        th, td {
            padding: 15px 16px;
            text-align: right;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
            vertical-align: top;
        }
        th {
            color: var(--primary);
            font-size: 12px;
            font-weight: 900;
            text-shadow: 0 0 9px rgba(0, 229, 255, .35);
        }
        tr:last-child td { border-bottom: 0; }
        .muted { color: var(--muted); font-weight: 700; }
        .dim { color: var(--dim); font-size: 12px; font-weight: 700; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 30px;
            padding: 5px 11px;
            border-radius: 999px;
            border: 1px solid rgba(0, 229, 255, .26);
            background: rgba(0, 229, 255, .09);
            color: var(--primary);
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }
        .badge.green { border-color: rgba(37, 211, 102, .28); background: rgba(37, 211, 102, .1); color: var(--green); }
        .badge.yellow { border-color: rgba(255, 214, 10, .28); background: rgba(255, 214, 10, .1); color: var(--yellow); }
        .status-form {
            display: grid;
            gap: 7px;
            min-width: 150px;
        }
        .status-select {
            width: 100%;
            min-height: 38px;
            border: 1px solid rgba(0, 229, 255, .22);
            border-radius: 13px;
            background: rgba(255, 255, 255, .055);
            color: #fff;
            padding: 0 10px;
            font-family: inherit;
            font-weight: 900;
            outline: none;
            cursor: pointer;
        }
        .status-select option { background: #111; color: #fff; }
        .status-save {
            min-height: 34px;
            border-radius: 12px;
            border: 1px solid rgba(37, 211, 102, .32);
            background: rgba(37, 211, 102, .1);
            color: var(--green);
            font-family: inherit;
            font-weight: 900;
            cursor: pointer;
        }
        .status-save:disabled {
            opacity: .62;
            cursor: wait;
        }
        .gift-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 170px;
        }
        .gift-img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(0, 229, 255, .55);
            box-shadow: 0 0 14px rgba(0, 229, 255, .35);
            flex-shrink: 0;
        }
        .items-list {
            display: grid;
            gap: 5px;
            min-width: 210px;
        }
        .item-line {
            color: rgba(255, 255, 255, .84);
            font-weight: 800;
        }
        .map-link {
            color: var(--primary);
            font-weight: 900;
            text-decoration: none;
        }
        .order-qr-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            color: var(--green);
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
        }
        .qr-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(0, 0, 0, .72);
            backdrop-filter: blur(10px);
        }
        .qr-modal.show { display: flex; }
        .qr-card {
            width: min(520px, 100%);
            border: 1px solid rgba(0, 229, 255, .2);
            border-radius: 24px;
            background: linear-gradient(145deg, rgba(18,18,24,.96), rgba(6,6,10,.96));
            box-shadow: 0 24px 70px rgba(0, 0, 0, .48);
            padding: 20px;
        }
        .qr-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .qr-card-title {
            color: var(--primary);
            font-size: 20px;
            font-weight: 900;
        }
        .qr-close {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: rgba(255,255,255,.08);
            color: #fff;
            cursor: pointer;
            font-size: 20px;
        }
        .qr-video-wrap {
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 18px;
            background: #000;
            aspect-ratio: 1 / 1;
        }
        .qr-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        .qr-help {
            margin-top: 12px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }
        .qr-manual {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            margin-top: 14px;
        }
        .qr-manual input {
            min-width: 0;
            height: 42px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: rgba(255,255,255,.055);
            color: #fff;
            padding: 0 14px;
            font: inherit;
            font-weight: 800;
        }
        .empty {
            padding: 34px;
            text-align: center;
            color: var(--muted);
            font-weight: 800;
        }
        .pagination-wrap { margin-top: 18px; }
        .pagination-wrap nav { display: flex; justify-content: center; }
        @media(max-width: 1100px) {
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .profile-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media(max-width: 900px) {
            .main { margin-right: 0; }
            .content { padding: 16px 12px 26px; }
            .page-head { margin-bottom: 14px; }
            h1 { font-size: 27px; }
            .page-head p { font-size: 12px; line-height: 1.7; }
            .page-head, .panel-head { align-items: flex-start; flex-direction: column; }
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 14px;
            }
            .stat-card {
                min-height: 86px;
                padding: 13px;
                border-radius: 18px;
            }
            .stat-label { font-size: 11px; }
            .stat-val { margin-top: 8px; font-size: 26px; }
            .stat-icon { font-size: 30px; left: 12px; bottom: 10px; }
            .profile-panel {
                grid-template-columns: 70px minmax(0, 1fr);
                gap: 12px;
                padding: 12px;
                margin-bottom: 14px;
                border-radius: 18px;
            }
            .profile-photo {
                width: 70px;
                height: 70px;
                border-radius: 16px;
            }
            .profile-head {
                margin-bottom: 9px;
                gap: 8px;
            }
            .profile-title {
                font-size: 18px;
            }
            .profile-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 7px;
            }
            .profile-detail {
                min-height: auto;
                padding: 7px 9px;
                border-radius: 12px;
            }
            .profile-detail:nth-child(n + 5) {
                display: none;
            }
            .profile-detail span {
                font-size: 10px;
                margin-bottom: 3px;
            }
            .profile-detail strong {
                font-size: 11px;
            }
            .badge {
                min-height: 25px;
                padding: 4px 8px;
                font-size: 10px;
            }
            .panel {
                padding: 14px;
                border-radius: 18px;
            }
            .panel-head {
                gap: 10px;
                padding-bottom: 12px;
                margin-bottom: 12px;
            }
            .panel-title { font-size: 16px; }
            .filter-row {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .search-inp {
                grid-column: 1 / -1;
                width: 100%;
            }
            .search-inp, .filter-select, .filter-btn {
                height: 40px;
                font-size: 12px;
            }
            .status-form { min-width: 132px; }
            table {
                min-width: 1160px;
                font-size: 12px;
            }
            th, td {
                padding: 12px;
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'طلبات الواجهة'])
            <div class="content">
                <div class="page-head">
                    <div>
                        <div class="page-kicker">Ozman Orders</div>
                        <h1>طلبات الواجهة</h1>
                        <p>كل طلب من السلة أو الدفع الفوري مع الهدية التي ظهرت للعميل من عجلة الشراء.</p>
                    </div>
                </div>

                @if(($distributorProfiles ?? collect())->isNotEmpty())
                    @foreach($distributorProfiles as $distributorProfile)
                        <section class="profile-panel">
                            <img
                                class="profile-photo"
                                src="{{ $distributorProfile->image ? asset($distributorProfile->image) : asset('images/logo.jpg') }}"
                                alt="{{ $distributorProfile->name }}"
                            >
                            <div>
                                <div class="profile-head">
                                    <div>
                                        <div class="page-kicker">بيانات الموزع</div>
                                        <div class="profile-title">{{ $distributorProfile->name }}</div>
                                    </div>
                                    <span class="badge {{ $distributorProfile->is_active ? 'green' : 'yellow' }}">
                                        <i class="ti {{ $distributorProfile->is_active ? 'ti-circle-check' : 'ti-alert-circle' }}"></i>
                                        {{ $distributorProfile->is_active ? 'نشط' : 'غير نشط' }}
                                    </span>
                                </div>
                                <div class="profile-grid">
                                    <div class="profile-detail">
                                        <span>المتجر</span>
                                        <strong>{{ $distributorProfile->shop?->name ?? '-' }}</strong>
                                    </div>
                                    <div class="profile-detail">
                                        <span>الهاتف</span>
                                        <strong dir="ltr">{{ $distributorProfile->phone ?: '-' }}</strong>
                                    </div>
                                    <div class="profile-detail">
                                        <span>واتساب</span>
                                        <strong dir="ltr">{{ $distributorProfile->whatsapp ?: '-' }}</strong>
                                    </div>
                                    <div class="profile-detail">
                                        <span>البريد</span>
                                        <strong dir="ltr">{{ $distributorProfile->email ?: '-' }}</strong>
                                    </div>
                                    <div class="profile-detail">
                                        <span>العنوان</span>
                                        <strong>{{ $distributorProfile->address ?: '-' }}</strong>
                                    </div>
                                    <div class="profile-detail">
                                        <span>الإحداثيات</span>
                                        <strong dir="ltr">
                                            {{ $distributorProfile->latitude && $distributorProfile->longitude ? $distributorProfile->latitude . ', ' . $distributorProfile->longitude : '-' }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </section>
                    @endforeach
                @endif

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي الطلبات</div>
                        <div class="stat-val">{{ number_format($totalCount) }}</div>
                        <i class="ti ti-receipt-2 stat-icon"></i>
                    </div>
                    <div class="stat-card" style="--card-color: var(--green);">
                        <div class="stat-label">طلبات واتساب</div>
                        <div class="stat-val">{{ number_format($whatsappCount) }}</div>
                        <i class="ti ti-brand-whatsapp stat-icon"></i>
                    </div>
                    <div class="stat-card" style="--card-color: var(--yellow);">
                        <div class="stat-label">دفع فوري</div>
                        <div class="stat-val">{{ number_format($instantCount) }}</div>
                        <i class="ti ti-bolt stat-icon"></i>
                    </div>
                    <div class="stat-card" style="--card-color: var(--accent);">
                        <div class="stat-label">طلبات معها هدية</div>
                        <div class="stat-val">{{ number_format($rewardedCount) }}</div>
                        <i class="ti ti-gift stat-icon"></i>
                    </div>
                    <div class="stat-card" style="--card-color: var(--primary);">
                        <div class="stat-label">طلبات عبر مسوقين</div>
                        <div class="stat-val">{{ number_format($marketerCount) }}</div>
                        <i class="ti ti-speakerphone stat-icon"></i>
                    </div>
                </div>

                <section class="panel">
                    <div class="panel-head">
                        <div class="panel-title">
                            <i class="ti ti-list-details"></i>
                            سجل الطلبات
                        </div>
                        <form class="filter-row" method="GET" action="{{ route('front-orders.index') }}">
                            <input class="search-inp" type="search" name="search" value="{{ $search }}" placeholder="بحث بالاسم، الهاتف، رقم الطلب أو الهدية">
                            <select class="filter-select" name="channel">
                                <option value="">كل الطرق</option>
                                <option value="whatsapp" @selected($selectedChannel === 'whatsapp')>واتساب</option>
                                <option value="instant_payment" @selected($selectedChannel === 'instant_payment')>دفع فوري</option>
                            </select>
                            <select class="filter-select" name="status">
                                <option value="">كل الحالات</option>
                                @foreach($statusOptions as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" @selected($selectedStatus === $statusValue)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                            <button class="filter-btn" type="submit"><i class="ti ti-filter"></i>فلترة</button>
                            <button class="filter-btn qr-scan-btn" type="button" id="orderQrScanBtn" data-search-url="{{ route('front-orders.index') }}">
                                <i class="ti ti-qrcode"></i>مسح QR
                            </button>
                        </form>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>العميل</th>
                                    <th>المتجر</th>
                                    <th>المصدر التسويقي</th>
                                    <th>المنتجات</th>
                                    <th>المبلغ</th>
                                    <th>طريقة الطلب</th>
                                    <th>حالة الدفع</th>
                                    <th>حالة الطلب</th>
                                    <th>الهدية</th>
                                    <th>الموقع</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td>
                                            <strong>{{ $order->order_number }}</strong>
                                            <div class="dim">#{{ $order->id }}</div>
                                            <a class="order-qr-link" href="{{ route('front-orders.qr', $order) }}" target="_blank" rel="noopener">
                                                <i class="ti ti-qrcode"></i> QR الطلب
                                            </a>
                                        </td>
                                        <td>
                                            <strong>{{ $order->customer_name }}</strong>
                                            <div class="muted">{{ $order->customer_phone ?: '-' }}</div>
                                            <div class="dim">واتساب: {{ $order->customer_whatsapp ?: '-' }}</div>
                                        </td>
                                        <td>{{ $order->shop?->name ?? 'عام' }}</td>
                                        <td>
                                            @if($order->distributorMarketer)
                                                <span class="badge green">
                                                    <i class="ti ti-speakerphone"></i>
                                                    المسوق: {{ $order->distributorMarketer->name }}
                                                </span>
                                                <div class="dim">الموزع: {{ $order->distributor?->name ?? '-' }}</div>
                                            @elseif($order->distributor)
                                                <span class="badge">الموزع: {{ $order->distributor->name }}</span>
                                            @else
                                                <span class="dim">مباشر</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="items-list">
                                                @foreach(array_slice($order->items ?? [], 0, 4) as $item)
                                                    <div class="item-line">{{ $item['name'] ?? '-' }} × {{ $item['qty'] ?? 1 }}</div>
                                                @endforeach
                                                @if(count($order->items ?? []) > 4)
                                                    <div class="dim">+ {{ count($order->items ?? []) - 4 }} منتجات أخرى</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <strong>{{ number_format((float) $order->total, 2) }} شيكل</strong>
                                            <div class="dim">قبل الخصم: {{ number_format((float) $order->subtotal, 2) }}</div>
                                            <div class="dim">الخصم: {{ number_format((float) $order->discount, 2) }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $order->order_channel === 'whatsapp' ? 'green' : 'yellow' }}">
                                                <i class="ti {{ $order->order_channel === 'whatsapp' ? 'ti-brand-whatsapp' : 'ti-bolt' }}"></i>
                                                {{ $order->channelLabel() }}
                                            </span>
                                            @if($order->payment_method)
                                                <div class="dim">{{ $order->payment_method }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge">{{ $order->payment_status }}</span>
                                        </td>
                                        <td>
                                            <form class="status-form" method="POST" action="{{ route('front-orders.status', $order) }}" data-status-form>
                                                @csrf
                                                @method('PATCH')
                                                <span class="badge {{ $order->statusClass() }}" data-status-badge>{{ $order->statusLabel() }}</span>
                                                <select class="status-select" name="status">
                                                    @foreach($statusOptions as $statusValue => $statusLabel)
                                                        <option value="{{ $statusValue }}" @selected($order->status === $statusValue)>{{ $statusLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="status-save" type="submit" data-status-save>حفظ</button>
                                            </form>
                                        </td>
                                        <td>
                                            @if($order->reward_label)
                                                <div class="gift-cell">
                                                    @if($order->reward_gift_image)
                                                        <img class="gift-img" src="{{ $order->reward_gift_image }}" alt="{{ $order->reward_label }}">
                                                    @endif
                                                    <div>
                                                        <strong>{{ $order->reward_label }}</strong>
                                                        <div class="dim">{{ $order->reward_won_at?->format('Y-m-d H:i') }}</div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="dim">لم يتم لف العجلة بعد</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($order->map_link)
                                                <a class="map-link" href="{{ $order->map_link }}" target="_blank" rel="noopener">فتح الخريطة</a>
                                            @else
                                                <span class="dim">-</span>
                                            @endif
                                            @if($order->customer_address)
                                                <div class="dim">{{ $order->customer_address }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $order->created_at?->format('Y-m-d') }}</strong>
                                            <div class="dim">{{ $order->created_at?->format('H:i') }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12">
                                            <div class="empty">لسه ما في طلبات مسجلة من الواجهة.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-wrap">
                        {{ $orders->links() }}
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        (() => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

            document.querySelectorAll('[data-status-form]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const button = form.querySelector('[data-status-save]');
                    const badge = form.querySelector('[data-status-badge]');
                    const select = form.querySelector('select[name="status"]');
                    const originalText = button?.textContent || 'حفظ';

                    if (button) {
                        button.disabled = true;
                        button.textContent = 'جاري...';
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: new FormData(form),
                        });
                        const result = await response.json().catch(() => ({}));

                        if (!response.ok || !result.ok) {
                            throw new Error(result.message || 'تعذر حفظ الحالة.');
                        }

                        if (badge) {
                            badge.textContent = result.label || select?.selectedOptions?.[0]?.textContent || '';
                            badge.className = `badge ${result.class || ''}`.trim();
                        }

                        if (button) {
                            button.textContent = 'تم';
                            setTimeout(() => {
                                button.textContent = originalText;
                                button.disabled = false;
                            }, 800);
                        }
                    } catch (error) {
                        if (button) {
                            button.textContent = 'فشل';
                            setTimeout(() => {
                                button.textContent = originalText;
                                button.disabled = false;
                            }, 1200);
                        }
                    }
                });
            });
        })();
    </script>

    <div class="qr-modal" id="orderQrModal" aria-hidden="true">
        <div class="qr-card" role="dialog" aria-modal="true" aria-labelledby="orderQrTitle">
            <div class="qr-card-head">
                <div>
                    <div class="qr-card-title" id="orderQrTitle">مسح QR الطلب</div>
                    <div class="dim">وجّه الكاميرا على QR الطلب وسيتم فتحه مباشرة.</div>
                </div>
                <button class="qr-close" type="button" id="orderQrClose" aria-label="إغلاق">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="qr-video-wrap">
                <video class="qr-video" id="orderQrVideo" muted playsinline></video>
            </div>
            <div class="qr-help" id="orderQrHelp">جاري تشغيل الكاميرا...</div>
            <form class="qr-manual" id="orderQrManualForm">
                <input type="text" id="orderQrManualInput" placeholder="أدخل رقم الطلب أو النص الموجود في QR" dir="ltr">
                <button class="filter-btn qr-scan-btn" type="submit">فتح</button>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const scanBtn = document.getElementById('orderQrScanBtn');
            const modal = document.getElementById('orderQrModal');
            const closeBtn = document.getElementById('orderQrClose');
            const video = document.getElementById('orderQrVideo');
            const help = document.getElementById('orderQrHelp');
            const manualForm = document.getElementById('orderQrManualForm');
            const manualInput = document.getElementById('orderQrManualInput');

            if (!scanBtn || !modal || !video) return;

            let stream = null;
            let detector = null;
            let scanning = false;

            const searchUrl = scanBtn.dataset.searchUrl || '{{ route('front-orders.index') }}';
            const orderPattern = /ORD-\d{8}-[A-Z0-9]+/i;

            function orderNumberFromScan(value) {
                const text = String(value || '').trim();
                if (!text) return '';

                try {
                    const url = new URL(text, window.location.origin);
                    const searchValue = url.searchParams.get('search');
                    const fromSearch = searchValue && searchValue.match(orderPattern);
                    if (fromSearch) return fromSearch[0].toUpperCase();
                } catch (error) {
                    // Not a URL, continue with plain text parsing.
                }

                const match = text.match(orderPattern);
                return match ? match[0].toUpperCase() : text;
            }

            function openOrder(value) {
                const orderNumber = orderNumberFromScan(value);
                if (!orderNumber) {
                    help.textContent = 'لم يتم العثور على رقم طلب داخل QR.';
                    return;
                }

                stopScanner();
                window.location.href = `${searchUrl}?search=${encodeURIComponent(orderNumber)}`;
            }

            async function scanLoop() {
                if (!scanning || !detector) return;

                try {
                    const codes = await detector.detect(video);
                    if (codes.length > 0) {
                        openOrder(codes[0].rawValue || '');
                        return;
                    }
                } catch (error) {
                    help.textContent = 'تعذر قراءة الكاميرا، جرّب إدخال رقم الطلب يدوياً.';
                }

                requestAnimationFrame(scanLoop);
            }

            async function startScanner() {
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                help.textContent = 'جاري تشغيل الكاميرا...';

                if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
                    help.textContent = 'المتصفح لا يدعم مسح QR بالكاميرا. استخدم الإدخال اليدوي.';
                    manualInput?.focus();
                    return;
                }

                try {
                    detector = new BarcodeDetector({ formats: ['qr_code'] });
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: 'environment' } },
                        audio: false
                    });
                    video.srcObject = stream;
                    await video.play();
                    scanning = true;
                    help.textContent = 'ضع QR داخل الكاميرا.';
                    requestAnimationFrame(scanLoop);
                } catch (error) {
                    help.textContent = 'لم نقدر نشغل الكاميرا. تأكد من السماح للمتصفح أو أدخل رقم الطلب يدوياً.';
                    manualInput?.focus();
                }
            }

            function stopScanner() {
                scanning = false;
                if (stream) {
                    stream.getTracks().forEach((track) => track.stop());
                    stream = null;
                }
                video.srcObject = null;
            }

            function closeScanner() {
                stopScanner();
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            }

            scanBtn.addEventListener('click', startScanner);
            closeBtn?.addEventListener('click', closeScanner);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeScanner();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.classList.contains('show')) closeScanner();
            });
            manualForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                openOrder(manualInput?.value || '');
            });
        })();
    </script>
</body>

</html>
