(() => {
    const container = document.currentScript.previousElementSibling;
    if (!container) return;
    const slides = [...container.querySelectorAll("[data-screen-slide]")];
    let index = 0,
        timer;
    function show() {
        slides.forEach((slide, i) => {
            slide.hidden = i !== index;
            const video = slide.querySelector("video"),
                frame = slide.querySelector("iframe");
            if (video) i === index && !document.hidden ? video.play().catch(() => {}) : video.pause();
            if (frame) i === index ? frame.setAttribute("src", frame.dataset.src) : frame.removeAttribute("src");
        });
        clearTimeout(timer);
        if (slides.length > 1 && !document.hidden)
            timer = setTimeout(() => {
                index = (index + 1) % slides.length;
                show();
            }, Number(slides[index].dataset.duration));
    }
    document.addEventListener("visibilitychange", show);
    show();
})();
