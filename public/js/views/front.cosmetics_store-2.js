(() => {
    const clips = window.beautyProductVideos || {};
    const modal = document.getElementById("beautyProductVideoModal");
    const player = document.getElementById("beautyProductVideoPlayer");
    const title = document.getElementById("beautyProductVideoTitle");
    const close = () => {
        if (!modal) return;
        player.pause();
        player.removeAttribute("src");
        player.load();
        window.OzmanBeautyOverlay.close(modal);
    };
    const open = (productId) => {
        const clip = clips[String(productId)];
        if (!clip || !modal) return;
        player.src = clip.url;
        player.muted = false;
        player.volume = 1;
        player.loop = true;
        title.textContent = clip.title;
        window.OzmanBeautyOverlay.open(modal);
        player.play().catch(() => {});
    };
    document.addEventListener(
        "click",
        (event) => {
            const trigger = event.target.closest(".beauty-video-hint");
            if (!trigger) return;
            const productId = trigger.closest(".beauty-product")?.querySelector("[data-add-product]")?.dataset.id;
            if (!productId || !clips[String(productId)]) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            open(productId);
        },
        true,
    );
    document.getElementById("beautyProductVideoClose")?.addEventListener("click", close);
    modal?.addEventListener("click", (event) => {
        if (event.target === modal) close();
    });
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal?.classList.contains("open")) close();
    });
})();
