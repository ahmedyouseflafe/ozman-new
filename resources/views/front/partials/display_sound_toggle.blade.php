{{-- A display video must stay muted until the visitor explicitly enables sound. --}}
<button type="button" class="storefront-display-sound-toggle" data-display-sound-toggle
    aria-label="{{ __('تشغيل صوت الفيديو') }}" title="{{ __('تشغيل صوت الفيديو') }}" hidden>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4z"/><path d="m16 10 5 5m0-5-5 5"/></svg>
</button>

<style>
    .storefront-display-sound-toggle{position:absolute;z-index:5;top:14px;left:14px;width:42px;height:42px;display:grid;place-items:center;border:1px solid rgba(8,222,244,.88);border-radius:50%;background:rgba(2,10,14,.88);box-shadow:0 0 18px rgba(8,222,244,.27);color:#08def4;font-size:20px;cursor:pointer;transition:transform .18s ease,background .18s ease,color .18s ease}.storefront-display-sound-toggle svg{width:21px;height:21px;pointer-events:none}.storefront-display-sound-toggle:hover,.storefront-display-sound-toggle:focus-visible{transform:scale(1.08);background:#08def4;color:#001216;outline:none}.storefront-display-sound-toggle[hidden]{display:none}@media(max-width:720px){.storefront-display-sound-toggle{top:10px;left:10px;width:36px;height:36px;font-size:17px}}
</style>

<script>
    (() => {
        const button = document.currentScript.previousElementSibling?.previousElementSibling;
        if (!button?.matches('[data-display-sound-toggle]')) return;
        const screen = button.parentElement;
        let soundEnabled = false;
        const activeVideo = () => screen.querySelector('.active video');
        const sync = () => {
            const video = activeVideo();
            screen.querySelectorAll('video').forEach(item => item.muted = item !== video || !soundEnabled);
            if (!video) { soundEnabled = false; button.hidden = true; return; }
            button.hidden = false;
            const label = soundEnabled ? @json(__('كتم صوت الفيديو')) : @json(__('تشغيل صوت الفيديو'));
            button.setAttribute('aria-label', label); button.title = label;
            button.innerHTML = soundEnabled
                ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/><path d="M20.5 3.5a12 12 0 0 1 0 17"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H3v6h3l5 4z"/><path d="m16 10 5 5m0-5-5 5"/></svg>';
        };
        button.addEventListener('click', () => {
            const video = activeVideo();
            if (!video) return;
            soundEnabled = !soundEnabled; sync(); video.play().catch(() => {});
        });
        new MutationObserver(sync).observe(screen, {subtree:true, attributes:true, attributeFilter:['class']});
        sync();
    })();
</script>
