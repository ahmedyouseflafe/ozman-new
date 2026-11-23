(() => {
    const screen = document.querySelector("[data-nutella-display]");
    const slides = [...screen.querySelectorAll("[data-nutella-slide]")];
    if (!slides.length) return;
    let index = 0,
        timer;
    function show() {
        clearTimeout(timer);
        slides.forEach((slide, i) => {
            const active = i === index;
            slide.hidden = !active;
            slide.classList.toggle("active", active);
            const video = slide.querySelector("video"),
                frame = slide.querySelector("iframe");
            if (video) {
                if (active && !document.hidden) video.play().catch(() => {});
                else {
                    video.pause();
                    if (!active) video.currentTime = 0;
                }
            }
            if (frame) {
                if (active && !document.hidden) {
                    if (!frame.hasAttribute("src")) frame.src = frame.dataset.src;
                } else frame.removeAttribute("src");
            }
        });
        if (slides.length > 1 && !document.hidden)
            timer = setTimeout(() => {
                index = (index + 1) % slides.length;
                show();
            }, Number(slides[index].dataset.duration));
    }
    document.addEventListener("visibilitychange", show);
    window.addEventListener("pagehide", () => clearTimeout(timer));
    window.addEventListener("pageshow", show);
    show();
})();
