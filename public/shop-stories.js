(() => {
    const section = document.getElementById('shopStories'), dialog = document.getElementById('shopStoryViewer');
    if (!section || !dialog) return;
    const byId = id => document.getElementById(id);
    const list = byId('shopStoriesList'), media = byId('shopStoryMedia'), progress = byId('shopStoryProgress'), pause = byId('shopStoryPause');
    let shops = [], shopIndex = 0, index = 0, frame = 0, generation = 0, elapsed = 0, last = 0;
    let paused = false, ready = false, video = null, opener = null, overflow = '';
    const alive = story => Date.parse(story.expires_at) > Date.now();
    const seen = new Set();
    try { JSON.parse(localStorage.getItem('ozman_seen_stories') || '[]').forEach(id => seen.add(id)); } catch (_) {}
    function renderList() {
        list.replaceChildren();
        shops.forEach((shop, i) => {
            if (!shop.stories.some(alive)) return;
            const button = document.createElement('button'), img = document.createElement('img'), title = document.createElement('span');
            button.type = 'button'; img.src = shop.logo; img.alt = ''; img.loading = 'lazy'; title.textContent = shop.title;
            button.classList.toggle('seen', shop.stories.filter(alive).every(story => seen.has(story.id)));
            button.append(img, title); button.onclick = () => open(i, button); list.append(button);
        });
        section.hidden = !list.children.length;
    }
    function stop() {
        cancelAnimationFrame(frame);
        if (video) { video.pause(); video.removeAttribute('src'); video.load(); }
        video = null; media.replaceChildren();
    }
    function open(i, button) {
        shops[i].stories = shops[i].stories.filter(alive);
        if (!shops[i].stories.length) { renderList(); return; }
        shopIndex = i; index = 0; opener = button; overflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden'; dialog.showModal();
        document.querySelectorAll('video').forEach(item => item.pause());
        document.dispatchEvent(new CustomEvent('ozman:story-viewer', { detail: { open: true } }));
        show();
    }
    function move(delta) {
        index = Math.max(0, index + delta);
        if (index >= shops[shopIndex].stories.length) { dialog.close(); return; }
        show();
    }
    function setPaused(value) {
        paused = value; last = 0; pause.textContent = paused ? 'متابعة' : 'إيقاف مؤقت';
        if (video) {
            if (paused) video.pause();
            else video.play().catch(() => { paused = true; pause.textContent = 'تشغيل الفيديو'; });
        }
    }
    function show() {
        stop(); const version = ++generation, shop = shops[shopIndex], story = shop.stories[index];
        if (!alive(story)) { move(1); return; }
        elapsed = 0; last = 0; paused = false; ready = false; pause.textContent = 'إيقاف مؤقت';
        byId('shopStoryTitle').textContent = shop.title; byId('shopStoryCaption').textContent = story.caption || '';
        byId('shopStoryVisit').href = shop.url; progress.replaceChildren();
        shop.stories.forEach((_, i) => { const bar = document.createElement('span'), fill = document.createElement('i'); fill.style.width = i < index ? '100%' : '0%'; bar.append(fill); progress.append(bar); });
        const element = document.createElement(story.type === 'video' ? 'video' : 'img');
        const loaded = () => {
            if (version !== generation) return;
            ready = true; seen.add(story.id);
            try { localStorage.setItem('ozman_seen_stories', JSON.stringify([...seen].slice(-1000))); } catch (_) {}
        };
        element.onerror = () => { if (version === generation) { ready = false; byId('shopStoryCaption').textContent = 'تعذر تحميل الستوري. انتقل للتالية أو أعد المحاولة.'; } };
        if (story.type === 'video') {
            video = element; video.playsInline = true; video.preload = 'metadata';
            video.onplaying = loaded; video.onwaiting = () => { ready = false; };
            video.onended = () => { if (version === generation) move(1); };
        } else { element.alt = story.caption || shop.title; element.onload = loaded; }
        element.src = story.src; media.append(element);
        if (video) video.play().catch(() => { if (version === generation) setPaused(true); });
        function tick(now) {
            if (version !== generation || !dialog.open) return;
            if (!alive(story)) { move(1); return; }
            if (!paused && !document.hidden && ready) {
                if (last) elapsed += now - last;
                const fraction = video ? (video.duration ? video.currentTime / video.duration : 0) : elapsed / 6000;
                progress.children[index].firstChild.style.width = `${Math.min(100, fraction * 100)}%`;
                if (!video && elapsed >= 6000) { move(1); return; }
            }
            last = now; frame = requestAnimationFrame(tick);
        }
        frame = requestAnimationFrame(tick);
    }
    byId('shopStoryClose').onclick = () => dialog.close();
    byId('shopStoryPrevious').onclick = () => move(-1); byId('shopStoryNext').onclick = () => move(1);
    pause.onclick = () => setPaused(!paused);
    let wasPaused = false, pressX = 0, pressAt = 0;
    media.onpointerdown = event => {
        wasPaused = paused; pressX = event.clientX; pressAt = performance.now();
        media.setPointerCapture(event.pointerId); setPaused(true);
    };
    media.onpointerup = event => {
        const distance = event.clientX - pressX;
        if (Math.abs(distance) > 60) { move(distance < 0 ? 1 : -1); return; }
        if (performance.now() - pressAt < 220 && !wasPaused) {
            const rect = media.getBoundingClientRect();
            move(event.clientX > rect.left + rect.width / 2 ? 1 : -1);
        } else if (!wasPaused) setPaused(false);
    };
    media.onpointercancel = () => { if (!wasPaused) setPaused(false); };
    dialog.addEventListener('keydown', event => {
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') { event.preventDefault(); move(event.key === 'ArrowRight' ? 1 : -1); }
    });
    dialog.addEventListener('close', () => {
        ++generation; stop(); document.body.style.overflow = overflow;
        document.dispatchEvent(new CustomEvent('ozman:story-viewer', { detail: { open: false } }));
        renderList(); if (opener?.isConnected) opener.focus(); else list.querySelector('button')?.focus();
    });
    document.addEventListener('visibilitychange', () => { if (dialog.open && document.hidden) setPaused(true); });
    window.addEventListener('pagehide', () => { if (dialog.open) dialog.close(); });
    function decorate() {
        document.querySelectorAll('#sideVTrack [data-story-shop-id]').forEach(item => {
            const i = shops.findIndex(shop => String(shop.id) === item.dataset.storyShopId && shop.stories.some(alive));
            item.classList.toggle('has-shop-story', i >= 0);
            if (i < 0 || item.querySelector('.shop-story-badge')) return;
            const badge = document.createElement('button'); badge.type = 'button'; badge.className = 'shop-story-badge'; badge.textContent = 'ستوري';
            badge.setAttribute('aria-label', `ستوريات ${shops[i].title}`);
            badge.onclick = event => { event.stopPropagation(); open(i, badge); };
            badge.onkeydown = event => event.stopPropagation(); item.append(badge);
        });
    }
    fetch(section.dataset.feed, { headers: { Accept: 'application/json' } })
        .then(response => { if (!response.ok) throw new Error('feed'); return response.json(); })
        .then(data => { shops = data; renderList(); decorate(); const track = byId('sideVTrack'); if (track) new MutationObserver(decorate).observe(track, { childList: true }); })
        .catch(() => { section.hidden = true; });
})();
