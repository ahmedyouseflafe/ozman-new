<style>
/* Native modal dialogs stay above sticky cards and the checkout bar on iOS. */
dialog.beauty-viewport-modal{
    position:fixed;inset:0;margin:0;border:0;width:100%;max-width:none;
    height:100vh;height:var(--beauty-overlay-height,100dvh);max-height:none;
    min-width:0;overflow:hidden;color:#fff;direction:rtl;
    padding:calc(12px + env(safe-area-inset-top,0px)) calc(12px + env(safe-area-inset-right,0px)) calc(12px + env(safe-area-inset-bottom,0px)) calc(12px + env(safe-area-inset-left,0px));
    backdrop-filter:none;-webkit-backdrop-filter:none;
}
dialog.beauty-viewport-modal::backdrop{background:#030105c9}
.beauty-overlay-open{overflow:hidden}
.beauty-overlay-open body{position:fixed;inset-inline:0;width:100%;overflow:hidden}
#beautyCartSheet .cart-card{position:relative;min-width:0;max-width:100%;max-height:100%;overscroll-behavior:contain;overflow-x:hidden;overflow-y:auto;scrollbar-gutter:stable;overflow-wrap:anywhere}
#beautyCartSheet h2{padding-inline-end:48px;min-height:44px;display:flex;align-items:center}
.beauty-cart-close-top{position:absolute;inset-inline-end:10px;top:10px;width:44px;height:44px;border:1px solid var(--line);border-radius:50%;background:#302035;color:#fff;font-size:26px;cursor:pointer}
#beautyCartSheet .cart-row{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:start}
#beautyCartSheet .cart-row>span:first-child{min-width:0;overflow-wrap:anywhere}
#beautyCartSheet .cart-row>span:last-child{white-space:nowrap}
#beautyCartSheet .cart-row button{min-width:36px;min-height:44px}
#beautyCartSheet .beauty-form,#beautyCartSheet .beauty-field,#beautyCartSheet .cart-actions{min-width:0}
#beautyCartSheet .beauty-field input,#beautyCartSheet .beauty-field textarea{min-width:0;max-width:100%;font-size:16px}
#beautyCartSheet .cart-actions{grid-template-columns:minmax(0,1fr) auto}
#beautyCartSheet .cart-actions a{padding:10px;white-space:normal;text-align:center}
#beautyProductImageModal,#beautyProductVideoModal{padding-top:calc(66px + env(safe-area-inset-top,0px));background:#08050bf5}
#beautyProductImageModal .beauty-product-image-dialog{display:grid;grid-template-rows:minmax(0,1fr) auto;gap:12px;width:min(960px,100%);height:100%;min-width:0;min-height:0;max-height:none;padding:0}
#beautyProductImageModal #beautyProductImageViewer{width:100%;height:100%;min-width:0;min-height:0;max-width:100%;max-height:100%;object-fit:contain;background:transparent;box-shadow:none;border-radius:12px}
#beautyProductImageModal .beauty-gallery-controls{position:static;margin:0;min-width:0;direction:rtl}
#beautyProductImageModal .beauty-gallery-controls button{position:static;transform:none;flex:none;width:48px;height:48px;padding:0}
#beautyProductImageModal .beauty-gallery-controls button[hidden]{display:none}
#beautyProductImageModal .beauty-product-image-close,#beautyProductVideoModal .beauty-product-video-close{position:absolute;top:calc(10px + env(safe-area-inset-top,0px));left:calc(12px + env(safe-area-inset-left,0px));width:44px;height:44px}
#beautyProductVideoModal .beauty-product-video-dialog{display:flex;align-items:center;justify-content:center;width:100%;height:100%;min-width:0;min-height:0;max-height:100%}
#beautyProductVideoModal video{width:100%;height:100%;min-width:0;min-height:0;max-height:100%;object-fit:contain}
@media(max-width:480px){#beautyCartSheet .cart-card{width:100%;padding:14px;border-radius:20px}}
</style>
<script>
(() => {
    const root = document.documentElement;
    const active = new Set();
    let saved = null;
    const resize = () => {
        // The visual viewport shrinks when the keyboard opens. Keep pinch zoom available.
        const viewport = window.visualViewport;
        const height = viewport && Math.abs(viewport.scale - 1) < .05 ? viewport.height : window.innerHeight;
        for (const modal of active) modal.style.setProperty('--beauty-overlay-height', `${height}px`);
    };
    window.OzmanBeautyOverlay = {
        open(modal) {
            if (modal.open) return;
            if (!active.size) {
                saved = {x:scrollX, y:scrollY, top:document.body.style.top};
                document.body.style.top = `-${saved.y}px`;
                root.classList.add('beauty-overlay-open');
            }
            active.add(modal);
            resize();
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            modal.showModal();
        },
        close(modal) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            modal.close();
            active.delete(modal);
            modal.style.removeProperty('--beauty-overlay-height');
            if (!active.size && saved) {
                root.classList.remove('beauty-overlay-open');
                document.body.style.top = saved.top;
                window.scrollTo({left:saved.x, top:saved.y, behavior:'instant'});
                saved = null;
            }
        }
    };
    window.visualViewport?.addEventListener('resize', resize);
    window.addEventListener('resize', resize);
    for (const [id, closeId] of [
        ['beautyCartSheet', 'closeBeautyCart'],
        ['beautyProductImageModal', 'beautyProductImageClose'],
        ['beautyProductVideoModal', 'beautyProductVideoClose'],
    ]) {
        const modal = document.getElementById(id);
        modal?.addEventListener('cancel', event => {
            event.preventDefault();
            document.getElementById(closeId)?.click();
        });
        // Other flows (such as a purchase reward) may close native dialogs directly.
        modal?.addEventListener('close', () => {
            if (!modal.open && active.has(modal)) document.getElementById(closeId)?.click();
        });
    }
    document.getElementById('closeBeautyCartTop')?.addEventListener('click', () => document.getElementById('closeBeautyCart').click());
})();
</script>
