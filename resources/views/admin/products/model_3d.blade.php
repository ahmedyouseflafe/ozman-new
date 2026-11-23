<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('عرض ثلاثي الأبعاد') }} - {{ $product->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.products.model_3d')
</head>
<body>
@include('admin.includes.sidebar')
<main class="m3-page">
    <section class="card">
        <h1><i class="ti ti-cube-3d-sphere" aria-hidden="true"></i>{{ __('عرض ثلاثي الأبعاد') }}</h1>
        <p>{{ __(':product — الزبون بيشوف القطعة بغرفته بحجمها الحقيقي من صفحة المنتج.', ['product' => $product->name]) }}</p>
        <div class="row"><a class="btn" href="{{ route('products.edit', $product) }}"><i class="ti ti-arrow-back-up"></i>{{ __('ارجع للمنتج') }}</a></div>
    </section>

    @if (! $state['available'])
        <section class="card"><p>{{ __('نماذج الـ 3D متاحة لمحلات الأثاث.') }}</p></section>
    @else
    <section class="card" id="current">
        <h2>{{ __('النموذج الحالي') }}</h2>
        <div id="currentBody"></div>
    </section>

    <section class="card">
        <h2>{{ __('المقاسات الحقيقية') }}</h2>
        <p>{{ __('بالسنتيمتر. منكبّر أو منصغّر النموذج عليها، فبتطلع القطعة بالغرفة بحجمها الصح.') }}</p>
        <form id="sizeForm">
            <div class="sizes">
                <label>{{ __('العرض (سم)') }}<input type="number" name="width" min="1" max="2000" step="0.1" value="{{ $state['size']['w'] ?? '' }}"></label>
                <label>{{ __('العمق (سم)') }}<input type="number" name="depth" min="1" max="2000" step="0.1" value="{{ $state['size']['d'] ?? '' }}"></label>
                <label>{{ __('الارتفاع (سم)') }}<input type="number" name="height" min="1" max="2000" step="0.1" value="{{ $state['size']['h'] ?? '' }}"></label>
            </div>
            <div class="row"><button class="btn" type="submit"><i class="ti ti-ruler-measure"></i>{{ __('احفظ المقاسات') }}</button></div>
        </form>
    </section>

    <section class="card">
        <h2>{{ __('ارفع ملف 3D') }}</h2>
        <p>{{ __('إذا عندك ملف .glb من المصنع أو من مصمم، هو الأدق. لحد 25 ميغا.') }}</p>
        <form id="uploadForm" class="row">
            <input type="file" name="model" accept=".glb,model/gltf-binary" required>
            <button class="btn" type="submit"><i class="ti ti-upload"></i>{{ __('ارفع') }}</button>
        </form>
    </section>

    <section class="card" id="convertCard">
        <h2>{{ __('أو اعمله من صورة المنتج') }}</h2>
        <p>{{ __('الذكاء الاصطناعي بيعمل نموذج من الصورة الرئيسية للمنتج. بتطلع أحسن من صورة للقطعة لحالها على خلفية سادة. بتشوف النتيجة قبل ما تظهر للزباين.') }}</p>
        <div id="convertBody"></div>
    </section>
    <div class="msg" id="msg" role="status"></div>
    @endif
</main>
@if ($state['available'])
<script type="module" src="https://unpkg.com/@google/model-viewer@4.4.0/dist/model-viewer.min.js"></script>
<script>
(() => {
    let state = @json($state);
    const T = {
        none: @json(__('لسا ما في نموذج لهالمنتج.')), view: @json(__('صفحة الزبون')), remove: @json(__('احذف النموذج')),
        convert: @json(__('حوّل الصورة لـ 3D')), off: @json(__('تحويل الصور لـ 3D مش مفعّل لسا. بتقدر ترفع ملف .glb.')),
        working: @json(__('عم بنعمل النموذج… بياخد تقريباً دقيقتين، وبتقدر تسكّر الصفحة وترجع.')), ready: @json(__('جاهز! شوفه، وإذا عجبك اعتمده.')),
        approve: @json(__('اعتمده للزباين')), discard: @json(__('ما بدي إياه')), again: @json(__('جرّب مرة ثانية')), confirmRemove: @json(__('أكيد بدك تحذف النموذج؟')),
    };
    const urls = {
        state: @json(route('products.model3d.state', $product)), upload: @json(route('products.model3d.upload', $product)),
        size: @json(route('products.model3d.size', $product)), convert: @json(route('products.model3d.convert', $product)),
        remove: @json(route('products.model3d.destroy', $product)), job: @json(url('/product-3d-jobs')),
    };
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const msg = document.getElementById('msg');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const viewer = (src) => `<model-viewer src="${esc(src)}" camera-controls auto-rotate shadow-intensity="1"></model-viewer>`;
    let timer = null;

    async function call(url, method = 'POST', body = null) {
        msg.className = 'msg'; msg.textContent = '';
        const r = await fetch(url, {method, credentials: 'same-origin', headers: {Accept: 'application/json', 'X-CSRF-TOKEN': csrf}, body});
        const data = await r.json().catch(() => ({}));
        if (!r.ok) { msg.className = 'msg bad'; msg.textContent = Object.values(data.errors || {}).flat()[0] || data.message || 'Error'; return; }
        state = data.model_3d; render(); if (data.message) msg.textContent = data.message;
    }

    function render() {
        document.getElementById('currentBody').innerHTML = state.model
            ? `${viewer(state.model)}<div class="row"><a class="btn" href="${esc(state.ar_url)}" target="_blank" rel="noopener"><i class="ti ti-external-link"></i>${esc(T.view)}</a><button class="btn btn-danger" type="button" id="removeBtn"><i class="ti ti-trash"></i>${esc(T.remove)}</button></div>`
            : `<div class="empty">${esc(T.none)}</div>`;
        document.getElementById('removeBtn')?.addEventListener('click', () => { if (confirm(T.confirmRemove)) call(urls.remove, 'DELETE'); });

        const job = state.job, box = document.getElementById('convertBody');
        if (job && (job.status === 'queued' || job.status === 'running')) {
            box.innerHTML = `<div class="status">${esc(T.working)}<div class="bar"><i style="width:${Math.max(5, job.progress)}%"></i></div></div>`;
            clearTimeout(timer); timer = setTimeout(() => call(urls.state, 'GET'), 6000);
        } else if (job && job.status === 'ready') {
            box.innerHTML = `<div class="status">${esc(T.ready)}</div>${viewer(job.preview)}<div class="row"><button class="btn btn-main" id="approveBtn" type="button"><i class="ti ti-check"></i>${esc(T.approve)}</button><button class="btn" id="discardBtn" type="button">${esc(T.discard)}</button></div>`;
            document.getElementById('approveBtn').onclick = () => call(`${urls.job}/${job.id}/approve`);
            document.getElementById('discardBtn').onclick = () => call(`${urls.job}/${job.id}/discard`);
        } else if (state.can_convert) {
            box.innerHTML = (job && job.status === 'failed' ? `<div class="status bad">${esc(job.error)}</div>` : '')
                + `<div class="row"><button class="btn btn-main" id="convertBtn" type="button"><i class="ti ti-wand"></i>${esc(job && job.status === 'failed' ? T.again : T.convert)}</button></div>`;
            document.getElementById('convertBtn').onclick = (e) => { e.target.disabled = true; call(urls.convert); };
        } else {
            box.innerHTML = `<p>${esc(T.off)}</p>`;
        }
    }

    document.getElementById('sizeForm').addEventListener('submit', (e) => { e.preventDefault(); call(urls.size, 'POST', new FormData(e.target)); });
    document.getElementById('uploadForm').addEventListener('submit', (e) => { e.preventDefault(); call(urls.upload, 'POST', new FormData(e.target)); });
    render();
})();
</script>
@endif
</body>
</html>
