const slides = [...document.querySelectorAll(".slide")];
let index = 0;
// يبدأ الصوت مكتوماً دائمًا؛ المتصفح لا يضمن السماح بالصوت قبل كبسة من المستخدم.
let muted = true;

function currentVideo() {
    return slides[index]?.querySelector("video") || null;
}

function syncVideoSound() {
    slides.forEach((slide, slideIndex) => {
        slide.querySelectorAll("video").forEach((video) => {
            const isCurrent = slideIndex === index;
            video.muted = !isCurrent || muted;
            video.defaultMuted = video.muted;

            if (isCurrent) {
                video.play().catch(() => {});
            } else {
                video.pause();
            }
        });
    });

    slides.forEach((slide, slideIndex) => {
        const toggle = slide.querySelector("[data-sound-toggle]");
        if (!toggle) return;
        const isCurrentVideo = slideIndex === index && Boolean(currentVideo());
        toggle.disabled = !isCurrentVideo;
        toggle.classList.toggle("is-active", isCurrentVideo && !muted);
        toggle.setAttribute("aria-pressed", String(isCurrentVideo && !muted));
        toggle.setAttribute("aria-label", muted ? "تفعيل صوت الفيديو" : "كتم صوت الفيديو");
        toggle.title = muted ? "تفعيل الصوت" : "كتم الصوت";
        toggle.innerHTML = `<i class="ti ${muted ? "ti-volume-off" : "ti-volume-3"}" aria-hidden="true"></i>`;
    });
}

document.querySelectorAll("[data-sound-toggle]").forEach((toggle) => {
    toggle.addEventListener("click", () => {
        if (!currentVideo()) return;
        muted = !muted;
        syncVideoSound();
    });
});

function nextSlide() {
    if (slides.length <= 1) return;
    slides[index].style.display = "none";
    index = (index + 1) % slides.length;
    slides[index].style.display = "";
    syncVideoSound();
    setTimeout(nextSlide, Number(slides[index].dataset.duration || 10000));
}

syncVideoSound();

if (slides.length > 1) {
    setTimeout(nextSlide, Number(slides[0].dataset.duration || 10000));
}
