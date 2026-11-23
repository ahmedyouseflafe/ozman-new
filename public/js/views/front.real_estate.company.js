(() => {
    document.querySelectorAll("[data-gallery]").forEach((gallery) => {
        const images = JSON.parse(gallery.dataset.gallery);
        let index = 0;
        gallery.querySelectorAll("[data-step]").forEach((button) =>
            button.addEventListener("click", () => {
                index = (index + Number(button.dataset.step) + images.length) % images.length;
                gallery.querySelector("img").src = images[index];
                gallery.querySelector("output").textContent = `${index + 1} / ${images.length}`;
            }),
        );
    });
    document.querySelector("[data-project-form]")?.addEventListener("submit", (event) => {
        event.preventDefault();
        const form = event.currentTarget,
            url = new URL(form.dataset.contact);
        const lines = [...form.querySelectorAll("input,select,textarea")]
            .filter((input) => input.value.trim())
            .map((input) => `${input.closest("label").firstChild.textContent.trim()}: ${input.value.trim()}`);
        url.searchParams.set("text", url.searchParams.get("text") + "\n" + lines.join("\n"));
        window.location.assign(url.href);
    });
    const slider = document.querySelector("[data-display-slider]");
    const slides = [...(slider?.querySelectorAll(".company-display-slide") || [])];
    let slideIndex = 0;
    const showSlide = (next) => {
        slides[slideIndex]?.querySelector("video")?.pause();
        slides[slideIndex]?.classList.remove("active");
        slideIndex = next;
        slides[slideIndex]?.classList.add("active");
        slides[slideIndex]
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
                    showSlide((slideIndex + 1) % slides.length);
                    schedule();
                },
                Number(slides[slideIndex]?.dataset.duration) || 8000,
            );
        schedule();
    }
    const tabs = [...document.querySelectorAll("[data-category-key]")];
    const panes = [...document.querySelectorAll("[data-category-pane]")];
    const selectCategory = (key, updateUrl = false) => {
        if (!tabs.some((tab) => tab.dataset.categoryKey === key)) key = tabs[0]?.dataset.categoryKey;
        if (!key) return;
        tabs.forEach((tab) => {
            const active = tab.dataset.categoryKey === key;
            tab.classList.toggle("active", active);
            tab.setAttribute("aria-pressed", String(active));
        });
        panes.forEach((pane) => (pane.hidden = pane.dataset.categoryPane !== key));
        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set("category", key);
            url.searchParams.delete("service");
            history.replaceState({}, "", url);
        }
    };
    tabs.forEach((tab) => tab.addEventListener("click", () => selectCategory(tab.dataset.categoryKey, true)));
    const params = new URLSearchParams(window.location.search);
    selectCategory(params.get("category") || tabs[0]?.dataset.categoryKey);
    const requestedService = params.get("service");
    if (requestedService)
        window.setTimeout(
            () =>
                document
                    .querySelector(`[data-service-key="${CSS.escape(requestedService)}"]`)
                    ?.scrollIntoView({ block: "center" }),
            100,
        );
})();
