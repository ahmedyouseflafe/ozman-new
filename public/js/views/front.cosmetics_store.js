(() => {
    const reducedMotion = window.matchMedia?.("(prefers-reduced-motion: reduce)").matches;
    let cartPopTimer;

    const animateCart = () => {
        const cart = document.querySelector(".beauty-cart");
        if (!cart) return;
        clearTimeout(cartPopTimer);
        cart.classList.remove("cart-pop");
        void cart.offsetWidth;
        cart.classList.add("cart-pop");
        cartPopTimer = setTimeout(() => cart.classList.remove("cart-pop"), 900);
    };

    const flyToCart = (button) => {
        if (reducedMotion) {
            animateCart();
            return;
        }

        const cartTarget = document.querySelector("#openBeautyCart") || document.querySelector(".beauty-cart");
        const source = button.closest(".beauty-product")?.querySelector(".product-picture");
        if (!cartTarget || !source) {
            animateCart();
            return;
        }

        const from = source.getBoundingClientRect();
        const to = cartTarget.getBoundingClientRect();
        const size = Math.min(68, Math.max(46, Math.min(from.width, from.height) * 0.34));
        const flyer = document.createElement("div");
        flyer.className = "beauty-cart-fly";
        flyer.style.width = size + "px";
        flyer.style.height = size + "px";
        flyer.style.left = from.left + (from.width - size) / 2 + "px";
        flyer.style.top = from.top + (from.height - size) / 2 + "px";

        const media = document.createElement("span");
        media.className = "beauty-cart-fly-media";
        const image = source.querySelector("img");
        if (image?.currentSrc || image?.src) {
            const clone = document.createElement("img");
            clone.src = image.currentSrc || image.src;
            clone.alt = "";
            media.append(clone);
        } else {
            media.innerHTML = '<i class="ti ti-shopping-bag"></i>';
        }
        const sparkles = document.createElement("span");
        sparkles.className = "beauty-cart-sparkles";
        sparkles.innerHTML = "<b>✦</b><b>✧</b><b>✦</b>";
        flyer.append(media, sparkles);

        document.body.append(flyer);
        const x = to.left + to.width / 2 - (from.left + from.width / 2);
        const y = to.top + to.height / 2 - (from.top + from.height / 2);
        const distance = Math.hypot(x, y) || 1;
        sparkles.style.setProperty("--trail-x", (-x / distance) * 56 + "px");
        sparkles.style.setProperty("--trail-y", (-y / distance) * 56 + "px");
        const flight = flyer.animate(
            [
                { transform: "translate(0, 0) scale(1)", opacity: 1, borderRadius: "16px" },
                {
                    transform: "translate(" + x * 0.35 + "px, " + (y * 0.18 - 74) + "px) scale(.82) rotate(-9deg)",
                    opacity: 1,
                    offset: 0.42,
                },
                {
                    transform: "translate(" + x + "px, " + y + "px) scale(.16) rotate(12deg)",
                    opacity: 0.22,
                    borderRadius: "50%",
                },
            ],
            { duration: 5000, easing: "cubic-bezier(.16,.8,.24,1)", fill: "forwards" },
        );

        flight.finished
            .catch(() => {})
            .finally(() => {
                flyer.remove();
                animateCart();
            });
    };

    document.querySelectorAll("[data-add-product]").forEach((button) => {
        button.addEventListener("click", () => flyToCart(button));
    });
})();
