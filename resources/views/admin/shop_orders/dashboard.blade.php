<!doctype html>
<html lang="ar" dir="rtl">

<head>
    @include('admin.partials.merchant_app_manifest', ['shop' => $shop])
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('front.partials.web_push_client')
    <title>طلبات {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    {{-- Same look as the restaurant dashboard. --}}
    @include('admin.restaurant.partials.dashboard_styles')
    <style>
        .oc-status.status-tag-delivered { color: #9fb3c2; background: rgba(255,255,255,.06) }
        .order-card-delivered { opacity: .78 }
    </style>
</head>

<body>
    @include('admin.includes.sidebar')
    <button type="button" class="order-alarm" id="shop-order-alarm" hidden aria-label="فتح الطلب الجديد وإيقاف صوت التنبيه">
        <span class="alarm-icon"><i class="ti ti-bell-ringing" aria-hidden="true"></i></span>
        <span class="alarm-copy">
            <strong>وصل طلب جديد!</strong>
            <span id="shop-order-alarm-message">اضغط هنا لفتح الطلب وإيقاف صوت التنبيه.</span>
        </span>
        <span class="alarm-action">فتح وإيقاف الصوت</span>
    </button>
    <main class="restaurant-page">
        <section class="hero glass rd-hero">
            <div class="rd-hero-top">
                <div class="rd-title">
                    <span class="eyebrow">طلبات المحل</span>
                    <h1>{{ $shop->name }}</h1>
                </div>
                <div class="rd-main-actions">
                    @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('shop-orders.availability'))
                        <form class="availability-form" method="post" action="{{ route('shop-orders.availability', $shop) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="is_accepting_orders" value="{{ $shop->is_accepting_orders ? 0 : 1 }}">
                            <button class="btn availability-toggle {{ $shop->is_accepting_orders ? 'is-open' : 'is-closed' }}" type="submit"
                                title="{{ $shop->is_accepting_orders ? 'اكبس لإيقاف استقبال الطلبات' : 'اكبس لاستقبال الطلبات' }}">
                                <span class="availability-dot" aria-hidden="true"></span>
                                <span><b>{{ $shop->is_accepting_orders ? 'المحل مفتوح' : 'المحل مغلق' }}</b><small>{{ $shop->is_accepting_orders ? 'اكبس للإغلاق' : 'اكبس للفتح' }}</small></span>
                            </button>
                        </form>
                    @endif
                    <a class="btn btn-primary" href="{{ $shop->publicUrl() }}" target="_blank"><i class="ti ti-external-link"></i> فتح المحل</a>
                </div>
            </div>
            <nav class="rd-links" aria-label="أقسام المحل">
                @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('products'))
                    <a href="{{ route('products', ['shop_id' => $shop->id]) }}"><i class="ti ti-package"></i> المنتجات</a>
                @endif
                @if (auth()->user()->isSuperAdmin() || auth()->user()->canAccessRouteName('front-orders.index'))
                    <a href="{{ route('front-orders.index', ['all' => 1]) }}"><i class="ti ti-list-details"></i> جدول كل الطلبات</a>
                @endif
                <a href="{{ route('shops.show', $shop) }}"><i class="ti ti-building-store"></i> لوحة المتجر</a>
            </nav>
        </section>

        @if (session('status'))
            <div class="notice notice-success"><i class="ti ti-circle-check"></i> {{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice notice-error"><b>تعذّر تنفيذ العملية:</b> {{ $errors->first() }}</div>
        @endif

        <section class="rd-stats" aria-label="ملخص الطلبات — اكبس للفلترة">
            <button type="button" class="rd-stat" data-status-filter="" style="--accent:var(--cyan)"><span><i class="ti ti-receipt"></i> <span id="stat-period-label">{{ $selectedPeriodLabel }}</span></span><strong id="stat-orders">{{ $stats['orders'] }}</strong></button>
            <button type="button" class="rd-stat" data-status-filter="new" style="--accent:var(--green)"><span><i class="ti ti-sparkles"></i> جديدة</span><strong id="stat-new">{{ $stats['new'] }}</strong></button>
            <button type="button" class="rd-stat" data-status-filter="preparing" style="--accent:var(--yellow)"><span><i class="ti ti-package"></i> بالتجهيز</span><strong id="stat-preparing">{{ $stats['preparing'] }}</strong></button>
            <button type="button" class="rd-stat" data-status-filter="ready" style="--accent:#b388ff"><span><i class="ti ti-bell-check"></i> جاهزة / بالطريق</span><strong id="stat-ready">{{ $stats['ready'] }}</strong></button>
            <div class="rd-stat rd-stat-sales" style="--accent:#ff9f43" title="يُحسب من الطلبات المسلّمة فقط"><span><i class="ti ti-cash"></i> إجمالي المبيعات</span><strong id="stat-sales-total">{{ number_format((float) $stats['sales_total'], 2) }} ₪</strong></div>
        </section>

        <section class="section glass">
            <div class="live-heading">
                <div class="section-title">
                    <div>
                        <h2>الطلبات</h2>
                        <div class="section-subtitle">الطلبات اللي بدها شغل بتطلع أول: جديد ← تجهيز ← جاهز ← تسليم.</div>
                    </div>
                </div>
                <div class="live-tools">
                    <span class="live" id="live-status"><i class="live-dot"></i> متصل وتحديث مباشر</span>
                    <button type="button" class="btn sound-toggle" id="shop-sound-toggle"><i class="ti ti-bell-ringing"></i> تفعيل صوت الطلبات والإشعارات</button>
                </div>
            </div>
            <form class="filters rd-filters" id="shop-orders-filter" method="get">
                <select class="field" name="period" aria-label="الفترة">
                    @foreach (['all' => 'كل الطلبات', 'today' => 'اليوم', 'week' => 'هذا الأسبوع', 'month' => 'هذا الشهر'] as $key => $label)
                        <option value="{{ $key }}" @selected($selectedPeriod === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="field" name="status" aria-label="الحالة">
                    <option value="">كل الحالات</option>
                    @foreach (\App\Http\Controllers\ShopOrdersController::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected($selectedStatus === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary" id="shop-orders-filter-submit"><i class="ti ti-filter"></i> فلترة</button>
            </form>
            <div class="order-board" id="shop-orders-body" aria-live="polite">
                @include('admin.shop_orders.partials.cards', ['orders' => $orders, 'shop' => $shop])
            </div>
            <div>{{ $orders->links() }}</div>
        </section>
    </main>
    <script>
        (() => {
            const body = document.getElementById('shop-orders-body');
            const liveStatus = document.getElementById('live-status');
            const alarm = document.getElementById('shop-order-alarm');
            const alarmMessage = document.getElementById('shop-order-alarm-message');
            const soundToggle = document.getElementById('shop-sound-toggle');
            const filterForm = document.getElementById('shop-orders-filter');
            const filterSubmit = document.getElementById('shop-orders-filter-submit');
            const periodLabel = document.getElementById('stat-period-label');
            if (!body || !liveStatus || !alarm || !soundToggle || !filterForm) return;
            const feedEndpoint = {{ Illuminate\Support\Js::from(route('shop-orders.feed', $shop)) }};
            let feedUrl = feedEndpoint + '?' + new URLSearchParams(new FormData(filterForm)).toString();
            const pushPublicKeyUrl = {{ Illuminate\Support\Js::from(route('merchant-app.push.public-key')) }};
            const pushSubscriptionUrl = {{ Illuminate\Support\Js::from(route('merchant-app.push.store')) }};
            const serviceWorkerUrl = {{ Illuminate\Support\Js::from(asset('merchant-pwa-sw.js')) }};
            const initialLatestId = Number({{ (int) $latestOrderId }});
            const acknowledgementKey = 'ozman.shop-orders.{{ (int) $shop->id }}.acknowledged-order';
            let acknowledgedId = initialLatestId;
            try {
                const savedId = window.localStorage.getItem(acknowledgementKey);
                if (savedId === null) window.localStorage.setItem(acknowledgementKey, String(initialLatestId));
                else acknowledgedId = Number(savedId) || 0;
            } catch (_) {}
            let pendingOrderId = 0, audioContext = null, alarmOutput = null, alarmTimer = null, soundUnlocked = false;
            let polling = false, statusUpdating = false, feedController = null, lastHtml = '';

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
                const channels = { app: 'من التطبيق', whatsapp: 'واتساب', electronics: 'من الموقع' };
                alarmMessage.textContent = `${order.number || 'طلب جديد'} · ${channels[order.channel] || 'طلب'}${order.customer ? ` · ${order.customer}` : ''}`;
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
            const unlock = () => enableSound(false);
            document.addEventListener('pointerdown', unlock, { once: true, capture: true });
            document.addEventListener('keydown', unlock, { once: true, capture: true });
            if ('Notification' in window && Notification.permission === 'granted') {
                enableBackgroundNotifications().then(() => {
                    soundToggle.innerHTML = '<i class="ti ti-bell-check"></i> إشعارات الخلفية مفعّلة';
                }).catch(() => {});
            }

            alarm.addEventListener('click', () => {
                const orderId = pendingOrderId;
                acknowledgedId = Math.max(acknowledgedId, orderId);
                try { window.localStorage.setItem(acknowledgementKey, String(acknowledgedId)); } catch (_) {}
                alarm.hidden = true;
                pendingOrderId = 0;
                stopAlarm();
                document.title = {{ Illuminate\Support\Js::from('طلبات ' . $shop->name) }};
                const card = document.getElementById(`shop-order-${orderId}`);
                if (card) { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); card.classList.add('is-flash'); }
            });

            // Card tap opens the order page; the status menu stays open across the live refresh.
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
                const cancel = event.target.closest('button[value="cancelled"]');
                if (cancel && !window.confirm('متأكد إنك بدك تلغي هالطلب؟ رح يوصل للزبون إشعار بالإلغاء.')) { event.preventDefault(); return; }
                if (event.target.closest('a,button,input,select,label,form') || window.getSelection()?.toString()) return;
                window.location.href = card.dataset.orderUrl;
            });

            const periodLabels = { today: 'طلبات اليوم', week: 'طلبات هذا الأسبوع', month: 'طلبات هذا الشهر', all: 'كل الطلبات' };
            function updateFeedUrl() {
                const params = new URLSearchParams(new FormData(filterForm));
                feedUrl = feedEndpoint + '?' + params.toString();
                const pageUrl = new URL(window.location.href);
                ['period', 'status'].forEach(key => params.get(key) ? pageUrl.searchParams.set(key, params.get(key)) : pageUrl.searchParams.delete(key));
                window.history.replaceState({}, '', pageUrl);
                if (periodLabel) periodLabel.textContent = periodLabels[params.get('period')] || periodLabels.all;
            }
            async function applyFilters() {
                updateFeedUrl();
                if (filterSubmit) filterSubmit.disabled = true;
                await refreshOrders();
                if (filterSubmit) filterSubmit.disabled = false;
            }

            // The summary tiles double as status filters.
            const statusSelect = filterForm.querySelector('select[name="status"]');
            const markStatusTile = () => document.querySelectorAll('[data-status-filter]').forEach(tile => {
                tile.classList.toggle('is-active', tile.dataset.statusFilter === (statusSelect?.value || ''));
                tile.setAttribute('aria-pressed', tile.classList.contains('is-active') ? 'true' : 'false');
            });
            document.querySelectorAll('[data-status-filter]').forEach(tile => tile.addEventListener('click', () => {
                statusSelect.value = statusSelect.value === tile.dataset.statusFilter ? '' : tile.dataset.statusFilter;
                markStatusTile();
                applyFilters();
            }));
            statusSelect?.addEventListener('change', markStatusTile);
            markStatusTile();

            body.addEventListener('submit', async event => {
                const form = event.target.closest('.status-form');
                if (!form) return;
                event.preventDefault();
                if (statusUpdating || !event.submitter) return;
                const payload = new FormData(form);
                payload.set('status', event.submitter.value);
                const feedback = form.querySelector('.status-feedback');
                const buttons = [...form.querySelectorAll('button:not(:disabled)')];
                statusUpdating = true;
                buttons.forEach(button => button.disabled = true);
                feedback.textContent = 'جارٍ الحفظ...';
                feedback.classList.remove('is-error');
                try {
                    const response = await fetch(form.action, {
                        method: 'POST', body: payload, credentials: 'same-origin',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (response.redirected || response.status === 401 || response.status === 403) throw new Error('انتهت الجلسة أو لا توجد صلاحية. حدّث الصفحة.');
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || (response.status === 419 ? 'انتهت الجلسة؛ حدّث الصفحة.' : data.message || 'تعذّر الحفظ.'));
                    const holder = document.createElement('div');
                    holder.innerHTML = data.html || '';
                    const updated = holder.querySelector('.order-card');
                    const card = form.closest('.order-card');
                    openMenus.delete(card.dataset.orderId);
                    if (updated) { card.replaceWith(updated); updated.querySelector('.status-feedback').textContent = 'تم الحفظ وإشعار الزبون.'; }
                    lastHtml = '';
                } catch (error) {
                    feedback.textContent = error.message;
                    feedback.classList.add('is-error');
                } finally {
                    statusUpdating = false;
                    buttons.forEach(button => button.disabled = false);
                }
            });

            async function refreshOrders() {
                if (polling) feedController?.abort();
                const requestUrl = feedUrl;
                const controller = new AbortController();
                feedController = controller;
                polling = true;
                try {
                    const response = await fetch(requestUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
                    if (response.status === 401 || response.status === 403) {
                        liveStatus.innerHTML = '<i class="live-dot"></i> لا توجد صلاحية أو انتهت الجلسة';
                        liveStatus.style.color = 'var(--red)';
                        return;
                    }
                    if (!response.ok) throw new Error('خطأ من السيرفر (' + response.status + ')');
                    const data = await response.json();
                    if (requestUrl !== feedUrl) return;
                    if (!statusUpdating && data.html !== lastHtml) {
                        lastHtml = data.html;
                        body.innerHTML = data.html;
                        openMenus.forEach(id => { const card = document.getElementById(`shop-order-${id}`); card ? setMenu(card, true) : openMenus.delete(id); });
                    }
                    for (const key of ['orders', 'new', 'preparing', 'ready']) {
                        const element = document.getElementById(`stat-${key}`);
                        if (element) element.textContent = data.stats[key] ?? 0;
                    }
                    const sales = document.getElementById('stat-sales-total');
                    if (sales) sales.textContent = `${Number(data.stats.sales_total ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₪`;
                    if (Number(data.latest_id) > acknowledgedId) showOrderAlarm(data.latest_order);
                    else if (!pendingOrderId) { liveStatus.innerHTML = '<i class="live-dot"></i> متصل وتحديث مباشر'; liveStatus.style.color = 'var(--green)'; }
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    liveStatus.innerHTML = '<i class="live-dot"></i> جاري إعادة الاتصال';
                    liveStatus.style.color = 'var(--yellow)';
                } finally {
                    if (feedController === controller) { feedController = null; polling = false; }
                }
            }
            filterForm.addEventListener('submit', event => { event.preventDefault(); applyFilters(); });
            filterForm.querySelectorAll('select').forEach(select => select.addEventListener('change', applyFilters));
            window.setInterval(refreshOrders, 5000);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshOrders(); });
        })();
    </script>
</body>

</html>
