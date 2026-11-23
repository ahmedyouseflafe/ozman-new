<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Support\AssistantPersona::name() }} - {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    @viewStyle('admin.assistant.index')
</head>
<body>
@include('admin.includes.sidebar')
<main class="as-page">
    <section class="hero">
        <div class="hero-who">
            @include('front.partials.assistant_avatar', ['size' => 64])
            <div>
            <h1>{{ \App\Support\AssistantPersona::name() }} · {{ __('مساعدتك الذكية') }}</h1>
            <p>{{ __('اسأله عن مبيعاتك وطلباتك ومنتجاتك، خلّيه يكتبلك منشورات ووصف منتجات، أو يجهّزلك تغيير وإنت بتأكّده.') }}</p>
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
            <span class="usage" id="usage"></span>
            <button class="btn btn-ghost" type="button" id="voiceBtn" aria-pressed="false" title="{{ __('اقرأ الردود بصوت') }}"><i class="ti ti-volume" aria-hidden="true"></i></button>
            <button class="btn btn-ghost" type="button" id="resetBtn"><i class="ti ti-plus" aria-hidden="true"></i>{{ __('محادثة جديدة') }}</button>
        </div>
    </section>
    @include('front.partials.assistant_stage')
    @if(! $state['enabled'])
        <div class="warn">@if(auth()->user()?->isSuperAdmin()){{ __('المساعد مش مفعّل: لازم مفتاح ANTHROPIC_API_KEY بملف .env.') }}@else{{ __('المساعد مش متاح هلقيت. إدارة أوزمان رح تفعّله قريباً.') }}@endif</div>
    @endif

    <div class="chat" id="chat" aria-live="polite"></div>

    <form class="composer" id="composer">
        <label class="attach" id="attach" title="{{ __('صورة منتج') }}"><i class="ti ti-photo" aria-hidden="true"></i><input type="file" id="image" accept="image/jpeg,image/png,image/webp"></label>
        <textarea id="input" rows="1" placeholder="{{ __('اكتب سؤالك… مثلاً: كم بعت هالأسبوع؟') }}" aria-label="{{ __('رسالتك') }}"></textarea>
        <button class="attach" type="button" id="micBtn" title="{{ __('احكي بدل ما تكتب') }}" aria-label="{{ __('احكي بدل ما تكتب') }}" hidden><i class="ti ti-microphone" aria-hidden="true"></i></button>
        <button class="btn btn-main" type="submit" id="sendBtn" @disabled(! $state['enabled'])><i class="ti ti-send" aria-hidden="true"></i></button>
    </form>
    <div class="error" id="error" role="alert"></div>
    <div class="note">{{ __('المساعد بيعتمد على بيانات محلك بس، وما بيغيّر إشي إلا لما تكبس «أكّد».') }}</div>
</main>
<script>{!! file_get_contents(public_path('assistant-voice.js')) !!}</script>
<script>
(() => {
    const state = @json($state);
    const T = {
        confirm: @json(__('أكّد')), cancel: @json(__('إلغاء')), thinking: @json(__('عم بفكّر… 🤔')), copy: @json(__('نسخ')), copied: @json(__('تم النسخ ✓')),
        image: @json(__('📷 صورة')), done: @json(__('تم')), cancelled: @json(__('انلغى')), expired: @json(__('انتهت صلاحيته، اطلبه من جديد')),
        hello: @json(__('أهلاً! أنا مساعد :shop', ['shop' => $shop->name])), intro: @json(__('بقدر أحكيلك كيف ماشي البيع، مين أحسن زباينك، شو المنتجات اللي واقفة، وأكتبلك منشورات ووصف منتجات. جرّب:')),
        used: @json(__('أسئلة هالشهر')), failed: @json(__('صار خطأ، جرّب كمان مرة.')),
    };
    const urls = {
        send: @json(route('shop-assistant.send', $shop)), reset: @json(route('shop-assistant.reset', $shop)),
        confirm: @json(url('/assistant-actions/__ID__/confirm')), cancel: @json(url('/assistant-actions/__ID__/cancel')),
    };
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const chat = document.getElementById('chat'), input = document.getElementById('input'), form = document.getElementById('composer');
    const sendBtn = document.getElementById('sendBtn'), error = document.getElementById('error'), image = document.getElementById('image');
    let conversationId = state.conversation_id;

    const esc = (s) => s.replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const inline = (s) => esc(s).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>')
        .replace(/(https?:\/\/[^\s<]+[^\s<.,،)])/g, (u) => u.startsWith(location.origin) ? `<a href="${u}">${u.replace(location.origin, '')}</a>` : u);
    const render = (text) => {
        let html = '', list = false;
        for (const line of text.split('\n')) {
            const item = line.match(/^\s*(?:[-•*]|\d+[.)])\s+(.*)$/);
            if (item) { if (!list) { html += '<ul>'; list = true; } html += `<li>${inline(item[1])}</li>`; continue; }
            if (list) { html += '</ul>'; list = false; }
            if (/^#{1,3}\s+/.test(line)) html += `<h3>${inline(line.replace(/^#{1,3}\s+/, ''))}</h3>`;
            else if (line.trim()) html += `<p>${inline(line)}</p>`;
        }
        return html + (list ? '</ul>' : '');
    };
    const post = async (url, body) => {
        const r = await fetch(url, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(body instanceof FormData ? {} : {'Content-Type': 'application/json'}) },
            body: body instanceof FormData ? body : JSON.stringify(body || {}) });
        const data = await r.json().catch(() => ({}));
        return { ok: r.ok, data };
    };
    const usage = (u) => { document.getElementById('usage').innerHTML = `${T.used}: <b>${u.used}</b> / ${u.limit}`; };

    const actionCard = (a) => {
        const box = document.createElement('div');
        box.className = `act ${a.status}`;
        const sum = document.createElement('div'); sum.className = 'sum'; sum.textContent = a.summary; box.append(sum);
        const row = document.createElement('div'); row.className = 'row'; box.append(row);
        if (a.status === 'pending') {
            const ok = Object.assign(document.createElement('button'), { type: 'button', className: 'btn btn-ok', textContent: T.confirm });
            const no = Object.assign(document.createElement('button'), { type: 'button', className: 'btn btn-ghost', textContent: T.cancel });
            ok.onclick = async () => { ok.disabled = no.disabled = true; const r = await post(urls.confirm.replace('__ID__', a.id));
                box.replaceWith(actionCard({...(r.data.action || a), result: r.data.message || T.failed, status: r.data.action?.status || 'failed'})); };
            no.onclick = async () => { ok.disabled = no.disabled = true; const r = await post(urls.cancel.replace('__ID__', a.id)); box.replaceWith(actionCard(r.data.action || {...a, status: 'cancelled'})); };
            row.append(ok, no);
        } else {
            const res = document.createElement('span'); res.className = 'res';
            res.textContent = a.status === 'cancelled' ? T.cancelled : a.status === 'expired' ? T.expired : (a.result || (a.status === 'done' ? T.done : ''));
            row.append(res);
        }
        return box;
    };
    const bubble = (m) => {
        const div = document.createElement('div');
        div.className = `msg ${m.role}`;
        if (m.role === 'assistant') {
            div.innerHTML = render(m.content);
            (m.actions || []).forEach((a) => div.append(actionCard(a)));
            const copy = Object.assign(document.createElement('button'), { type: 'button', className: 'copy', textContent: T.copy });
            copy.onclick = () => navigator.clipboard?.writeText(m.content).then(() => { copy.textContent = T.copied; setTimeout(() => copy.textContent = T.copy, 1500); });
            div.append(copy);
        } else {
            if (m.has_image) div.innerHTML = `<div class="img-tag">${T.image}</div>`;
            div.insertAdjacentHTML('beforeend', `<p>${esc(m.content)}</p>`);
        }
        chat.append(div);
        chat.scrollTop = chat.scrollHeight;
    };
    const welcome = () => {
        chat.innerHTML = `<div class="welcome"><i class="ti ti-sparkles"></i><b>${esc(T.hello)}</b>${esc(T.intro)}<div class="chips"></div></div>`;
        const chips = chat.querySelector('.chips');
        state.suggestions.forEach((s) => { const c = Object.assign(document.createElement('button'), { type: 'button', className: 'chip', textContent: s }); c.onclick = () => send(s); chips.append(c); });
    };

    async function send(text) {
        text = (text ?? input.value).trim();
        const file = image.files[0];
        if ((!text && !file) || sendBtn.disabled) return;
        error.textContent = '';
        if (chat.querySelector('.welcome')) chat.innerHTML = '';
        bubble({ role: 'user', content: text || '', has_image: !!file });
        input.value = ''; input.style.height = '';
        const typing = Object.assign(document.createElement('div'), { className: 'typing', textContent: T.thinking }); chat.append(typing); chat.scrollTop = chat.scrollHeight;
        window.OzmanAvatar?.set('thinking');
        sendBtn.disabled = true;
        const fd = new FormData();
        if (text) fd.append('message', text);
        if (file) fd.append('image', file);
        if (conversationId) fd.append('conversation_id', conversationId);
        image.value = ''; document.getElementById('attach').classList.remove('on');
        try {
            const r = await post(urls.send, fd);
            if (!r.ok) throw new Error(r.data.message || T.failed);
            conversationId = r.data.conversation_id; usage(r.data.usage);
            // When she reads it, the text appears as she starts talking (not seconds before her voice).
            if ((speakReplies || spoken) && window.OzmanVoice) await window.OzmanVoice.speakThen(r.data.message.content, voiceLang);
            bubble(r.data.message);
        } catch (e) { error.textContent = e.message; }
        finally {
            typing.remove(); sendBtn.disabled = !state.enabled; spoken = false; input.focus();
            if (!window.OzmanVoice?.busy()) window.OzmanAvatar?.set('idle');
        }
    }

    // Voice: talk instead of typing (sends when you stop), and hear the replies.
    const voiceLang = @json(app()->getLocale());
    window.OzmanVoice?.configure({speechUrl: @json(route('assistant.speech'))});
    const micBtn = document.getElementById('micBtn'), voiceBtn = document.getElementById('voiceBtn');
    let speakReplies = false, spoken = false, stopListening = null;
    try { speakReplies = localStorage.getItem('ozAssistantSpeak') === '1'; } catch (e) {}
    const drawVoice = () => { voiceBtn.setAttribute('aria-pressed', String(speakReplies)); voiceBtn.querySelector('i').className = speakReplies ? 'ti ti-volume' : 'ti ti-volume-off'; };
    drawVoice();
    voiceBtn.addEventListener('click', () => {
        speakReplies = !speakReplies; drawVoice();
        if (!speakReplies) window.OzmanVoice?.stopSpeaking();
        try { localStorage.setItem('ozAssistantSpeak', speakReplies ? '1' : '0'); } catch (e) {}
    });
    if (window.OzmanVoice?.canListen && state.enabled) {
        micBtn.hidden = false;
        micBtn.addEventListener('click', () => {
            if (stopListening) { stopListening(); return; }
            if (sendBtn.disabled) return;
            micBtn.classList.add('listening'); input.value = '';
            stopListening = window.OzmanVoice.listen({lang: voiceLang,
                onText: (text) => { input.value = text; },
                onError: (e) => { if (e === 'not-allowed' || e === 'service-not-allowed') error.textContent = @json(__('اسمح للموقع يستعمل الميكروفون وجرّب كمان مرة.')); },
                onEnd: (text) => { micBtn.classList.remove('listening'); stopListening = null; if (text.trim()) { spoken = true; send(text); } }});
        });
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); send(); });
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } });
    input.addEventListener('input', () => { input.style.height = ''; input.style.height = Math.min(input.scrollHeight, 160) + 'px'; });
    image.addEventListener('change', () => document.getElementById('attach').classList.toggle('on', image.files.length > 0));
    document.getElementById('resetBtn').addEventListener('click', async () => { const r = await post(urls.reset); if (r.ok) { conversationId = r.data.conversation_id; welcome(); usage(r.data.usage); } });

    usage(state.usage);
    state.messages.length ? state.messages.forEach(bubble) : welcome();
})();
</script>
</body>
</html>
