(() => {
    const slider = document.querySelector("[data-display-slider]");
    const slides = [...(slider?.querySelectorAll(".restaurant-display-slide") || [])];
    let index = 0;
    const showSlide = (next) => {
        slides[index]?.querySelector("video")?.pause();
        slides[index]?.classList.remove("active");
        index = next;
        slides[index]?.classList.add("active");
        slides[index]
            ?.querySelector("video")
            ?.play()
            .catch(() => {});
    };
    slides[0]
        ?.querySelector("video")
        ?.play()
        .catch(() => {});
    if (slides.length > 1) {
        const schedule = () =>
            window.setTimeout(
                () => {
                    showSlide((index + 1) % slides.length);
                    schedule();
                },
                Number(slides[index]?.dataset.duration) || 8000,
            );
        schedule();
    }

    const tabs = [...document.querySelectorAll("[data-category-key]")];
    const panes = [...document.querySelectorAll("[data-category-pane]")];
    const selectCategory = (key) => {
        tabs.forEach((tab) => {
            const active = tab.dataset.categoryKey === key;
            tab.classList.toggle("active", active);
            tab.setAttribute("aria-pressed", String(active));
        });
        panes.forEach((pane) => {
            const active = pane.dataset.categoryPane === key;
            pane.hidden = !active;
            const video = pane.querySelector(".category-background");
            if (video) active ? video.play().catch(() => {}) : video.pause();
        });
    };
    tabs.forEach((tab) => tab.addEventListener("click", () => selectCategory(tab.dataset.categoryKey)));
    if (tabs[0]) selectCategory(tabs[0].dataset.categoryKey);
})();
