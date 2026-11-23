(() => {
    const root = document.documentElement;
    const active = new Set();
    let saved = null;
    const resize = () => {
        // The visual viewport shrinks when the keyboard opens. Keep pinch zoom available.
        const viewport = window.visualViewport;
        const height = viewport && Math.abs(viewport.scale - 1) < 0.05 ? viewport.height : window.innerHeight;
        for (const modal of active) modal.style.setProperty("--beauty-overlay-height", `${height}px`);
    };
    window.OzmanBeautyOverlay = {
        open(modal) {
            if (modal.open) return;
            if (!active.size) {
                saved = { x: scrollX, y: scrollY, top: document.body.style.top };
                document.body.style.top = `-${saved.y}px`;
                root.classList.add("beauty-overlay-open");
            }
            active.add(modal);
            resize();
            modal.classList.add("open");
            modal.setAttribute("aria-hidden", "false");
            modal.showModal();
        },
        close(modal) {
            modal.classList.remove("open");
            modal.setAttribute("aria-hidden", "true");
            modal.close();
            active.delete(modal);
            modal.style.removeProperty("--beauty-overlay-height");
            if (!active.size && saved) {
                root.classList.remove("beauty-overlay-open");
                document.body.style.top = saved.top;
                window.scrollTo({ left: saved.x, top: saved.y, behavior: "instant" });
                saved = null;
            }
        },
    };
    window.visualViewport?.addEventListener("resize", resize);
    window.addEventListener("resize", resize);
    for (const [id, closeId] of [
        ["beautyCartSheet", "closeBeautyCart"],
        ["beautyProductImageModal", "beautyProductImageClose"],
        ["beautyProductVideoModal", "beautyProductVideoClose"],
    ]) {
        const modal = document.getElementById(id);
        modal?.addEventListener("cancel", (event) => {
            event.preventDefault();
            document.getElementById(closeId)?.click();
        });
        // Other flows (such as a purchase reward) may close native dialogs directly.
        modal?.addEventListener("close", () => {
            if (!modal.open && active.has(modal)) document.getElementById(closeId)?.click();
        });
    }
    document
        .getElementById("closeBeautyCartTop")
        ?.addEventListener("click", () => document.getElementById("closeBeautyCart").click());
})();
