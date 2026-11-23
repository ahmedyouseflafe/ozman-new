<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <title>الطلبات - Ozman</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.front_orders.index')
</head>

<body>
    @php($isMarketerView = auth()->user()?->isMarketer())
    <div class="shell">
        @include('admin.includes.sidebar')
        <main class="main">
            @include('admin.includes.header', ['title' => 'الطلبات'])
            <div class="content">
                <div class="page-head">
                    <div>
                        <div class="page-kicker">Ozman Orders</div>
                        <h1>الطلبات</h1>
                        <p>كل طلب من السلة أو الدفع الفوري مع الهدية التي ظهرت للعميل من عجلة الشراء.</p>
                    </div>
                </div>

                @if(($distributorProfiles ?? collect())->isNotEmpty())
                    @foreach($distributorProfiles as $distributorProfile)
                        <section class="profile-panel">
                            <img
                                class="profile-photo"
                                src="{{ $distributorProfile->image ? asset($distributorProfile->image) : asset('images/logo.svg') }}"
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
                    @unless($isMarketerView)
                    <div class="stat-card" style="--card-color: var(--accent);">
                        <div class="stat-label">طلبات معها هدية</div>
                        @unless($isMarketerView)
                        <div class="stat-val">{{ number_format($rewardedCount) }}</div>
                        <i class="ti ti-gift stat-icon"></i>
                        @endunless
                    </div>
                    @endunless
                    <div class="stat-card" style="--card-color: var(--primary);">
                        <div class="stat-label">طلبات عبر مسوقين</div>
                        <div class="stat-val">{{ number_format($marketerCount) }}</div>
                        <i class="ti ti-speakerphone stat-icon"></i>
                    </div>
                    <div class="stat-card" style="--card-color: var(--green);">
                        <div class="stat-label">{{ $isMarketerView ? 'أرباحي' : 'أرباح المسوقين' }}</div>
                        <div class="stat-val">{{ number_format((float) $marketerCommissionTotal, 2) }}</div>
                        <i class="ti ti-coins stat-icon"></i>
                    </div>
                </div>

                <section class="panel">
                    <div class="panel-head">
                        <div class="panel-title">
                            <i class="ti ti-list-details"></i>
                            سجل الطلبات
                        </div>
                        <form class="filter-row" method="GET" action="{{ route('front-orders.index') }}">
                            <input class="search-inp" type="search" name="search" value="{{ $search }}" placeholder="{{ $isMarketerView ? 'بحث بالاسم، الهاتف أو رقم الطلب' : 'بحث بالاسم، الهاتف، رقم الطلب أو الهدية' }}">
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
                                    @unless($isMarketerView)
                                        <th>الهدية</th>
                                    @endunless
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
                                            @if($order->distributorMarketer)
                                                @php($commissionRate = $order->marketer_commission_rate !== null ? (float) $order->marketer_commission_rate : (float) $order->distributorMarketer->commission_rate)
                                                @php($commissionAmount = $order->marketer_commission_amount !== null ? (float) $order->marketer_commission_amount : ((float) $order->total * $commissionRate / 100))
                                                <div class="dim" style="color: var(--green); font-weight: 900;">عمولة المسوق: {{ number_format($commissionAmount, 2) }} شيكل ({{ number_format($commissionRate, 2) }}%)</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $order->order_channel === 'whatsapp' ? 'green' : 'yellow' }}">
                                                <i class="ti {{ match ($order->order_channel) { 'whatsapp' => 'ti-brand-whatsapp', 'app' => 'ti-device-mobile', default => 'ti-bolt' } }}"></i>
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
                                            @if($isMarketerView || ! auth()->user()->canAccessRouteName('front-orders.status'))
                                                <span class="badge {{ $order->statusClass() }}" data-status-badge>{{ $order->statusLabel() }}</span>
                                            @else
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
                                            @endif
                                        </td>
                                        @unless($isMarketerView)
                                        <td>
                                            @include('admin.restaurant.partials.purchase_reward')
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
                                        @endunless
                                        <td>
                                            @if($order->map_link)
                                                <a class="map-link" href="{{ $order->map_link }}" target="_blank" rel="noopener">فتح الخريطة</a>
                                            @else
                                                <span class="dim">-</span>
                                            @endif
                                            @if($order->customer_address)
                                                <div class="dim">{{ $order->customer_address }}</div>
                                            @endif
                                            @if($order->customer_notes)
                                                <div class="dim" style="white-space: pre-line;">{{ $order->customer_notes }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $order->created_at?->format('Y-m-d') }}</strong>
                                            <div class="dim">{{ $order->created_at?->format('H:i') }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isMarketerView ? 11 : 12 }}">
                                            <div class="empty">لسه ما في طلبات مسجلة من الواجهة.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-wrap">
                        {{ $orders->onEachSide(1)->links('admin.partials.pagination') }}
                    </div>
                </section>
            </div>
        </main>
    </div>

    @viewScript('admin.front_orders.index')

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
