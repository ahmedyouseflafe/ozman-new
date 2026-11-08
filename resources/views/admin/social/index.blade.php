<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('السوشال ميديا') }} - {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root{--bg:#071118;--panel:#0c1b24;--line:#1d3b49;--cyan:#16d3ee;--green:#55e9a0;--gold:#ffd166;--red:#ff9aae;--muted:#9eb4c2;--ig:linear-gradient(45deg,#f9a825,#e1306c,#833ab4);--fb:#1877f2;--tt:#111}*{box-sizing:border-box}
        body{margin:0;background:radial-gradient(circle at 85% 0,rgba(225,48,108,.12),transparent 30%),var(--bg);color:#f7fbff;font-family:Cairo,Arial,sans-serif}
        .soc-page{width:min(1200px,calc(100% - 310px));margin:0 270px 0 auto;padding:28px 0 60px}
        .panel,.hero{border:1px solid var(--line);border-radius:24px;background:linear-gradient(145deg,rgba(15,37,48,.96),rgba(7,19,26,.98));box-shadow:0 16px 50px rgba(0,0,0,.2)}
        .hero{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:24px;flex-wrap:wrap}
        .eyebrow{color:#ff8fb6;font-size:11px;font-weight:900;letter-spacing:.08em}.hero h1{margin:4px 0;font-size:28px}.hero p{margin:0;color:var(--muted);font-size:13px;max-width:640px}
        select,input,textarea{font:inherit;color:#f7fbff;background:#081720;border:1px solid var(--line);border-radius:12px;padding:10px 12px}
        textarea{width:100%;min-height:150px;resize:vertical;line-height:1.7}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 16px;border:1px solid #276377;border-radius:12px;background:#102b37;color:#e8fbff;font:inherit;font-size:13px;font-weight:900;text-decoration:none;cursor:pointer}
        .btn:disabled{opacity:.55;cursor:wait}
        .btn-main{border:0;background:linear-gradient(135deg,#ff5f9e,#8b5cf6);color:#fff}.btn-ai{border:0;background:linear-gradient(135deg,#3fe0a0,#16b5d6);color:#04202a}
        .btn-ig{border:0;background:var(--ig);color:#fff}.btn-fb{border:0;background:var(--fb);color:#fff}.btn-tt{border:1px solid #444;background:var(--tt);color:#fff}
        .notice{margin:0 0 14px;padding:12px 14px;border:1px solid #4fcf9c;border-radius:12px;background:rgba(63,198,144,.1);color:#8affc5;font-size:13px}
        .errors{margin:0 0 14px;padding:12px 14px;border:1px solid #a0475a;border-radius:12px;background:rgba(160,71,90,.12);color:#ffb1c0;font-size:13px}
        .warn{padding:14px 16px;border:1px solid #6a5420;border-radius:16px;background:rgba(56,44,14,.35);color:#ffe3a3;font-size:13px;line-height:1.8}
        h2{margin:0 0 12px;font-size:18px;display:flex;align-items:center;gap:8px}
        .grid{display:grid;grid-template-columns:1.35fr 1fr;gap:16px;margin-top:16px}.grid>section{padding:20px}
        .accounts{display:grid;gap:8px;margin-bottom:14px}.acc{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--line);border-radius:14px;background:#081720}
        .acc img,.acc .ph{width:38px;height:38px;border-radius:50%;object-fit:cover;background:#16303c;display:grid;place-items:center}
        .acc b{display:block;font-size:14px}.acc small{color:var(--muted)}.acc .st{margin-inline-start:auto;font-size:12px;font-weight:800}
        .pf{display:inline-grid;place-items:center;width:22px;height:22px;border-radius:7px;font-size:13px;color:#fff}.pf-instagram{background:var(--ig)}.pf-facebook{background:var(--fb)}.pf-tiktok{background:#000;border:1px solid #444}
        .connect{display:flex;gap:8px;flex-wrap:wrap}
        .field{display:grid;gap:6px;margin-bottom:14px}.field>span{font-size:13px;font-weight:800;color:#cfe3ee}
        .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
        .picked{display:flex;gap:12px;align-items:center;padding:10px;border:1px dashed var(--line);border-radius:14px}.picked[hidden]{display:none}.picked img{width:72px;height:72px;border-radius:12px;object-fit:cover}
        .uploads{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}.uploads img,.uploads video{width:84px;height:84px;border-radius:12px;object-fit:cover;border:1px solid var(--line);background:#000}
        .drop{display:flex;align-items:center;gap:12px;padding:14px;border:1px dashed #6b3a5a;border-radius:14px;background:rgba(255,95,158,.06);cursor:pointer}.drop i{font-size:26px;color:#ff8fb6}.drop b{display:block;font-size:13.5px}.drop input{display:none}
        .checks{display:flex;gap:8px;flex-wrap:wrap}.check{display:flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid var(--line);border-radius:12px;background:#081720;cursor:pointer;font-size:13px}
        .check input{accent-color:#ff5f9e;width:17px;height:17px;padding:0}
        .chips{display:flex;gap:8px;flex-wrap:wrap}.chip{padding:8px 12px;border:1px solid #6b3a5a;border-radius:999px;background:rgba(255,95,158,.1);color:#ffd0e2;font:inherit;font-size:12.5px;font-weight:800;cursor:pointer}
        .chip.on{background:linear-gradient(135deg,#ff5f9e,#8b5cf6);border-color:transparent;color:#fff}
        .hint{color:var(--muted);font-size:12px;margin:0}
        .posts{display:grid;gap:10px}.post{padding:12px 14px;border:1px solid var(--line);border-radius:14px;background:#081720}
        .post-head{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;color:var(--muted)}.post p{margin:8px 0 0;font-size:13px;white-space:pre-line;color:#dbe9f1}
        .badge{padding:3px 9px;border-radius:999px;font-size:11.5px;font-weight:900}.b-scheduled{background:rgba(255,209,102,.15);color:var(--gold)}.b-published{background:rgba(85,233,160,.14);color:var(--green)}.b-failed,.b-partial{background:rgba(255,154,174,.14);color:var(--red)}.b-cancelled{background:#16303c;color:var(--muted)}.b-publishing{background:rgba(22,211,238,.14);color:var(--cyan)}
        .post form{margin-inline-start:auto}.link-btn{border:0;background:none;color:var(--red);font:inherit;font-size:12px;font-weight:800;cursor:pointer}
        .empty{color:var(--muted);font-size:13px;text-align:center;padding:20px}
        @media(max-width:1050px){.soc-page{width:calc(100% - 28px);margin:auto;padding-top:78px}.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
@include('admin.includes.sidebar')
<main class="soc-page">
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="errors" role="alert">{{ $errors->first() }}</div>@endif

    <section class="hero">
        <div>
            <span class="eyebrow">SOCIAL MEDIA MANAGER</span>
            <h1>{{ __('السوشال ميديا') }} · {{ $shop->name }}</h1>
            <p>{{ __('اربط إنستغرام وفيسبوك وتيك توك، وخلّي المساعد الذكي يكتب منشوراتك عن منتجاتك وينشرها بأفضل وقت. كل منشور فيه رابط بيفتح المنتج على أوزمان عشان الزبون يطلب فوراً.') }}</p>
        </div>
        @if($shops->count() > 1)
            <form method="GET" action="{{ route('social.index') }}"><select name="shop_id" onchange="this.form.submit()" aria-label="{{ __('المحل') }}">@foreach($shops as $s)<option value="{{ $s->id }}" @selected($s->id === $shop->id)>{{ $s->name }}</option>@endforeach</select></form>
        @endif
    </section>

    @if(! $configured)
        <div class="warn" style="margin-top:16px">{{ __('خدمة النشر لسا مش مفعّلة على السيرفر. لازم يكون ZERNIO_API_KEY بملف .env.') }}</div>
    @endif
    @if($apiError)<div class="errors" style="margin-top:16px">{{ __('ما قدرنا نجيب الحسابات: :error', ['error' => $apiError]) }}</div>@endif

    <div class="grid">
        <section class="panel">
            <h2><i class="ti ti-sparkles" aria-hidden="true"></i> {{ __('منشور جديد') }}</h2>
            @if(empty($accounts))
                <p class="empty">{{ __('اربط حساب واحد على الأقل (على اليسار) عشان تبلّش تنشر.') }}</p>
            @else
            <form method="POST" action="{{ route('social.posts.store', ['shop_id' => $shop->id]) }}" id="composer" enctype="multipart/form-data">
                @csrf
                <label class="field"><span>{{ __('المنتج') }}</span>
                    <select name="product_id" id="productSelect">
                        <option value="">{{ __('— بدون منتج (منشور عام) —') }}</option>
                        @foreach($products as $p)<option value="{{ $p->id }}" data-image="{{ $p->main_image ? (\Illuminate\Support\Str::startsWith($p->main_image, ['http://','https://']) ? $p->main_image : asset(ltrim($p->main_image, '/'))) : '' }}" @selected((string) old('product_id') === (string) $p->id)>{{ $p->localized('name') }} — {{ number_format((float) ($p->discount_price ?: $p->price), 2) }} ₪</option>@endforeach
                    </select>
                </label>
                <div class="picked" id="picked" hidden><img id="pickedImg" alt=""><p class="hint">{{ __('صورة المنتج من المنيو. بتنزل مع المنشور إذا ما رفعت صورة خاصة.') }}</p></div>

                <div class="field" style="margin-top:14px"><span>{{ __('صورك أو الفيديو تبعك (اختياري)') }}</span>
                    <label class="drop"><i class="ti ti-photo-plus" aria-hidden="true"></i><span><b>{{ __('ارفع صور (لحد 10) أو فيديو واحد') }}</b><small class="hint">{{ __('JPG أو PNG أو WebP أو MP4. إذا رفعت، بتنزل بدل صورة المنيو.') }}</small></span>
                        <input type="file" name="media[]" id="mediaInput" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime" multiple></label>
                    <div class="uploads" id="uploads"></div>
                </div>

                <div class="field" style="margin-top:14px"><span>{{ __('خلّي المساعد يكتب') }}</span>
                    <div class="row">
                        <select id="captionLang" aria-label="{{ __('لغة المنشور') }}"><option value="ar">عربي</option><option value="he">עברית</option><option value="en">English</option></select>
                        <input id="captionIdea" maxlength="500" placeholder="{{ __('فكرة اختيارية: عرض الويكند، وجبة جديدة…') }}" style="flex:1;min-width:180px">
                        <button type="button" class="btn btn-ai" id="writeBtn" @disabled(! $writerReady)><i class="ti ti-wand" aria-hidden="true"></i> {{ __('اكتبلي المنشور') }}</button>
                    </div>
                    @unless($writerReady)<p class="hint">{{ __('الكاتب الذكي بحاجة لـ ANTHROPIC_API_KEY على السيرفر. بتقدر تكتب المنشور بإيدك.') }}</p>@endunless
                    <p class="hint" id="writeStatus" role="status"></p>
                </div>

                <label class="field"><span>{{ __('نص المنشور') }}</span>
                    <textarea name="caption" id="caption" required maxlength="2000" placeholder="{{ __('اكتب المنشور هون، أو كبس «اكتبلي المنشور»') }}">{{ old('caption') }}</textarea>
                </label>
                <label class="check" style="width:max-content;margin:-4px 0 14px"><input type="checkbox" name="add_link" value="1" checked> {{ __('ضيف رابط الطلب للمنتج تحت المنشور') }}</label>

                <div class="field"><span>{{ __('وين ينتشر؟') }}</span>
                    <div class="checks">
                        @foreach($accounts as $a)
                            <label class="check" @unless($a['active']) style="opacity:.5" title="{{ __('بدو إعادة ربط') }}" @endunless><input type="checkbox" name="accounts[]" value="{{ $a['id'] }}" @disabled(! $a['active']) @checked($a['active'] && (in_array($a['id'], old('accounts', []), true) || ! old('accounts')))><span class="pf pf-{{ $a['platform'] }}"><i class="ti ti-brand-{{ $a['platform'] }}" aria-hidden="true"></i></span> {{ $a['username'] ? '@'.$a['username'] : ($a['name'] ?: $a['platform']) }}</label>
                        @endforeach
                    </div>
                </div>

                <div class="field"><span>{{ __('إمتى؟') }}</span>
                    <div class="checks">
                        <label class="check"><input type="radio" name="when" value="schedule" @checked(old('when', 'schedule') === 'schedule')> {{ __('جدولة') }}</label>
                        <label class="check"><input type="radio" name="when" value="now" @checked(old('when') === 'now')> {{ __('انشر هلقيت') }}</label>
                    </div>
                    <div id="scheduleBox">
                        <p class="hint" style="margin:6px 0">@if(collect($suggestions)->contains('learned', true)){{ __('🔥 أفضل أوقات حسب تفاعل جمهورك:') }}@else{{ __('أوقات مقترحة (رح تصير أدق لما يصير عندك منشورات):') }}@endif</p>
                        <div class="chips">@foreach($suggestions as $s)<button type="button" class="chip" data-at="{{ $s['value'] }}">{{ $s['label'] }}</button>@endforeach</div>
                        <input type="datetime-local" name="scheduled_at" id="scheduledAt" value="{{ old('scheduled_at', $suggestions[0]['value'] ?? '') }}" style="margin-top:10px">
                        <p class="hint">{{ __('التوقيت حسب :tz.', ['tz' => $timezone]) }}</p>
                    </div>
                </div>
                <button class="btn btn-main" type="submit"><i class="ti ti-send" aria-hidden="true"></i> {{ __('اعتمد المنشور') }}</button>
            </form>
            @endif
        </section>

        <div style="display:grid;gap:16px;align-content:start">
            <section class="panel" style="padding:20px">
                <h2><i class="ti ti-plug-connected" aria-hidden="true"></i> {{ __('الحسابات المربوطة') }}</h2>
                <div class="accounts">
                    @forelse($accounts as $a)
                        <div class="acc">@if($a['picture'])<img src="{{ $a['picture'] }}" alt="">@else<span class="ph"><i class="ti ti-user" aria-hidden="true"></i></span>@endif
                            <div><b>{{ $a['name'] ?: $a['username'] }}</b><small><span class="pf pf-{{ $a['platform'] }}"><i class="ti ti-brand-{{ $a['platform'] }}" aria-hidden="true"></i></span> {{ $a['username'] ? '@'.$a['username'] : $a['platform'] }}</small></div>
                            <span class="st" style="color: {{ $a['active'] ? 'var(--green)' : 'var(--red)' }}">{{ $a['active'] ? __('شغّال') : __('بدو إعادة ربط') }}</span></div>
                    @empty
                        <p class="empty">{{ __('لسا ما في حسابات مربوطة.') }}</p>
                    @endforelse
                </div>
                @if($configured)
                <div class="connect">
                    <a class="btn btn-ig" href="{{ route('social.connect', ['platform' => 'instagram', 'shop_id' => $shop->id]) }}"><i class="ti ti-brand-instagram" aria-hidden="true"></i> {{ __('اربط إنستغرام') }}</a>
                    <a class="btn btn-fb" href="{{ route('social.connect', ['platform' => 'facebook', 'shop_id' => $shop->id]) }}"><i class="ti ti-brand-facebook" aria-hidden="true"></i> {{ __('اربط فيسبوك') }}</a>
                    <a class="btn btn-tt" href="{{ route('social.connect', ['platform' => 'tiktok', 'shop_id' => $shop->id]) }}"><i class="ti ti-brand-tiktok" aria-hidden="true"></i> {{ __('اربط تيك توك') }}</a>
                </div>
                <p class="hint" style="margin-top:10px">{{ __('إنستغرام لازم يكون حساب احترافي (Business أو Creator). بتسجّل دخول عند المنصة نفسها، وإحنا ما بنشوف كلمة السر.') }}</p>
                @endif
            </section>

            <section class="panel" style="padding:20px">
                <h2><i class="ti ti-calendar-event" aria-hidden="true"></i> {{ __('المنشورات') }}</h2>
                <div class="posts">
                    @forelse($posts as $post)
                        @php($label = ['scheduled' => 'مجدول', 'published' => 'انتشر', 'publishing' => 'عم ينتشر', 'partial' => 'انتشر جزئياً', 'failed' => 'فشل', 'cancelled' => 'ملغي', 'draft' => 'مسودة', 'pending_approval' => 'بانتظار الموافقة'][$post->status] ?? $post->status)
                        <article class="post">
                            <div class="post-head">
                                <span class="badge b-{{ $post->status }}">{{ __($label) }}</span>
                                @foreach($post->targets as $t)<span class="pf pf-{{ $t['platform'] }}" title="{{ $t['username'] ?? '' }}"><i class="ti ti-brand-{{ $t['platform'] }}" aria-hidden="true"></i></span>@endforeach
                                <span>{{ ($post->scheduled_for ?? $post->created_at)->timezone($timezone)->format('d/m H:i') }}</span>
                                @if($post->status === 'scheduled')
                                    <form method="POST" action="{{ route('social.posts.destroy', ['post' => $post, 'shop_id' => $shop->id]) }}">@csrf @method('DELETE')<button class="link-btn" type="submit">{{ __('إلغاء') }}</button></form>
                                @endif
                            </div>
                            <p>{{ \Illuminate\Support\Str::limit($post->caption, 180) }}</p>
                            @if($post->error)<p style="color:var(--red)">{{ $post->error }}</p>@endif
                        </article>
                    @empty
                        <p class="empty">{{ __('أول منشور إلك رح يبين هون.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</main>
<script>
(() => {
    const select = document.getElementById('productSelect');
    if (!select) return;
    const picked = document.getElementById('picked'), pickedImg = document.getElementById('pickedImg');
    const mediaInput = document.getElementById('mediaInput'), uploads = document.getElementById('uploads');
    const showPicked = () => { const img = select.selectedOptions[0]?.dataset.image; picked.hidden = !img || (mediaInput && mediaInput.files.length > 0); if (img) pickedImg.src = img; };
    mediaInput?.addEventListener('change', () => {
        uploads.replaceChildren(...[...mediaInput.files].slice(0, 10).map((f) => {
            const el = document.createElement(f.type.startsWith('video/') ? 'video' : 'img');
            el.src = URL.createObjectURL(f); if (el.tagName === 'VIDEO') { el.muted = true; el.playsInline = true; }
            return el;
        }));
        showPicked();
    });
    select.addEventListener('change', showPicked); showPicked();

    const writeBtn = document.getElementById('writeBtn'), status = document.getElementById('writeStatus'), caption = document.getElementById('caption');
    writeBtn?.addEventListener('click', async () => {
        if (!select.value && !document.getElementById('captionIdea').value.trim()) { status.textContent = @json(__('اختار منتج أو اكتب فكرة.')); return; }
        writeBtn.disabled = true; status.textContent = @json(__('عم بكتب… ✍️'));
        try {
            const r = await fetch(@json(route('social.caption', ['shop_id' => $shop->id])), { method: 'POST', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ product_id: select.value || null, language: document.getElementById('captionLang').value, idea: document.getElementById('captionIdea').value || null }) });
            const data = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(data.message || r.status);
            caption.value = data.caption; status.textContent = @json(__('جاهز! عدّل عليه إذا بدك.'));
        } catch (e) { status.textContent = e.message; }
        finally { writeBtn.disabled = false; }
    });

    const at = document.getElementById('scheduledAt'), box = document.getElementById('scheduleBox');
    const chips = [...document.querySelectorAll('.chip')];
    const mark = () => chips.forEach((c) => c.classList.toggle('on', c.dataset.at === at.value));
    chips.forEach((c) => c.addEventListener('click', () => { at.value = c.dataset.at; document.querySelector('input[name=when][value=schedule]').checked = true; toggle(); mark(); }));
    at.addEventListener('input', mark); mark();
    const toggle = () => { box.hidden = document.querySelector('input[name=when]:checked')?.value !== 'schedule'; };
    document.querySelectorAll('input[name=when]').forEach((r) => r.addEventListener('change', toggle)); toggle();
})();
</script>
</body>
</html>
