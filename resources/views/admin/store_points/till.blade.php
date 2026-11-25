<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('نقاط للزبون بالمحل') }} | {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.store_points.till')
</head>
<body>
@include('admin.includes.sidebar')
@php($texts = [
    'added' => __('انضافت :points نقطة', ['points' => '{p}']),
    'for' => __('لـ :name', ['name' => '{n}']),
    'expired' => __('خلص وقت الكود'),
    'cancelled' => __('انلغى الكود'),
    'left' => __('الزبون بيمسحه بتطبيق أوزمان أو بكاميرا جواله · باقي :time', ['time' => '{t}']),
    'failed' => __('ما قدرنا نعمل الكود، جرّب كمان مرة.'),
])
<main class="till-page">
    <section class="hero">
        <div>
            <span class="eyebrow">{{ __('نقاط الولاء') }}</span>
            <h1>{{ __('نقاط للزبون بالمحل') }}</h1>
            <p>{{ __('اكتب مبلغ الشراء، وورّي الزبون الكود ليمسحه بتطبيق أوزمان أو بكاميرا جواله. النقاط بتنضاف لحسابه فوراً.') }}</p>
        </div>
        @if($shops->count() > 1)
        <form method="get">
            <select name="shop_id" onchange="this.form.submit()" aria-label="{{ __('المحل') }}">
                @foreach($shops as $option)<option value="{{ $option->id }}" @selected($shop->id === $option->id)>{{ $option->name }}</option>@endforeach
            </select>
        </form>
        @endif
    </section>

    <section class="panel" id="till" data-make="{{ route('store-points.make', $shop) }}" data-status="{{ url('/store-points/codes') }}">
        {{-- 1. The amount --}}
        <form class="step" data-step="amount" id="amountForm">
            <label for="amount">{{ __('مبلغ الشراء ₪') }}</label>
            <input id="amount" name="amount" type="number" inputmode="decimal" min="1" max="{{ $max }}" step="0.01" required autofocus>
            <p class="error" id="error" hidden></p>
            <button type="submit" class="cta"><i class="ti ti-qrcode" aria-hidden="true"></i> {{ __('اعمل الكود') }}</button>
            <p class="hint">{{ __('الكود بينفع مرة وحدة، لمدة :minutes دقايق.', ['minutes' => $minutes]) }}</p>
        </form>

        {{-- 2. The QR, waiting for the customer --}}
        <div class="step" data-step="qr" hidden>
            <div class="points"><b id="points"></b> {{ __('نقطة') }}</div>
            <div class="amount">{{ __('على شراء بـ') }} <span id="amountShown"></span> ₪</div>
            <div class="qr" id="qr" role="img" aria-label="{{ __('كود النقاط') }}"></div>
            <div class="timer" id="timer"></div>
            <div class="actions">
                <button type="button" class="ghost" id="cancel">{{ __('إلغاء الكود') }}</button>
            </div>
        </div>

        {{-- 3. Done (or expired) --}}
        <div class="step done" data-step="done" hidden>
            <i class="ti ti-circle-check" aria-hidden="true"></i>
            <h2 id="doneTitle"></h2>
            <p id="doneName"></p>
            <button type="button" class="cta" id="again"><i class="ti ti-plus" aria-hidden="true"></i> {{ __('زبون جديد') }}</button>
        </div>
    </section>
</main>
<script>
(() => {
    const box = document.getElementById('till');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const t = @json($texts);
    let code = null, poll = null, tick = null;
    const show = (step) => box.querySelectorAll('.step').forEach((el) => { el.hidden = el.dataset.step !== step; });
    const stop = () => { clearInterval(poll); clearInterval(tick); };
    const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' };

    // The server sends the QR as rows of 1 (dark) and 0 (light); drawn as one SVG path with a quiet zone.
    function drawQr(rows) {
        const n = rows.length, pad = 4, size = n + pad * 2;
        let d = '';
        rows.forEach((row, y) => { for (let x = 0; x < row.length; x++) if (row[x] === '1') d += `M${x + pad} ${y + pad}h1v1h-1z`; });
        document.getElementById('qr').innerHTML = `<svg viewBox="0 0 ${size} ${size}" shape-rendering="crispEdges"><rect width="${size}" height="${size}" fill="#fff"/><path d="${d}" fill="#000"/></svg>`;
    }

    function finish(fresh) {
        stop();
        const done = fresh.status === 'claimed';
        document.getElementById('doneTitle').textContent = done ? t.added.replace('{p}', fresh.points) : (fresh.status === 'cancelled' ? t.cancelled : t.expired);
        document.getElementById('doneName').textContent = done && fresh.claimed_by ? t.for.replace('{n}', fresh.claimed_by) : '';
        box.querySelector('.done').classList.toggle('ok', done);
        show('done');
    }

    async function check() {
        if (!code) return;
        try {
            const res = await fetch(`${box.dataset.status}/${code.id}`, { headers });
            if (!res.ok) return;
            const fresh = (await res.json()).code;
            if (fresh.status !== 'open') finish(fresh);
        } catch (_) { /* the next check comes in two seconds */ }
    }

    function countdown() {
        const left = Math.max(0, Math.round((new Date(code.expires_at) - Date.now()) / 1000));
        const mm = String(Math.floor(left / 60)).padStart(2, '0'), ss = String(left % 60).padStart(2, '0');
        document.getElementById('timer').textContent = t.left.replace('{t}', `${mm}:${ss}`);
        if (left === 0) check();
    }

    document.getElementById('amountForm').addEventListener('submit', async (event) => {
        event.preventDefault();
        const error = document.getElementById('error');
        error.hidden = true;
        const amount = document.getElementById('amount').value;
        try {
            const res = await fetch(box.dataset.make, { method: 'POST', headers, body: JSON.stringify({ amount }) });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                error.textContent = (json.errors && Object.values(json.errors)[0]?.[0]) || json.message || t.failed;
                error.hidden = false;
                return;
            }
            code = json.code;
            document.getElementById('points').textContent = code.points;
            document.getElementById('amountShown').textContent = Number(code.amount).toLocaleString('en');
            drawQr(code.qr || []);
            show('qr');
            countdown();
            tick = setInterval(countdown, 1000);
            poll = setInterval(check, 2000);
        } catch (_) {
            error.textContent = t.failed;
            error.hidden = false;
        }
    });

    document.getElementById('cancel').addEventListener('click', async () => {
        if (!code) return;
        stop();
        const res = await fetch(`${box.dataset.status}/${code.id}`, { method: 'DELETE', headers }).catch(() => null);
        finish(res && res.ok ? (await res.json()).code : { status: 'cancelled' });
    });

    document.getElementById('again').addEventListener('click', () => {
        code = null;
        document.getElementById('amount').value = '';
        show('amount');
        document.getElementById('amount').focus();
    });
})();
</script>
</body>
</html>
