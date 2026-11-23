{{-- The customers' shopping assistant: a floating button on storefronts that opens a chat. Signed-in customers only;
     guests get a sign-in link. On a shop's page it answers about that shop first. --}}
@php
    $saShop = isset($shop) && $shop instanceof \App\Models\Shop && $shop->exists ? $shop : null;
    $saLocale = app()->getLocale();
    $saName = \App\Support\AssistantPersona::name();
    $saHello = $saShop
        ? __('أهلاً! أنا :name 🌸 اسألني أي إشي عن :shop', ['name' => $saName, 'shop' => $saShop->name])
        : __('أهلاً! أنا :name 🌸 شو بتدوّر عليه اليوم؟', ['name' => $saName]);
@endphp
@if(config('services.anthropic.customer_assistant.enabled', true) && filled(config('services.anthropic.key')))
<button type="button" class="oz-sa-fab" id="ozSaFab" aria-haspopup="dialog" aria-controls="ozSaPanel">
    @include('front.partials.assistant_avatar', ['size' => 56, 'class' => 'oz-sa-fab-face'])
    <span class="oz-sa-fab-label">{{ $saShop ? __('اسأل :name', ['name' => $saName]) : $saName }}</span>
</button>
<section class="oz-sa" id="ozSaPanel" role="dialog" aria-modal="false" aria-label="{{ __('مساعد التسوّق') }}" dir="{{ $saLocale === 'en' ? 'ltr' : 'rtl' }}" hidden>
    <header class="oz-sa-head">
        @include('front.partials.assistant_avatar', ['size' => 42])
        <div><b>{{ $saName }} · {{ $saShop ? __('اسأل عن :shop', ['shop' => $saShop->name]) : __('مساعدة التسوّق') }}</b><small id="ozSaUsage"></small></div>
        <button type="button" class="oz-sa-icon" id="ozSaVoice" title="{{ __('اقرأ الردود بصوت') }}" aria-label="{{ __('اقرأ الردود بصوت') }}" aria-pressed="false">🔈</button>
        <button type="button" class="oz-sa-icon" id="ozSaReset" title="{{ __('محادثة جديدة') }}" aria-label="{{ __('محادثة جديدة') }}">＋</button>
        <button type="button" class="oz-sa-icon" id="ozSaClose" title="{{ __('إغلاق') }}" aria-label="{{ __('إغلاق') }}">×</button>
    </header>
    @include('front.partials.assistant_stage')
    <div class="oz-sa-log" id="ozSaLog" aria-live="polite"></div>
    <template id="ozSaFaceBig">@include('front.partials.assistant_avatar', ['size' => 96, 'class' => 'oz-sa-face-big'])</template>
    <form class="oz-sa-form" id="ozSaForm">
        <input type="text" id="ozSaInput" maxlength="1000" autocomplete="off" placeholder="{{ __('شو بتدوّر عليه؟') }}" aria-label="{{ __('رسالتك') }}">
        <button type="button" id="ozSaMic" class="oz-sa-mic" aria-label="{{ __('احكي بدل ما تكتب') }}" title="{{ __('احكي بدل ما تكتب') }}" hidden>🎤</button>
        <button type="submit" id="ozSaSend" aria-label="{{ __('إرسال') }}">➤</button>
    </form>
</section>
@viewStyle('front.partials.shopping_assistant')
<script>{!! file_get_contents(public_path('assistant-voice.js')) !!}</script>
<script>
(() => {
    const fab = document.getElementById('ozSaFab'), panel = document.getElementById('ozSaPanel'), log = document.getElementById('ozSaLog');
    const form = document.getElementById('ozSaForm'), input = document.getElementById('ozSaInput'), send = document.getElementById('ozSaSend');
    const shopId = @json($saShop?->id);
    const urls = { action: (id, verb) => @json(url('/assistant/actions')) + `/${id}/${verb}`, state: @json(route('customer-assistant.state')), send: @json(route('customer-assistant.send')), reset: @json(route('customer-assistant.reset')),
        login: @json(route('community.login', ['return' => '/'.ltrim(request()->path(), '/')])) };
    const T = { hello: @json($saHello),
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
            conversationId = data.conversation_id; usage(data.usage);
            // When she reads it, the text appears as she starts talking (not seconds before her voice).
            if ((speakReplies || spoken) && window.OzmanVoice) await window.OzmanVoice.speakThen(data.message.content, voiceLang);
            bubble(data.message);
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
