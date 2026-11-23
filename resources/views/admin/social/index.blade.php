<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('السوشال ميديا') }} - {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.social.index')
</head>
<body>
@include('admin.includes.sidebar')
<main class="soc-page">
    @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="errors" role="alert">{{ $errors->first() }}</div>@endif

    <section class="hero">
        <div>
            <span class="eyebrow">التسويق</span>
            <h1>{{ __('السوشال ميديا') }} · {{ $shop->name }}</h1>
            <p>{{ __('اربط إنستغرام وفيسبوك وتيك توك، وخلّي المساعد الذكي يكتب منشوراتك عن منتجاتك وينشرها بأفضل وقت. كل منشور فيه رابط بيفتح المنتج على أوزمان عشان الزبون يطلب فوراً.') }}</p>
        </div>
        @if($shops->count() > 1)
            <form method="GET" action="{{ route('social.index') }}"><select name="shop_id" onchange="this.form.submit()" aria-label="{{ __('المحل') }}">@foreach($shops as $s)<option value="{{ $s->id }}" @selected($s->id === $shop->id)>{{ $s->name }}</option>@endforeach</select></form>
        @endif
    </section>

    @if(! $configured)
        <div class="warn" style="margin-top:16px">@if(auth()->user()?->isSuperAdmin()){{ __('خدمة النشر لسا مش مفعّلة على السيرفر. لازم يكون ZERNIO_API_KEY بملف .env.') }}@else{{ __('النشر التلقائي على السوشال ميديا مش متاح حالياً. إدارة أوزمان رح تفعّله قريباً.') }}@endif</div>
    @endif
    @if($apiError)<div class="errors" style="margin-top:16px">@if(auth()->user()?->isSuperAdmin()){{ __('ما قدرنا نجيب الحسابات: :error', ['error' => $apiError]) }}@else{{ __('ما قدرنا نجيب حساباتك هلق. جرّب كمان شوي.') }}@endif</div>@endif

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
                <label class="check" style="width:max-content;margin:-4px 0 14px"><input type="checkbox" name="add_link" value="1" checked> {{ __('ضيف رابط الطلب تحت المنشور') }}</label>
                <p class="hint" style="margin:-8px 0 14px">{{ __('بفيسبوك الرابط بينكبس. إنستغرام وتيك توك ما بيسمحوا بروابط بالمنشور، فبنكتب «الرابط بالبايو»، وحط رابط محلك بالبايو:') }} <span dir="ltr" style="color:var(--cyan);user-select:all">{{ $shop->publicUrl() }}</span></p>

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
