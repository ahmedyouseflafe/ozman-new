(() => {
    'use strict';
    const data = window.PromoterPage;
    if (!data) return;
    const byId = id => document.getElementById(id);
    const player = byId('promoterVideo');
    let currentVideo = 0;
    const safePlay = media => { const promise = media.play(); if (promise) promise.catch(() => {}); };
    const shopLink = (element, item) => {
        element.hidden = !item.url;
        if (item.url) element.href = item.url; else element.removeAttribute('href');
    };
    function selectVideo(index, play = true) {
        if (!player || !data.videos[index]) return;
        currentVideo = index;
        const item = data.videos[index];
        player.src = item.src;
        if (item.poster) player.poster = item.poster; else player.removeAttribute('poster');
        byId('screenShop').textContent = item.shop || 'من اختياراتي';
        byId('screenCaption').textContent = item.caption || '';
        byId('screenStatus').textContent = '';
        shopLink(byId('screenShopLink'), item);
        document.querySelectorAll('[data-video]').forEach(button => {
            const active = Number(button.dataset.video) === index;
            button.classList.toggle('active', active);button.setAttribute('aria-pressed', String(active));
        });
        if (play) safePlay(player);
    }
    document.querySelectorAll('[data-video]').forEach(button => button.addEventListener('click', () => selectVideo(Number(button.dataset.video))));
    player?.addEventListener('ended', () => { if (data.videos.length > 1) selectVideo((currentVideo + 1) % data.videos.length); });
    player?.addEventListener('error', () => { byId('screenStatus').textContent = 'تعذّر تشغيل هذا الفيديو. جرّب فيديو آخر أو حدّث الصفحة.'; });
    byId('shareProfile')?.addEventListener('click', async () => {
        const status = byId('profileShareStatus'); status.textContent = '';
        try {
            if (navigator.share) await navigator.share({title: data.title, url: data.url});
            else { await navigator.clipboard.writeText(data.url); status.textContent = 'تم نسخ رابط الصفحة.'; }
        } catch (error) { if (error.name !== 'AbortError') status.textContent = 'رابط الصفحة: ' + data.url; }
    });
    const dialog = byId('socialViewer');
    let items = [], index = 0, isStory = false, paused = false, ready = false, elapsed = 0, last = 0, frame = 0;
    let oldOverflow = '', opener = null;
    const imageDuration = 7000;
    function pause(value) {
        paused = value;
        byId('viewerPause').textContent = value ? 'متابعة' : 'إيقاف مؤقت';
        const video = byId('viewerMedia').querySelector('video');
        if (video) { if (value) video.pause(); else safePlay(video); }
    }
    function stopMedia() {
        const video = byId('viewerMedia').querySelector('video');
        if (video) { video.pause(); video.removeAttribute('src'); video.load(); }
        byId('viewerMedia').replaceChildren();
    }
    function move(step) {
        const next = index + step;
        if (next < 0) return;
        if (next >= items.length) { if (isStory) dialog.close(); return; }
        index = next; render();
    }
    function render() {
        stopMedia(); ready = false; elapsed = 0; paused = false; last = performance.now();
        const item = items[index];
        byId('viewerCounter').textContent = `${index + 1} / ${items.length}`;
        byId('viewerCaption').textContent = item.caption || '';
        byId('viewerStatus').textContent = '';
        byId('viewerPrevious').disabled = index === 0;
        byId('viewerNext').disabled = !isStory && index === items.length - 1;
        byId('viewerPause').hidden = !isStory;
        byId('viewerPause').textContent = 'إيقاف مؤقت';
        byId('storyProgress').hidden = !isStory;
        byId('storyProgress').firstElementChild.style.width = '0%';
        shopLink(byId('viewerShop'), item);
        const media = document.createElement(item.type === 'video' ? 'video' : 'img');
        media.addEventListener('error', () => {
            ready = false;pause(true);byId('viewerStatus').textContent = 'المحتوى غير متاح أو انتهت مدة عرضه. انتقل للتالي أو حدّث الصفحة.';
        });
        if (item.type === 'video') {
            media.controls = true; media.playsInline = true; media.muted = true;
            if (item.poster) media.poster = item.poster;
            media.addEventListener('ended', () => { if (isStory) move(1); });
            media.addEventListener('loadeddata', () => { ready = true; });
            media.addEventListener('pause', () => { if (isStory && !media.ended) { paused = true;byId('viewerPause').textContent = 'متابعة'; } });
            media.addEventListener('play', () => { paused = false;byId('viewerPause').textContent = 'إيقاف مؤقت'; });
        } else {
            media.alt = item.caption || 'صورة المنشور';
            media.addEventListener('load', () => { ready = true;last = performance.now(); });
        }
        media.src = item.src;byId('viewerMedia').append(media);
        if (item.type === 'video') safePlay(media);
    }
    function tick(time) {
        const delta = time - last;last = time;
        if (dialog.open && isStory && ready && !paused && !document.hidden) {
            const video = byId('viewerMedia').querySelector('video');
            if (video) byId('storyProgress').firstElementChild.style.width = `${Number.isFinite(video.duration) && video.duration ? Math.min(100,video.currentTime / video.duration * 100) : 0}%`;
            else { elapsed += delta;byId('storyProgress').firstElementChild.style.width = `${Math.min(100,elapsed / imageDuration * 100)}%`;if (elapsed >= imageDuration) move(1); }
        }
        if (dialog.open) frame = requestAnimationFrame(tick);
    }
    function openViewer(kind, selected, button) {
        items = kind === 'story' ? data.stories : kind === 'reel' ? data.videos : data.posts;
        if (!items[selected]) return;
        index = selected;isStory = kind === 'story';opener = button;
        player?.pause();oldOverflow = document.body.style.overflow;document.body.style.overflow = 'hidden';
        dialog.showModal();render();frame = requestAnimationFrame(tick);
    }
    document.querySelectorAll('[data-story]').forEach(button => button.addEventListener('click', () => openViewer('story', Number(button.dataset.story), button)));
    document.querySelectorAll('[data-post]').forEach(button => button.addEventListener('click', () => openViewer('post', Number(button.dataset.post), button)));
    document.querySelectorAll('[data-reel]').forEach(button => button.addEventListener('click', () => openViewer('reel', Number(button.dataset.reel), button)));
    byId('viewerClose').addEventListener('click', () => dialog.close());
    byId('viewerPrevious').addEventListener('click', () => move(-1));
    byId('viewerNext').addEventListener('click', () => move(1));
    byId('viewerPause').addEventListener('click', () => pause(!paused));
    dialog.addEventListener('close', () => { cancelAnimationFrame(frame);stopMedia();document.body.style.overflow = oldOverflow;opener?.focus({preventScroll:true}); });
    dialog.addEventListener('click', event => { if (event.target === dialog) { const r=dialog.getBoundingClientRect();if(event.clientX<r.left || event.clientX>r.right || event.clientY<r.top || event.clientY>r.bottom)dialog.close(); } });
    document.addEventListener('visibilitychange', () => { if (document.hidden) { player?.pause();if (dialog.open) pause(true); } });
    window.addEventListener('pagehide', () => { player?.pause();if(dialog.open)dialog.close(); });
})();
