(() => {
    const box = document.getElementById("heroShowcase"),
        slides = [...document.querySelectorAll("[data-hero-slide]")],
        dots = [...document.querySelectorAll("[data-hero-dot]")];
    if (!box || slides.length < 2) return;
    let current = 0,
        timer;
    const show = (index) => {
            current = (index + slides.length) % slides.length;
            slides.forEach((slide, i) => slide.classList.toggle("active", i === current));
            dots.forEach((dot, i) => dot.classList.toggle("active", i === current));
        },
        play = () => {
            clearInterval(timer);
            timer = setInterval(() => show(current + 1), 3000);
        };
    document.getElementById("heroPrev").onclick = (event) => {
        event.preventDefault();
        show(current - 1);
        play();
    };
    document.getElementById("heroNext").onclick = (event) => {
        event.preventDefault();
        show(current + 1);
        play();
    };
    dots.forEach(
        (dot, index) =>
            (dot.onclick = (event) => {
                event.preventDefault();
                show(index);
                play();
            }),
    );
    document.addEventListener("visibilitychange", () => {
        if (document.hidden) clearInterval(timer);
        else play();
    });
    play();
})();
