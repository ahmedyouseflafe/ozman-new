{{-- The customers' shopping assistant: a floating button on storefronts that opens a chat. Signed-in customers only;
     guests get a sign-in link. On a shop's page it answers about that shop first. --}}
@php
    $saShop = isset($shop) && $shop instanceof \App\Models\Shop && $shop->exists ? $shop : null;
    $saLocale = app()->getLocale();
@endphp
@if(config('services.anthropic.customer_assistant.enabled', true) && filled(config('services.anthropic.key')))
<button type="button" class="oz-sa-fab" id="ozSaFab" aria-haspopup="dialog" aria-controls="ozSaPanel">
    @include('front.partials.assistant_avatar', ['size' => 56, 'class' => 'oz-sa-fab-face'])
    <span class="oz-sa-fab-label">{{ $saShop ? __('اسأل') : __('مساعد') }}</span>
</button>
<section class="oz-sa" id="ozSaPanel" role="dialog" aria-modal="false" aria-label="{{ __('مساعد التسوّق') }}" dir="{{ $saLocale === 'en' ? 'ltr' : 'rtl' }}" hidden>
    <header class="oz-sa-head">
        @include('front.partials.assistant_avatar', ['size' => 42])
        <div><b>{{ $saShop ? __('اسأل عن :shop', ['shop' => $saShop->name]) : __('مساعد التسوّق') }}</b><small id="ozSaUsage"></small></div>
        <button type="button" class="oz-sa-icon" id="ozSaVoice" title="{{ __('اقرأ الردود بصوت') }}" aria-label="{{ __('اقرأ الردود بصوت') }}" aria-pressed="false">🔈</button>
        <button type="button" class="oz-sa-icon" id="ozSaReset" title="{{ __('محادثة جديدة') }}" aria-label="{{ __('محادثة جديدة') }}">＋</button>
        <button type="button" class="oz-sa-icon" id="ozSaClose" title="{{ __('إغلاق') }}" aria-label="{{ __('إغلاق') }}">×</button>
    </header>
    <div class="oz-sa-log" id="ozSaLog" aria-live="polite"></div>
    <template id="ozSaFaceBig">@include('front.partials.assistant_avatar', ['size' => 96, 'class' => 'oz-sa-face-big'])</template>
    <form class="oz-sa-form" id="ozSaForm">
        <input type="text" id="ozSaInput" maxlength="1000" autocomplete="off" placeholder="{{ __('شو بتدوّر عليه؟') }}" aria-label="{{ __('رسالتك') }}">
        <button type="button" id="ozSaMic" class="oz-sa-mic" aria-label="{{ __('احكي بدل ما تكتب') }}" title="{{ __('احكي بدل ما تكتب') }}" hidden>🎤</button>
        <button type="submit" id="ozSaSend" aria-label="{{ __('إرسال') }}">➤</button>
    </form>
</section>
<style>
.oz-sa-fab{position:fixed;z-index:9991;inset-inline-start:12px;bottom:calc(172px + env(safe-area-inset-bottom,0px));display:flex;flex-direction:column;align-items:center;gap:4px;padding:0;border:0;background:transparent;color:#fff;font-family:inherit;cursor:pointer;-webkit-tap-highlight-color:transparent}
.oz-sa-fab-face{border:2px solid rgba(255,255,255,.55);animation:ozSaWave 6s ease-in-out infinite}
@keyframes ozSaWave{0%,86%,100%{transform:none}90%{transform:rotate(-8deg)}94%{transform:rotate(8deg)}}
.oz-sa-face-big{margin:0 auto 10px}
.oz-sa-fab-label{padding:3px 8px;border-radius:999px;background:rgba(10,10,14,.72);font-size:11.5px}
.oz-sa{position:fixed;z-index:9996;inset-inline-start:8px;bottom:calc(8px + env(safe-area-inset-bottom,0px));width:min(400px,calc(100vw - 16px));height:min(620px,calc(100dvh - 24px));display:flex;flex-direction:column;border:1px solid #1d3b49;border-radius:22px;background:#0a141b;color:#f2f7fa;box-shadow:0 24px 70px rgba(0,0,0,.55);font-family:Cairo,Tahoma,Arial,sans-serif;overflow:hidden}
.oz-sa[hidden]{display:none}
.oz-sa-head{display:flex;align-items:center;gap:8px;padding:12px 14px;border-bottom:1px solid #1d3b49;background:linear-gradient(135deg,rgba(8,222,244,.12),transparent)}
.oz-sa-head div{flex:1;min-width:0}.oz-sa-head b{display:block;font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.oz-sa-head small{color:#93aab7;font-size:11px}
.oz-sa-icon{width:34px;height:34px;border:1px solid #1d3b49;border-radius:10px;background:#102029;color:#e8fbff;font-size:18px;line-height:1;cursor:pointer}
.oz-sa-log{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:10px}
.oz-sa-msg{max-width:88%;padding:9px 12px;border-radius:16px;font-size:14px;line-height:1.75;word-wrap:break-word}
.oz-sa-msg.user{align-self:flex-start;background:#123545;border:1px solid #1f5468}
.oz-sa-msg.assistant{align-self:flex-end;background:#10151a;border:1px solid #1d3b49}
.oz-sa-msg p{margin:0 0 4px}.oz-sa-msg p:last-child{margin:0}.oz-sa-msg ul{margin:2px 0;padding-inline-start:18px}.oz-sa-msg h4{margin:4px 0;color:#08def4;font-size:14px}
.oz-sa-cards{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;align-self:stretch}
.oz-sa-card{flex:0 0 140px;display:flex;flex-direction:column;gap:3px;padding:7px;border:1px solid #1d3b49;border-radius:14px;background:#0f1a21;color:inherit;text-decoration:none}
.oz-sa-card img,.oz-sa-card .ph{width:100%;aspect-ratio:1;border-radius:10px;object-fit:cover;background:#16303c}
.oz-sa-card b{font-size:12.5px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.oz-sa-card small{color:#93aab7;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.oz-sa-card .price{color:#08def4;font-weight:900;font-size:13px}.oz-sa-card s{color:#93aab7;font-size:11px;margin-inline-start:4px}
.oz-sa-welcome{margin:auto 0;text-align:center;color:#93aab7;font-size:13.5px;line-height:1.8}.oz-sa-welcome b{display:block;color:#f2f7fa;font-size:16px;margin-bottom:4px}
.oz-sa-chips{display:flex;flex-wrap:wrap;justify-content:center;gap:6px;margin-top:10px}
.oz-sa-chip{padding:6px 10px;border:1px solid #1f5468;border-radius:999px;background:rgba(8,222,244,.07);color:#cdf6ff;font:inherit;font-size:12px;cursor:pointer}
.oz-sa-action{align-self:stretch;display:grid;gap:8px;padding:10px 12px;border:1px solid #27dd86;border-radius:14px;background:rgba(39,221,134,.07)}.oz-sa-action b{line-height:1.5}.oz-sa-action div{display:flex;gap:8px}.oz-sa-action button{padding:7px 14px;border-radius:10px;border:1px solid #27dd86;font:inherit;font-weight:800;cursor:pointer}.oz-sa-book{background:#27dd86;color:#04210f}.oz-sa-drop{background:transparent;color:#cfe;border-color:#1d3b49!important}.oz-sa-action button:disabled{opacity:.6;cursor:wait}.oz-sa-action small{color:#93aab7}.oz-sa-action.failed,.oz-sa-action.expired{border-color:#ff9aae;background:rgba(255,154,174,.07)}.oz-sa-action.cancelled{border-color:#1d3b49;background:transparent}
.oz-sa-typing{align-self:flex-end;color:#93aab7;font-size:12.5px}.oz-sa-error{color:#ff9aae;font-size:12.5px;text-align:center}
.oz-sa-login{display:inline-block;margin-top:10px;padding:9px 16px;border-radius:12px;background:linear-gradient(135deg,#08def4,#1fa8ee);color:#02161a;font-weight:900;text-decoration:none}
.oz-sa-form{display:flex;gap:6px;padding:10px;border-top:1px solid #1d3b49}
.oz-sa-form input{flex:1;min-width:0;padding:11px 12px;border:1px solid #1d3b49;border-radius:14px;background:#131719;color:#f2f7fa;font:inherit;font-size:14px;outline:none}
.oz-sa-form button{width:44px;border:0;border-radius:14px;background:#08def4;color:#02161a;font-size:17px;cursor:pointer}.oz-sa-form button:disabled{opacity:.5}
.oz-sa-form .oz-sa-mic{background:#102029;border:1px solid #1d3b49;color:#e8fbff}.oz-sa-form .oz-sa-mic.on{background:#ff5d7a;border-color:#ff5d7a;color:#fff;animation:ozSaPulse 1.1s infinite}
@keyframes ozSaPulse{50%{box-shadow:0 0 0 6px rgba(255,93,122,.25)}}
#ozSaVoice[aria-pressed="true"]{background:#08def4;color:#02161a;border-color:#08def4}
</style>
<script>{!! file_get_contents(public_path('assistant-voice.js')) !!}</script>
<script>
(() => {
    const fab = document.getElementById('ozSaFab'), panel = document.getElementById('ozSaPanel'), log = document.getElementById('ozSaLog');
    const form = document.getElementById('ozSaForm'), input = document.getElementById('ozSaInput'), send = document.getElementById('ozSaSend');
    const shopId = @json($saShop?->id);
    const urls = { action: (id, verb) => @json(url('/assistant/actions')) + `/${id}/${verb}`, state: @json(route('customer-assistant.state')), send: @json(route('customer-assistant.send')), reset: @json(route('customer-assistant.reset')),
        login: @json(route('community.login', ['return' => '/'.ltrim(request()->path(), '/')])) };
    const T = { hello: @json($saShop ? __('أهلاً! اسألني أي إشي عن :shop', ['shop' => $saShop->name]) : __('أهلاً! شو بتدوّر عليه اليوم؟')),
        intro: @json(__('احكيلي شو بدك بكلامك، وأنا بلاقيلك من محلات أوزمان. وبقدر أحكيلك وين طلبك وكم نقطة عندك.')),
        login: @json(__('سجّل دخول عشان تحكي مع المساعد')), loginBtn: @json(__('تسجيل الدخول')), typing: @json(__('عم بدوّرلك…')),
        failed: @json(__('صار خطأ، جرّب كمان مرة.')), expired: @json(__('حدّث الصفحة وجرّب كمان مرة.')), off: @json(__('المساعد مش متاح هلقيت.')),
        today: @json(__('أسئلة اليوم')), listening: @json(__('عم بسمعك…')), noMic: @json(__('اسمح للموقع يستعمل الميكروفون وجرّب كمان مرة.')), book: @json(__('احجز')), cancel: @json(__('إلغاء')), cancelled: @json(__('انلغى')), stale: @json(__('انتهت صلاحيته، اطلبه من جديد')) };
    let state = null, csrf = null, conversationId = null, busy = false;

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const inline = (s) => esc(s).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
    const render = (text) => {
        let html = '', list = false;
        for (const line of String(text).split('\n')) {
            const item = line.match(/^\s*(?:[-•*]|\d+[.)])\s+(.*)$/);
            if (item) { if (!list) { html += '<ul>'; list = true; } html += `<li>${inline(item[1])}</li>`; continue; }
            if (list) { html += '</ul>'; list = false; }
            if (/^#{1,3}\s+/.test(line)) html += `<h4>${inline(line.replace(/^#{1,3}\s+/, ''))}</h4>`;
            else if (line.trim()) html += `<p>${inline(line)}</p>`;
        }
        return html + (list ? '</ul>' : '');
    };
    const money = (n) => n == null ? '' : `${Number(n).toLocaleString(undefined, {maximumFractionDigits: 2})} ₪`;
    const usage = (u) => { document.getElementById('ozSaUsage').textContent = u ? `${T.today}: ${u.used}/${u.limit}` : ''; };
    const scroll = () => { log.scrollTop = log.scrollHeight; };

    const bubble = (m) => {
        const div = document.createElement('div');
        div.className = `oz-sa-msg ${m.role}`;
        div.innerHTML = m.role === 'assistant' ? render(m.content) : `<p>${esc(m.content)}</p>`;
        log.append(div);
        const cards = [...(m.products || []).map((p) => `<a class="oz-sa-card" href="${esc(p.url)}">${p.image ? `<img src="${esc(p.image)}" alt="" loading="lazy">` : '<span class="ph"></span>'}<b>${esc(p.name)}</b><small>${esc(p.shop?.name)}</small><span><span class="price">${money(p.final_price)}</span>${p.discount_price ? `<s>${money(p.price)}</s>` : ''}</span></a>`),
            ...(m.shops || []).map((s) => `<a class="oz-sa-card" href="${esc(s.web_url)}">${s.logo ? `<img src="${esc(s.logo)}" alt="" loading="lazy">` : '<span class="ph"></span>'}<b>${esc(s.name)}</b><small>${esc(s.city || '')}</small></a>`)];
        if (cards.length) { const row = document.createElement('div'); row.className = 'oz-sa-cards'; row.innerHTML = cards.join(''); log.append(row); }
        (m.actions || []).forEach((a) => log.append(actionCard(a)));
        scroll();
    };
    // A booking the assistant prepared: "book" makes it, "cancel" drops it; afterwards the card says what happened.
    const actionCard = (a) => {
        const box = document.createElement('div');
        const draw = () => {
            box.className = `oz-sa-action ${a.status}`;
            const note = a.status === 'cancelled' ? T.cancelled : a.status === 'expired' ? T.stale : (a.result || '');
            box.innerHTML = `<b>${esc(a.summary)}</b>` + (a.status === 'pending'
                ? `<div><button type="button" class="oz-sa-book">${esc(T.book)}</button><button type="button" class="oz-sa-drop">${esc(T.cancel)}</button></div>`
                : (note ? `<small>${esc(note)}</small>` : ''));
            const decide = async (verb) => {
                box.querySelectorAll('button').forEach((b) => b.disabled = true);
                try { const data = await post(urls.action(a.id, verb)); Object.assign(a, data.action || {}, {result: data.message || data.action?.result}); }
                catch (e) { Object.assign(a, {status: 'failed', result: e.message}); }
                draw();
            };
            box.querySelector('.oz-sa-book')?.addEventListener('click', () => decide('confirm'));
            box.querySelector('.oz-sa-drop')?.addEventListener('click', () => decide('cancel'));
        };
        draw();
        return box;
    };
    const faceBig = document.getElementById('ozSaFaceBig').innerHTML;
    const welcome = () => {
        log.innerHTML = `<div class="oz-sa-welcome">${faceBig}<b>${esc(T.hello)}</b>${esc(T.intro)}<div class="oz-sa-chips"></div></div>`;
        const chips = log.querySelector('.oz-sa-chips');
        (state?.suggestions || []).forEach((s) => { const c = Object.assign(document.createElement('button'), {type: 'button', className: 'oz-sa-chip', textContent: s}); c.onclick = () => ask(s); chips.append(c); });
    };
    const guest = () => {
        log.innerHTML = `<div class="oz-sa-welcome">${faceBig}<b>${esc(T.hello)}</b>${esc(T.login)}<br><a class="oz-sa-login" href="${esc(urls.login)}">${esc(T.loginBtn)}</a></div>`;
        input.disabled = send.disabled = true;
    };

    async function load() {
        try {
            const r = await fetch(urls.state + (shopId ? `?shop_id=${shopId}` : ''), {credentials: 'same-origin', headers: {Accept: 'application/json'}});
            if (r.status === 401) return guest();
            state = await r.json();
            csrf = state.csrf; conversationId = state.conversation_id; usage(state.usage);
            if (!state.enabled) { log.innerHTML = `<div class="oz-sa-welcome">${esc(T.off)}</div>`; input.disabled = send.disabled = true; return; }
            log.innerHTML = '';
            state.messages.length ? state.messages.forEach(bubble) : welcome();
            input.focus();
        } catch (e) { log.innerHTML = `<div class="oz-sa-error">${esc(T.failed)}</div>`; }
    }

    async function post(url, body) {
        const r = await fetch(url, {method: 'POST', credentials: 'same-origin', headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf || ''}, body: JSON.stringify(body || {})});
        const data = await r.json().catch(() => ({}));
        if (r.status === 419) throw new Error(T.expired);
        if (!r.ok) throw new Error(data.message || T.failed);
        return data;
    }

    async function ask(text) {
        text = String(text ?? input.value).trim();
        if (!text || busy || !state) return;
        busy = true; send.disabled = true;
        if (log.querySelector('.oz-sa-welcome')) log.innerHTML = '';
        log.querySelector('.oz-sa-error')?.remove();
        bubble({role: 'user', content: text});
        input.value = '';
        const typing = Object.assign(document.createElement('div'), {className: 'oz-sa-typing', textContent: T.typing}); log.append(typing); scroll();
        window.OzmanAvatar?.set('thinking');
        try {
            const data = await post(urls.send, {message: text, conversation_id: conversationId, shop_id: shopId});
            conversationId = data.conversation_id; bubble(data.message); usage(data.usage);
            if (speakReplies || spoken) window.OzmanVoice?.speak(data.message.content, voiceLang);
        } catch (e) {
            const err = Object.assign(document.createElement('div'), {className: 'oz-sa-error', textContent: e.message}); log.append(err); scroll();
        } finally {
            typing.remove(); busy = false; send.disabled = false; spoken = false; input.focus();
            if (!window.OzmanVoice?.busy()) window.OzmanAvatar?.set('idle');
        }
    }

    // Voice: the mic fills the box as you talk and sends when you stop; replies are read aloud when 🔊 is on (or after you spoke).
    const voiceLang = @json($saLocale);
    window.OzmanVoice?.configure({speechUrl: @json(route('assistant.speech')), csrf: () => csrf || ''});
    const mic = document.getElementById('ozSaMic'), voiceBtn = document.getElementById('ozSaVoice');
    let speakReplies = false, spoken = false, stopListening = null;
    try { speakReplies = localStorage.getItem('ozSaSpeak') === '1'; } catch (e) {}
    const drawVoice = () => { voiceBtn.setAttribute('aria-pressed', String(speakReplies)); voiceBtn.textContent = speakReplies ? '🔊' : '🔈'; };
    drawVoice();
    voiceBtn.addEventListener('click', () => {
        speakReplies = !speakReplies; drawVoice();
        if (!speakReplies) window.OzmanVoice?.stopSpeaking();
        try { localStorage.setItem('ozSaSpeak', speakReplies ? '1' : '0'); } catch (e) {}
    });
    if (window.OzmanVoice?.canListen) {
        mic.hidden = false;
        mic.addEventListener('click', () => {
            if (stopListening) { stopListening(); return; }
            if (busy || !state || input.disabled) return;
            const before = input.placeholder;
            mic.classList.add('on'); input.value = ''; input.placeholder = T.listening;
            stopListening = window.OzmanVoice.listen({lang: voiceLang,
                onText: (text) => { input.value = text; },
                onError: (error) => { if (error === 'not-allowed' || error === 'service-not-allowed') { const err = Object.assign(document.createElement('div'), {className: 'oz-sa-error', textContent: T.noMic}); log.append(err); scroll(); } },
                onEnd: (text) => {
                    mic.classList.remove('on'); input.placeholder = before; stopListening = null;
                    if (text.trim()) { spoken = true; ask(text); }
                }});
        });
    }

    fab.addEventListener('click', () => { panel.hidden = false; fab.hidden = true; if (!state) load(); else input.focus(); });
    document.getElementById('ozSaClose').addEventListener('click', () => { panel.hidden = true; fab.hidden = false; });
    document.getElementById('ozSaReset').addEventListener('click', async () => {
        if (!state || busy) return;
        try { const data = await post(urls.reset); conversationId = data.conversation_id; usage(data.usage); welcome(); } catch (e) {}
    });
    form.addEventListener('submit', (e) => { e.preventDefault(); ask(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !panel.hidden) { panel.hidden = true; fab.hidden = false; } });
})();
</script>
@endif
