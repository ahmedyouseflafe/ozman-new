<!doctype html>
<html lang="ar" dir="rtl">

<head>
    @include('admin.partials.merchant_app_manifest', ['shop' => $shop])
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('front.partials.web_push_client')
    <title>إدارة مطعم {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @include('admin.restaurant.partials.dashboard_styles')
</head>

<body>
    @include('admin.includes.sidebar')
    <button type="button" class="order-alarm" id="restaurant-order-alarm" hidden
        aria-label="فتح الطلب الجديد وإيقاف صوت التنبيه">
        <span class="alarm-icon"><i class="ti ti-bell-ringing" aria-hidden="true"></i></span>
        <span class="alarm-copy">
            <strong>وصل طلب جديد!</strong>
            <span id="restaurant-order-alarm-message">اضغط هنا لفتح الطلب وإيقاف صوت التنبيه.</span>
        </span>
        <span class="alarm-action">فتح وإيقاف الصوت</span>
    </button>
    <main class="restaurant-page">
        <section class="hero glass rd-hero">
            <div class="rd-hero-top">
                <div class="rd-title">
                    <span class="eyebrow">لوحة المطعم</span>
                    <h1>{{ $shop->name }}</h1>
                </div>
                <div class="rd-main-actions">
                    @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.availability'))
                        <form class="availability-form" method="post" action="{{ route('restaurant.availability', $shop) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="is_accepting_orders" value="{{ $shop->is_accepting_orders ? 0 : 1 }}">
                            <button class="btn availability-toggle {{ $shop->is_accepting_orders ? 'is-open' : 'is-closed' }}" type="submit"
                                title="{{ $shop->is_accepting_orders ? 'اكبس لإغلاق المطعم وإيقاف الطلبات' : 'اكبس لفتح المطعم واستقبال الطلبات' }}">
                                <span class="availability-dot" aria-hidden="true"></span>
                                <span><b>{{ $shop->is_accepting_orders ? 'المطعم مفتوح' : 'المطعم مغلق' }}</b><small>{{ $shop->is_accepting_orders ? 'اكبس للإغلاق' : 'اكبس للفتح' }}</small></span>
                            </button>
                        </form>
                    @else
                        <span class="btn availability-toggle {{ $shop->is_accepting_orders ? 'is-open' : 'is-closed' }}">
                            <span class="availability-dot" aria-hidden="true"></span>
                            <span><b>{{ $shop->is_accepting_orders ? 'المطعم مفتوح' : 'المطعم مغلق' }}</b></span>
                        </span>
                    @endif
                    <a class="btn btn-primary" href="{{ route('restaurant.menu', $shop) }}" target="_blank"><i class="ti ti-external-link"></i> فتح المنيو</a>
                </div>
            </div>
            <nav class="rd-links" aria-label="أقسام المطعم">
                @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('products'))
                    <a href="{{ route('products', ['shop_id' => $shop->id]) }}"><i class="ti ti-tools-kitchen-2"></i> الوجبات</a>
                @endif
                @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.tables.index'))
                    <a href="{{ route('restaurant.tables.index', $shop) }}"><i class="ti ti-qrcode"></i> الطاولات</a>
                @endif
                @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.drivers.index'))
                    <a href="{{ route('restaurant.drivers.index', $shop) }}"><i class="ti ti-motorbike"></i> المندوبين</a>
                @endif
                @if (auth()->user()->canAccessRouteName('restaurant.customers.index'))
                    <a href="{{ route('restaurant.customers.index', $shop) }}"><i class="ti ti-users"></i> العملاء</a>
                @endif
                @if (auth()->user()->canAccessRouteName('reward-wheels.shop-signup.edit'))
                    <a href="{{ route('reward-wheels.shop-signup.edit', $shop) }}"><i class="ti ti-discount"></i> عجلة التسجيل</a>
                @endif
                <a href="{{ route('shops.show', $shop) }}"><i class="ti ti-building-store"></i> بيانات المطعم</a>
            </nav>
        </section>

        @if (session('status'))
            <div class="notice notice-success"><i class="ti ti-circle-check"></i> {{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice notice-error"><b>تعذّر تنفيذ العملية:</b>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rd-stats" aria-label="ملخص الطلبات — اكبس للفلترة">
            <button type="button" class="rd-stat" data-status-filter="" style="--accent:var(--cyan)"><span><i class="ti ti-receipt"></i> <span id="stat-period-label">{{ $selectedPeriodLabel }}</span></span><strong id="stat-orders">{{ $stats['orders'] }}</strong></button>
            <button type="button" class="rd-stat" data-status-filter="new" style="--accent:var(--green)"><span><i class="ti ti-sparkles"></i> جديدة</span><strong id="stat-new">{{ $stats['new'] }}</strong></button>
            <button type="button" class="rd-stat" data-status-filter="preparing" style="--accent:var(--yellow)"><span><i class="ti ti-chef-hat"></i> بالتحضير</span><strong id="stat-preparing">{{ $stats['preparing'] }}</strong></button>
            <button type="button" class="rd-stat" data-status-filter="ready" style="--accent:#b388ff"><span><i class="ti ti-bell-check"></i> جاهزة</span><strong id="stat-ready">{{ $stats['ready'] }}</strong></button>
            <div class="rd-stat rd-stat-sales" style="--accent:#ff9f43" title="يُحسب من الطلبات المكتملة فقط"><span><i class="ti ti-cash"></i> إجمالي مبيعات المطعم</span><strong id="stat-sales-total">{{ number_format((float) $stats['sales_total'], 2) }} ₪</strong></div>
        </section>

        {{-- نُقلت إدارة المندوبين إلى صفحة مستقلة. --}}
        {{--
            <section class="section glass" id="restaurant-drivers">
                <div class="section-head">
                    <div class="section-title"><span class="section-icon"><i class="ti ti-motorbike"></i></span>
                        <div>
                            <h2>مندوبي التوصيل</h2>
                            <div class="section-subtitle">أضف مندوبًا، ثم اختره في طلب التوصيل. يرى المندوب الطلب في
                                صفحته ويبدأ التوصيل بعد أن يصبح جاهزًا.</div>
                        </div>
                    </div>
                    <label class="status-field"><span>رابط دخول المندوب — انسخه وأرسله له</span><input class="field"
                            readonly value="{{ route('driver.login') }}" aria-label="رابط دخول المندوب"
                            onclick="this.select()"></label>
                </div>
                <form class="driver-management-form" method="post"
                    action="{{ route('restaurant.drivers.store', $shop) }}">
                    @csrf
                    <input class="field" name="name" value="{{ old('name') }}" placeholder="اسم المندوب"
                        autocomplete="name" required maxlength="255">
                    <input class="field" name="phone" value="{{ old('phone') }}" placeholder="رقم الجوال"
                        inputmode="tel" autocomplete="tel" required maxlength="60">
                    <input class="field" name="email" value="{{ old('email') }}"
                        placeholder="البريد الإلكتروني للدخول" type="email" autocomplete="off" required
                        maxlength="255">
                    <input class="field" name="password" placeholder="كلمة مرور للمندوب (8 أحرف على الأقل)"
                        type="password" autocomplete="new-password" required minlength="8">
                    <input class="field" name="password_confirmation" placeholder="تأكيد كلمة المرور"
                        type="password" autocomplete="new-password" required minlength="8">
                    <button class="btn btn-primary" type="submit"><i class="ti ti-user-plus"></i> إضافة مندوب وربطه
                        بالمطعم</button>
                </form>
                <div class="driver-grid">
                    @forelse($drivers as $driver)
                        <article class="driver-card">
                            <strong>{{ $driver->user?->name }} <span
                                    class="tag">{{ $driver->is_active ? 'فعال' : 'متوقف' }}</span></strong>
                            <small>{{ $driver->user?->phone }} · {{ $driver->user?->email }}</small>
                            @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('restaurant.drivers.toggle'))
                                <form method="post" action="{{ route('restaurant.drivers.toggle', $driver) }}">
                                    @csrf @method('patch')
                                    <input type="hidden" name="is_active" value="{{ $driver->is_active ? 0 : 1 }}">
                                    <button class="btn {{ $driver->is_active ? 'btn-danger' : '' }}"
                                        type="submit">{{ $driver->is_active ? 'إيقاف المندوب' : 'تفعيل المندوب' }}</button>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="empty">لا يوجد مندوبون بعد.</p>
                    @endforelse
                </div>
            </section>
        --}}

        <section class="section glass">
            <div class="live-heading">
                <div class="section-title">
                    <div>
                        <h2>الطلبات</h2>
                        <div class="section-subtitle">الطلبات اللي بدها شغل بتطلع أول: جديد ← تحضير ← جاهز.</div>
                    </div>
                </div>
                <div class="live-tools">
                    <span class="live" id="live-status"><i class="live-dot"></i> متصل وتحديث مباشر</span>
                    <button type="button" class="btn sound-toggle" id="restaurant-sound-toggle"><i class="ti ti-bell-ringing"></i> تفعيل صوت الطلبات والإشعارات</button>
                </div>
            </div>
            <form class="filters rd-filters" id="restaurant-orders-filter" method="get">
                <select class="field" name="period" aria-label="الفترة">
                    @foreach (['today' => 'اليوم', 'week' => 'هذا الأسبوع', 'month' => 'هذا الشهر', 'all' => 'كل الطلبات'] as $key => $label)
                        <option value="{{ $key }}" @selected($selectedPeriod === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="field" name="type" aria-label="نوع الطلب">
                    <option value="">كل الأنواع</option>
                    @foreach (['dine_in' => 'طاولات', 'delivery' => 'توصيل', 'pickup' => 'استلام'] as $key => $label)
                        <option value="{{ $key }}" @selected($selectedType === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="field" name="status" aria-label="الحالة">
                    <option value="">كل الحالات</option>
                    @foreach (['new' => 'جديد', 'preparing' => 'قيد التحضير', 'ready' => 'جاهز', 'out_for_delivery' => 'خرج للتوصيل', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'] as $key => $label)
                        <option value="{{ $key }}" @selected($selectedStatus === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary" id="restaurant-orders-filter-submit"><i class="ti ti-filter"></i> فلترة</button>
            </form>
            <div class="order-board" id="restaurant-orders-body" aria-live="polite">
                @include('admin.restaurant.partials.order_list_rows', [
                    'orders' => $orders,
                    'shop' => $shop,
                ])
            </div>
            <div>{{ $orders->links() }}</div>
        </section>
    </main>
    <script>
        (() => {
            const body = document.getElementById('restaurant-orders-body');
            const liveStatus = document.getElementById('live-status');
            const alarm = document.getElementById('restaurant-order-alarm');
            const alarmMessage = document.getElementById('restaurant-order-alarm-message');
            const soundToggle = document.getElementById('restaurant-sound-toggle');
            const filterForm = document.getElementById('restaurant-orders-filter');
            const filterSubmit = document.getElementById('restaurant-orders-filter-submit');
            const periodLabel = document.getElementById('stat-period-label');
            if (!body || !liveStatus || !alarm || !alarmMessage || !soundToggle || !filterForm) return;
            const feedEndpoint =
                {{ Illuminate\Support\Js::from(route('restaurant.orders.feed', ['shop' => $shop, 'period' => $selectedPeriod, 'status' => $selectedStatus, 'type' => $selectedType])) }};
            let feedUrl = feedEndpoint;
            const pushPublicKeyUrl = {{ Illuminate\Support\Js::from(route('merchant-app.push.public-key')) }};
            const pushSubscriptionUrl = {{ Illuminate\Support\Js::from(route('merchant-app.push.store')) }};
            const serviceWorkerUrl = {{ Illuminate\Support\Js::from(asset('merchant-pwa-sw.js')) }};
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const dashboardUrl = {{ Illuminate\Support\Js::from(route('restaurant.dashboard', ['shop' => $shop, 'period' => $selectedPeriod, 'status' => $selectedStatus, 'type' => $selectedType])) }};
            const initialLatestId = Number({{ (int) $latestOrderId }});
            const acknowledgementKey = 'ozman.restaurant.{{ (int) $shop->id }}.acknowledged-order';
            let acknowledgedId = initialLatestId;
            try {
                const savedId = window.localStorage.getItem(acknowledgementKey);
                if (savedId === null) window.localStorage.setItem(acknowledgementKey, String(initialLatestId));
                else acknowledgedId = Number(savedId) || 0;
            } catch (_) {}
            let pendingOrderId = 0;
            let audioContext = null;
            let alarmOutput = null;
            let alarmTimer = null;
            let soundUnlocked = false;
            let polling = false;
            let statusUpdating = false;
            let feedController = null;

            async function enableBackgroundNotifications(requestPermission = false) {
                if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                    throw new Error('هذا المتصفح لا يدعم إشعارات الخلفية.');
                }

                let permission = Notification.permission;
                if (permission === 'default' && requestPermission) {
                    permission = await Notification.requestPermission();
                }
                if (permission !== 'granted') {
                    throw new Error('اسمح بإشعارات الطلبات من إعدادات المتصفح ليصل الطلب حتى والشاشة مقفلة.');
                }

                const keyResponse = await fetch(pushPublicKeyUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!keyResponse.ok) throw new Error('تعذّر تجهيز إشعارات الخلفية.');
                const { public_key: publicKey } = await keyResponse.json();

                try {
                    const registration = await window.OzmanWebPush.register(serviceWorkerUrl);
                    const subscription = await window.OzmanWebPush.subscription(registration, publicKey);
                    await window.OzmanWebPush.save(pushSubscriptionUrl, subscription, { shop_id: {{ (int) $shop->id }} });
                } catch (error) { throw new Error(window.OzmanWebPush.message(error)); }

            }

            const periodLabels = {
                today: 'طلبات اليوم',
                week: 'طلبات هذا الأسبوع',
                month: 'طلبات هذا الشهر',
                all: 'كل الطلبات'
            };

            function updateFeedUrl() {
                const params = new URLSearchParams(new FormData(filterForm));
                const nextUrl = new URL(feedEndpoint, window.location.origin);
                nextUrl.search = params.toString();
                feedUrl = nextUrl.toString();

                const dashboardUrl = new URL(window.location.href);
                ['period', 'type', 'status'].forEach(key => {
                    const value = params.get(key);
                    if (value) dashboardUrl.searchParams.set(key, value);
                    else dashboardUrl.searchParams.delete(key);
                });
                window.history.replaceState({}, '', dashboardUrl);

                if (periodLabel) periodLabel.textContent = periodLabels[params.get('period')] || periodLabels.today;
            }

            async function applyFilters() {
                updateFeedUrl();
                if (filterSubmit) {
                    filterSubmit.disabled = true;
                    filterSubmit.classList.add('is-loading');
                }
                await refreshOrders();
                if (filterSubmit) {
                    filterSubmit.disabled = false;
                    filterSubmit.classList.remove('is-loading');
                }
            }

            function getAlarmOutput() {
                if (alarmOutput) return alarmOutput;

                const compressor = audioContext.createDynamicsCompressor();
                compressor.threshold.setValueAtTime(-18, audioContext.currentTime);
                compressor.knee.setValueAtTime(12, audioContext.currentTime);
                compressor.ratio.setValueAtTime(8, audioContext.currentTime);
                compressor.attack.setValueAtTime(.003, audioContext.currentTime);
                compressor.release.setValueAtTime(.18, audioContext.currentTime);

                const masterGain = audioContext.createGain();
                masterGain.gain.setValueAtTime(.95, audioContext.currentTime);
                masterGain.connect(compressor);
                compressor.connect(audioContext.destination);
                alarmOutput = masterGain;

                return alarmOutput;
            }

            function playAlarmPulse() {
                if (!soundUnlocked || !audioContext || audioContext.state !== 'running') return;
                const now = audioContext.currentTime;
                const output = getAlarmOutput();

                [
                    [784, 0, .16],
                    [988, .18, .16],
                    [1318, .36, .2],
                    [988, .62, .22]
                ].forEach(([frequency, offset, duration]) => {
                    const noteGain = audioContext.createGain();
                    const startsAt = now + offset;
                    const endsAt = startsAt + duration;
                    noteGain.gain.setValueAtTime(.0001, startsAt);
                    noteGain.gain.exponentialRampToValueAtTime(.42, startsAt + .018);
                    noteGain.gain.setValueAtTime(.42, Math.max(startsAt + .02, endsAt - .055));
                    noteGain.gain.exponentialRampToValueAtTime(.0001, endsAt);
                    noteGain.connect(output);

                    [
                        [frequency, 'triangle', 1],
                        [frequency * 2, 'sine', .22]
                    ].forEach(([tone, type, level]) => {
                        const oscillator = audioContext.createOscillator();
                        const toneGain = audioContext.createGain();
                        oscillator.type = type;
                        oscillator.frequency.setValueAtTime(tone, startsAt);
                        toneGain.gain.setValueAtTime(level, startsAt);
                        oscillator.connect(toneGain);
                        toneGain.connect(noteGain);
                        oscillator.start(startsAt);
                        oscillator.stop(endsAt + .02);
                    });
                });

                if (document.visibilityState !== 'visible') navigator.vibrate?.([180, 80, 180]);
            }

            async function enableSound(preview = false) {
                try {
                    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextClass) throw new Error('unsupported');
                    audioContext ||= new AudioContextClass();
                    if (audioContext.state === 'suspended') await audioContext.resume();
                    soundUnlocked = audioContext.state === 'running';
                    if (soundUnlocked) {
                        soundToggle.classList.add('enabled');
                        soundToggle.classList.remove('attention');
                        soundToggle.innerHTML = '<i class="ti ti-volume-2"></i> صوت الطلبات مفعّل';
                        if (preview || pendingOrderId) playAlarmPulse();
                    }
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    soundToggle.innerHTML = '<i class="ti ti-volume-off"></i> اضغط للسماح بالصوت';
                }
            }

            function startAlarm() {
                if (alarmTimer) return;
                if (!soundUnlocked) soundToggle.classList.add('attention');
                playAlarmPulse();
                alarmTimer = window.setInterval(playAlarmPulse, 1200);
            }

            function stopAlarm() {
                if (alarmTimer) window.clearInterval(alarmTimer);
                alarmTimer = null;
            }

            function showOrderAlarm(order) {
                const orderId = Number(order?.id || 0);
                if (!orderId || orderId <= acknowledgedId) return;
                pendingOrderId = Math.max(pendingOrderId, orderId);
                const types = {
                    dine_in: 'طلب طاولة',
                    delivery: 'طلب توصيل',
                    pickup: 'طلب استلام'
                };
                alarmMessage.textContent =
                    `${order.number || 'طلب جديد'} · ${types[order.type] || 'طلب مطعم'}${order.customer ? ` · ${order.customer}` : ''}`;
                alarm.hidden = false;
                document.title = '🔔 طلب جديد — ' + {{ Illuminate\Support\Js::from($shop->name) }};
                liveStatus.innerHTML = '<i class="live-dot"></i> وصل طلب جديد — بانتظار التأكيد';
                liveStatus.style.color = 'var(--yellow)';
                startAlarm();
            }

            soundToggle.addEventListener('click', async () => {
                await enableSound(true);
                try {
                    await enableBackgroundNotifications(true);
                    soundToggle.classList.remove('attention');
                    soundToggle.innerHTML = '<i class="ti ti-bell-check"></i> صوت وإشعارات الطلبات مفعّلة';
                    liveStatus.innerHTML = '<i class="live-dot"></i> إشعارات الخلفية مفعّلة حتى عند قفل الشاشة';
                    liveStatus.style.color = 'var(--green)';
                } catch (error) {
                    soundToggle.classList.add('attention');
                    liveStatus.innerHTML = '<i class="live-dot"></i> فعّل إذن الإشعارات ليصل الطلب عند قفل الشاشة';
                    liveStatus.style.color = 'var(--yellow)';
                }
            });
            const unlockOnFirstInteraction = () => enableSound(false);
            document.addEventListener('pointerdown', unlockOnFirstInteraction, {
                once: true,
                capture: true
            });
            document.addEventListener('keydown', unlockOnFirstInteraction, {
                once: true,
                capture: true
            });
            if ('Notification' in window && Notification.permission === 'granted') {
                enableBackgroundNotifications().then(() => {
                    soundToggle.innerHTML = '<i class="ti ti-bell-check"></i> إشعارات الخلفية مفعّلة';
                }).catch(() => {});
            }

            alarm.addEventListener('click', () => {
                const orderId = pendingOrderId;
                acknowledgedId = Math.max(acknowledgedId, orderId);
                try {
                    window.localStorage.setItem(acknowledgementKey, String(acknowledgedId));
                } catch (_) {}
                alarm.hidden = true;
                pendingOrderId = 0;
                stopAlarm();
                soundToggle.classList.remove('attention');
                document.title = {{ Illuminate\Support\Js::from('إدارة مطعم ' . $shop->name) }};
                liveStatus.innerHTML = '<i class="live-dot"></i> تم فتح الطلب الجديد';
                liveStatus.style.color = 'var(--green)';
                const row = document.getElementById(`restaurant-order-${orderId}`);
                if (row) {
                    row.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    row.classList.add('is-flash');
                } else if (orderId) window.location.href = `${dashboardUrl}#restaurant-order-${orderId}`;
            });

            // Tapping an empty part of a card opens the order; buttons and links keep their own job.
            // Cards stay small; the status menu stays open across the live refresh.
            const openMenus = new Set();
            const setMenu = (card, open) => {
                const menu = card.querySelector('.status-menu'), toggle = card.querySelector('.status-menu-toggle');
                if (!menu || !toggle) return;
                menu.hidden = !open; toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                open ? openMenus.add(card.dataset.orderId) : openMenus.delete(card.dataset.orderId);
            };
            body.addEventListener('click', event => {
                const card = event.target.closest('.order-card[data-order-id]');
                if (!card) return;
                const toggle = event.target.closest('.status-menu-toggle');
                if (toggle) { setMenu(card, toggle.getAttribute('aria-expanded') !== 'true'); return; }
                // Tapping the card opens the order's own details page.
                if (event.target.closest('a,button,input,select,label,form') || window.getSelection()?.toString()) return;
                window.location.href = card.dataset.orderUrl;
            });

            // The summary tiles double as status filters.
            const statusSelect = filterForm.querySelector('select[name="status"]');
            const markStatusTile = () => document.querySelectorAll('[data-status-filter]').forEach(tile => {
                tile.classList.toggle('is-active', tile.dataset.statusFilter === (statusSelect?.value || ''));
                tile.setAttribute('aria-pressed', tile.classList.contains('is-active') ? 'true' : 'false');
            });
            document.querySelectorAll('[data-status-filter]').forEach(tile => tile.addEventListener('click', () => {
                if (!statusSelect) return;
                statusSelect.value = statusSelect.value === tile.dataset.statusFilter ? '' : tile.dataset.statusFilter;
                markStatusTile();
                applyFilters();
            }));
            statusSelect?.addEventListener('change', markStatusTile);
            markStatusTile();

            // Keep "edit time" panels open across the live refresh.
            const openPrep = new Set();
            const restoreOpenPrep = () => {
                openMenus.forEach(id => { const card = document.getElementById(`restaurant-order-${id}`); card ? setMenu(card, true) : openMenus.delete(id); });
                openPrepRestore();
            };
            const openPrepRestore = () => openPrep.forEach(id => {
                const card = document.getElementById(`restaurant-order-${id}`);
                const prep = card?.querySelector('.quick-prep'), edit = card?.querySelector('.prep-edit');
                if (!prep || !edit) { openPrep.delete(id); return; }
                prep.hidden = false; edit.setAttribute('aria-expanded', 'true'); edit.innerHTML = '<i class="ti ti-x" aria-hidden="true"></i> إخفاء';
            });
            let lastHtml = '';

            body.addEventListener('click', event => {
                const choice = event.target.closest('.preparation-choice');
                if (!choice || choice.disabled) return;

                const form = choice.closest('.status-form');
                const minutes = Number(choice.dataset.preparationMinutes);
                const value = form?.querySelector('input[name="estimated_preparation_minutes"]');
                const saveButton = form?.querySelector('.status-save');
                if (!form || !value || !saveButton || !Number.isInteger(minutes)) return;

                value.value = String(minutes);
                form.querySelectorAll('.preparation-choice').forEach(button => {
                    const selected = button === choice;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });

                form.requestSubmit(saveButton);
            });

            body.addEventListener('submit', async event => {
                const form = event.target.closest('.status-form');
                if (!form) return;
                event.preventDefault();
                if (statusUpdating) return;

                const submitter = event.submitter || form.querySelector('.status-save');
                if (submitter.value === 'preparing' && !form.querySelector('input[name="estimated_preparation_minutes"]').value) {
                    // Cooking needs a time first: show the time choices; picking one saves.
                    const prep = form.querySelector('.quick-prep');
                    if (prep) prep.hidden = false;
                    const feedbackBox = form.querySelector('.status-feedback');
                    if (feedbackBox) feedbackBox.textContent = 'اختار وقت التحضير.';
                    return;
                }
                const payload = new FormData(form);
                payload.set('status', submitter.value);
                const feedback = form.querySelector('.status-feedback');
                const buttons = [...form.querySelectorAll('button:not(:disabled)')];
                statusUpdating = true;
                form.classList.add('is-saving');
                buttons.forEach(button => button.disabled = true);
                feedback.textContent = 'جارٍ حفظ حالة الطلب...';
                feedback.classList.remove('is-error');

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: payload,
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                    });
                    if (response.redirected || response.status === 401 || response.status === 403) {
                        throw new Error('انتهت الجلسة أو لا توجد صلاحية. حدّث الصفحة وسجّل الدخول مجددًا.');
                    }
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const validationError = Object.values(data.errors || {}).flat()[0];
                        throw new Error(validationError || (response.status === 419 ?
                            'انتهت الجلسة؛ حدّث الصفحة وحاول مجددًا.' : data.message ||
                            'تعذّر حفظ حالة الطلب. حاول مجددًا.'));
                    }
                    const replacement = document.createElement('div');
                    replacement.innerHTML = data.html || '';
                    const updatedRow = replacement.querySelector('.order-card');
                    if (!updatedRow) throw new Error('تم الحفظ، لكن تعذّر تحديث العرض. حدّث الصفحة.');
                    const savedId = form.closest('.order-card')?.dataset.orderId;
                    openPrep.delete(savedId); openMenus.delete(savedId);
                    form.closest('.order-card').replaceWith(updatedRow);
                    lastHtml = '';
                    tickTimers();
                    const updatedFeedback = updatedRow.querySelector('.status-feedback');
                    if (updatedFeedback) updatedFeedback.textContent = 'تم الحفظ وإشعار العميل.';
                } catch (error) {
                    feedback.textContent = error.message;
                    feedback.classList.add('is-error');
                } finally {
                    statusUpdating = false;
                    form.classList.remove('is-saving');
                    buttons.forEach(button => button.disabled = false);
                }
            });

            // Countdown for orders in the kitchen: counts down to the chosen time, then shows how late it is.
            const tickTimers = () => {
                const now = Date.now();
                document.querySelectorAll('.prep-timer[data-due-at]').forEach(timer => {
                    const left = Math.round((Date.parse(timer.dataset.dueAt) - now) / 1000);
                    const abs = Math.abs(left);
                    const hours = Math.floor(abs / 3600), mins = Math.floor(abs % 3600 / 60);
                    // Over an hour: show hours and minutes instead of a huge minute count.
                    const text = hours ? `${hours}س ${mins}د` : `${String(mins).padStart(2, '0')}:${String(abs % 60).padStart(2, '0')}`;
                    const value = timer.querySelector('.prep-timer-value');
                    if (value) { value.textContent = left >= 0 ? text : `+${text}`; value.style.direction = hours ? 'rtl' : 'ltr'; }
                    timer.classList.toggle('is-late', left < 0);
                    timer.classList.toggle('is-soon', left >= 0 && left <= 120);
                    timer.title = left >= 0 ? 'الوقت المتبقي للتجهيز' : 'متأخر عن الوقت المحدد';
                });
            };
            tickTimers();
            window.setInterval(tickTimers, 1000);

            body.addEventListener('click', event => {
                const edit = event.target.closest('.prep-edit');
                if (edit) {
                    const prep = edit.closest('.quick-status')?.querySelector('.quick-prep');
                    const id = edit.closest('.order-card')?.dataset.orderId;
                    if (prep) {
                        prep.hidden = !prep.hidden;
                        edit.setAttribute('aria-expanded', prep.hidden ? 'false' : 'true');
                        edit.innerHTML = prep.hidden ? '<i class="ti ti-pencil" aria-hidden="true"></i> الوقت' : '<i class="ti ti-x" aria-hidden="true"></i> إخفاء';
                        if (id) prep.hidden ? openPrep.delete(id) : openPrep.add(id);
                    }
                    return;
                }
                const cancel = event.target.closest('.quick-status button[value="cancelled"]');
                if (cancel && !window.confirm('متأكد إنك بدك تلغي هالطلب؟ رح يوصل للزبون إشعار بالإلغاء.')) event.preventDefault();
            });

            async function refreshOrders() {
                if (polling) feedController?.abort();
                const requestUrl = feedUrl;
                const controller = new AbortController();
                feedController = controller;
                polling = true;
                try {
                    const response = await fetch(requestUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal: controller.signal
                    });
                    if (response.status === 401 || response.status === 403) {
                        liveStatus.innerHTML = '<i class="live-dot"></i> لا توجد صلاحية أو انتهت الجلسة';
                        liveStatus.style.color = 'var(--red)';
                        return
                    }
                    if (!response.ok) throw new Error('خطأ من السيرفر (' + response.status + ')');
                    const data = await response.json();
                    if (requestUrl !== feedUrl) return;
                    const editingOrder = body.contains(document.activeElement) && document.activeElement.matches('input,select,textarea');
                    // Re-render only when something changed, so the page doesn't jump every 3 seconds.
                    if (!editingOrder && !statusUpdating && data.html !== lastHtml) {
                        lastHtml = data.html; body.innerHTML = data.html; restoreOpenPrep(); tickTimers();
                    }
                    for (const key of ['orders', 'new', 'preparing', 'ready']) {
                        const element = document.getElementById(`stat-${key}`);
                        if (element) element.textContent = data.stats[key] ?? 0
                    }
                    const salesTotal = document.getElementById('stat-sales-total');
                    if (salesTotal) salesTotal.textContent =
                        `${Number(data.stats.sales_total??0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})} ₪`;
                    if (Number(data.latest_id) > acknowledgedId) showOrderAlarm(data.latest_order);
                    else if (!pendingOrderId) {
                        liveStatus.innerHTML = '<i class="live-dot"></i> متصل وتحديث مباشر';
                        liveStatus.style.color = 'var(--green)';
                    }
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    liveStatus.innerHTML = '<i class="live-dot"></i> جاري إعادة الاتصال' + (String(error.message).startsWith('خطأ') ? ' · ' + error.message : '');
                    liveStatus.title = navigator.onLine ? 'تعذّر تحديث الطلبات، رح نحاول كل 3 ثواني.' : 'الجهاز مش متصل بالإنترنت.';
                    liveStatus.style.color = 'var(--yellow)'
                } finally {
                    if (feedController === controller) {
                        feedController = null;
                        polling = false;
                    }
                }
            }
            filterForm.addEventListener('submit', event => {
                event.preventDefault();
                applyFilters();
            });
            filterForm.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', applyFilters);
            });
            window.setInterval(refreshOrders, 3000);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) refreshOrders()
            });
        })();
    </script>
</body>

</html>
